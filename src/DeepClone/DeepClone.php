<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\DeepClone;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class DeepClone
{
    /** Internal classes that silently lose state through property restoration. */
    private const NOT_ROUND_TRIPPABLE = [
        'ZipArchive' => true,
        'XMLWriter' => true,
        'XMLReader' => true,
        'SNMP' => true,
        'tidy' => true,
    ];

    private static array $reflectors = [];
    private static array $prototypes = [];
    private static array $cloneable = [];
    private static array $instantiableWithoutConstructor = [];
    private static array $needsFullUnserialize = [];
    private static array $hydrators = [];
    private static array $simpleHydrators = [];
    private static array $scopeMaps = [];
    private static array $propertyScopes = []; // [class][key] = [declaring class, real name]; key is bare name, "\0*\0name", or "\0class\0name"
    private static array $protos = [];
    private static array $classInfo = [];
    private static \stdClass $sentinel;

    /**
     * @param list<string>|null $allowed_classes Classes that may be serialized
     *                                           (null = all, [] = none)
     */
    public static function deepclone_to_array(mixed $value, ?array $allowed_classes = null): array
    {
        if (\is_resource($value)) {
            throw new \DeepClone\NotInstantiableException('Type "'.get_resource_type($value).' resource" is not instantiable.');
        }

        if (!\is_object($value) && !(\is_array($value) && $value) || $value instanceof \UnitEnum) {
            return ['value' => $value];
        }

        $allowedSet = null;
        if (null !== $allowed_classes) {
            $allowedSet = [];
            foreach ($allowed_classes as $cls) {
                if (!\is_string($cls)) {
                    throw new \ValueError('deepclone_to_array(): Argument $allowed_classes must be an array of class names, '.self::valueName($cls).' given');
                }
                $allowedSet[strtolower($cls)] = true;
            }
        }

        $objectsPool = [];
        $refsPool = [];
        $objectsCount = 0;
        $isStatic = true;
        $refs = [];
        $refMasks = [];
        $topMask = null;

        try {
            $prepared = self::prepare([$value], $objectsPool, $refsPool, $objectsCount, $isStatic, $topMask, $allowedSet)[0];
        } finally {
            // Snapshot ref state while references still break cycles.
            foreach ($refsPool as $i => $v) {
                if ($v[0]->count) {
                    if ($v[1] instanceof \UnitEnum) {
                        $refs[1 + $i] = $v[1]::class.'::'.$v[1]->name;
                        $refMasks[1 + $i] = 'e';
                    } elseif (\is_object($v[1])) {
                        $oid = spl_object_id($v[1]);
                        $refs[1 + $i] = isset($objectsPool[$oid]) ? $objectsPool[$oid][0] : $v[2];
                        $refMasks[1 + $i] = true;
                    } elseif (\is_array($v[2])) {
                        $m = null;
                        $refs[1 + $i] = self::replaceRefs($v[2], $m);
                        if (null !== $m) {
                            $refMasks[1 + $i] = $m;
                        }
                    } else {
                        $refs[1 + $i] = $v[2];
                    }
                }
                $v[0] = $v[1];
            }
        }

        if ($isStatic) {
            return ['value' => $value];
        }

        $objectMeta = [];
        $properties = [];
        $resolve = [];
        $states = [];

        foreach ($objectsPool as [$id, $class, $props, $wakeup, $v, $propMask]) {
            $objectMeta[$id] = [$class, $wakeup];

            if (0 < $wakeup) {
                $states[$wakeup] = $id;
            } elseif (0 > $wakeup) {
                $states[-$wakeup] = null !== $propMask ? [$id, $props, $propMask] : [$id, $props];
                $props = [];
            }

            foreach ($props as $scope => $scopeProps) {
                foreach ($scopeProps as $name => $propValue) {
                    $properties[$scope][$name][$id] = $propValue;
                    if (isset($propMask[$scope][$name])) {
                        $resolve[$scope][$name][$id] = $propMask[$scope][$name];
                    }
                }
            }
        }

        ksort($states);

        // Unwrap remaining Reference objects into plain integers/arrays.
        $preparedMask = \is_int($prepared) ? null : ($topMask[0] ?? null);
        if (!\is_int($prepared)) {
            $m = null;
            $prepared = self::replaceRefs($prepared, $m);
            if (\is_array($preparedMask)) {
                if (null !== $m) {
                    $preparedMask = $m + array_filter($preparedMask, static fn ($v) => false !== $v);
                } else {
                    $preparedMask = array_filter($preparedMask, static fn ($v) => false !== $v) ?: null;
                }
            } elseif (false === $preparedMask) {
                $preparedMask = $m;
            }
        }

        foreach ($resolve as $scope => $names) {
            foreach ($names as $name => $ids) {
                foreach ($ids as $id => $marker) {
                    if (false !== $marker) {
                        continue;
                    }
                    $v = $properties[$scope][$name][$id];
                    if (!$v instanceof Reference) {
                        $m = null;
                        $properties[$scope][$name][$id] = self::replaceRefs($v, $m);
                        if (null !== $m) {
                            $ids[$id] = $m;
                        } else {
                            unset($ids[$id]);
                        }
                    } elseif ($v->count) {
                        $properties[$scope][$name][$id] = $v->id;
                        $ids[$id] = true;
                    } else {
                        $m = null;
                        $properties[$scope][$name][$id] = self::replaceRefs($v->value, $m);
                        if (null !== $m) {
                            $ids[$id] = $m;
                        } else {
                            unset($ids[$id]);
                        }
                    }
                }
                $resolve[$scope][$name] = $ids;
            }
        }

        foreach ($states as $k => $v) {
            if (\is_array($v)) {
                $m = null;
                $states[$k][1] = self::replaceRefs($v[1], $m);
                if ($m) {
                    $states[$k][2] = isset($v[2]) ? $v[2] + $m : $m;
                }
            }
        }

        // After unwrapping unshared hard refs, the value may have become static.
        if (!$objectMeta && !$refs && null === $preparedMask) {
            return ['value' => $prepared];
        }

        // Deduplicate class names in objectMeta.
        $classes = [];
        $classMap = [];
        $metaOut = [];
        foreach ($objectMeta as $id => [$class, $wakeup]) {
            if (!isset($classMap[$class])) {
                $classMap[$class] = \count($classes);
                $classes[] = $class;
            }
            $metaOut[$id] = 0 !== $wakeup ? [$classMap[$class], $wakeup] : $classMap[$class];
        }

        // When all entries share class index 0 with wakeup 0, store just the count.
        $n = \count($metaOut);
        foreach ($metaOut as $v) {
            if (0 !== $v) {
                $n = $metaOut;
                break;
            }
        }

        $data = [
            'classes' => 1 === \count($classes) ? $classes[0] : ($classes ?: ''),
            'objectMeta' => $n,
            'prepared' => $prepared,
        ];

        if (null !== $preparedMask) {
            $data['mask'] = $preparedMask;
        }
        if ($properties) {
            $data['properties'] = $properties;
        }
        if ($resolve) {
            $data['resolve'] = $resolve;
        }
        if ($states) {
            $data['states'] = $states;
        }
        if ($refs) {
            $data['refs'] = $refs;
        }
        if ($refMasks) {
            $data['refMasks'] = $refMasks;
        }

        return $data;
    }

    /**
     * @param list<string>|null $allowed_classes Classes that may be instantiated
     *                                           (null = all, [] = none)
     */
    public static function deepclone_from_array(array $data, ?array $allowed_classes = null): mixed
    {
        if (\array_key_exists('value', $data)) {
            return $data['value'];
        }

        if (!\array_key_exists('classes', $data)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) is missing required "classes" key');
        }
        if (!\array_key_exists('objectMeta', $data)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) is missing required "objectMeta" key');
        }
        if (!\array_key_exists('prepared', $data)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) is missing required "prepared" key');
        }
        $classes = $data['classes'];
        if (!\is_string($classes) && !\is_array($classes)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "classes" must be of type string|array, '.self::valueName($classes).' given');
        }
        $meta = $data['objectMeta'];
        if (!\is_int($meta) && !\is_array($meta)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" must be of type int|array, '.self::valueName($meta).' given');
        }
        foreach (['properties', 'resolve', 'states', 'refs', 'refMasks'] as $key) {
            if (\array_key_exists($key, $data) && !\is_array($data[$key])) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "'.$key.'" must be of type array, '.self::valueName($data[$key]).' given');
            }
        }

        if (\is_string($classes)) {
            $classes = '' !== $classes ? [$classes] : [];
        } else {
            foreach ($classes as $cls) {
                if (!\is_string($cls)) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "classes" entries must be of type string, '.self::valueName($cls).' given');
                }
            }
            $classes = array_values($classes);
        }
        $numClasses = \count($classes);

        if (null !== $allowed_classes) {
            $allowed = array_change_key_case(array_flip($allowed_classes));
            foreach ($classes as $cls) {
                if (!isset($allowed[strtolower($cls)])) {
                    throw new \ValueError('deepclone_from_array(): class "'.$cls.'" is not allowed');
                }
            }
            if (!isset($allowed['closure'])) {
                foreach (['mask', 'refMasks'] as $key) {
                    if (isset($data[$key]) && self::maskHasClosure($data[$key])) {
                        throw new \ValueError('deepclone_from_array(): class "Closure" is not allowed');
                    }
                }
                if (isset($data['resolve'])) {
                    foreach ($data['resolve'] as $scope) {
                        if (\is_array($scope)) {
                            foreach ($scope as $name) {
                                if (self::maskHasClosure($name)) {
                                    throw new \ValueError('deepclone_from_array(): class "Closure" is not allowed');
                                }
                            }
                        }
                    }
                }
                if (isset($data['states'])) {
                    foreach ($data['states'] as $state) {
                        if (\is_array($state) && isset($state[2]) && self::maskHasClosure($state[2])) {
                            throw new \ValueError('deepclone_from_array(): class "Closure" is not allowed');
                        }
                    }
                }
            }
        }

        // $expectedStates maps ids that flag a state replay to their wakeup
        // sign (+1 / -1 is enough; the raw value is fine). Stays empty in
        // the common no-wakeup case so the later validation pass is O(1).
        $expectedStates = [];
        if (\is_int($meta)) {
            if ($meta < 0) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" count must be non-negative, '.$meta.' given');
            }
            // Sanity cap: the IS_LONG form specifies a count without the per-
            // object payload, so a tiny input can demand huge allocations.
            // Legitimate use never needs more than ~1M objects in a single
            // payload — beyond that, use the array form which is naturally
            // bounded by the hash-table size.
            if ($meta > 0x100000) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" count out of range: '.$meta);
            }
            if ($meta > 0 && $numClasses < 1) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" references class index 0 but "classes" is empty');
            }
            $objectMeta = $meta ? array_fill(0, $meta, [$classes[0], 0]) : [];
            $numObjects = $meta;
        } else {
            $numObjects = \count($meta);
            $objectMeta = [];
            foreach ($meta as $id => $v) {
                if (!\is_int($id) || $id < 0 || $id >= $numObjects) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry index '.$id.' out of range');
                }
                if (\is_array($v)) {
                    $cidx = $v[0] ?? null;
                    $wakeup = $v[1] ?? null;
                    if (!\is_int($cidx) || !\is_int($wakeup)) {
                        throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry '.$id.' must be [int, int]');
                    }
                } elseif (\is_int($v)) {
                    $cidx = $v;
                    $wakeup = 0;
                } else {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry '.$id.' must be of type int|array, '.self::valueName($v).' given');
                }
                if ($cidx < 0 || $cidx >= $numClasses) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry '.$id.' has out-of-range class index '.$cidx);
                }
                $objectMeta[$id] = [$classes[$cidx], $wakeup];
                if (0 !== $wakeup) {
                    $expectedStates[$id] = $wakeup;
                }
            }
        }

        return self::reconstruct(
            $data['prepared'],
            $objectMeta,
            $numObjects,
            $data['properties'] ?? [],
            $data['resolve'] ?? [],
            $data['states'] ?? [],
            $data['refs'] ?? [],
            $data['mask'] ?? null,
            $data['refMasks'] ?? [],
            $allowed_classes,
            $expectedStates,
        );
    }

    public static function deepclone_hydrate(object|string $object_or_class, array $vars = [], int $flags = 0): object
    {
        if ($flags & ~(\DEEPCLONE_HYDRATE_CALL_HOOKS | \DEEPCLONE_HYDRATE_NO_LAZY_INIT | \DEEPCLONE_HYDRATE_PRESERVE_REFS)) {
            throw new \ValueError('deepclone_hydrate(): Argument #3 ($flags) contains unknown bits');
        }
        if (($flags & \DEEPCLONE_HYDRATE_CALL_HOOKS) && ($flags & \DEEPCLONE_HYDRATE_NO_LAZY_INIT)) {
            throw new \ValueError('deepclone_hydrate(): Argument #3 ($flags) DEEPCLONE_HYDRATE_CALL_HOOKS and DEEPCLONE_HYDRATE_NO_LAZY_INIT are mutually exclusive');
        }

        if (\is_string($class = $object_or_class)) {
            $r = self::$reflectors[$class] ??= self::getClassReflector($class);
            if (self::$cloneable[$class]) {
                $object = clone self::$prototypes[$class];
            } elseif (self::$instantiableWithoutConstructor[$class]) {
                $object = $r->newInstanceWithoutConstructor();
            } elseif (null === self::$prototypes[$class]) {
                throw new \DeepClone\NotInstantiableException('Class "'.$class.'" is not instantiable.');
            } elseif ($r->implementsInterface('Serializable') && !method_exists($class, '__unserialize')) {
                $object = unserialize('C:'.\strlen($class).':"'.$class.'":0:{}');
            } else {
                $object = unserialize('O:'.\strlen($class).':"'.$class.'":0:{}');
            }
        } else {
            $r = null;
            $object = $object_or_class;
            $class = $object::class;
        }

        if (!$vars) {
            return $object;
        }

        // Look up each key in the pre-built (propertyScopes) index, which
        // handles all three mangled-key shapes uniformly — bare "foo",
        // "\0*\0foo", "\0Class\0foo" — with a single hash lookup. Bare
        // "foo" resolves to the most-derived declaring class; shadowed
        // parent-privates are reachable via "\0Parent\0foo". Unknown
        // keys fall through to the object's own class scope.
        //
        // Scan first: if every key maps to $class, hand $vars straight
        // to the $class hydrator and skip the intermediate grouping.
        $r ??= self::$reflectors[$class] ?? new \ReflectionClass($class);
        $propertyScopes = self::$propertyScopes[$class] ??= self::getPropertyScopes($r);

        $needsGroup = false;
        foreach ($vars as $name => $_) {
            if (\array_key_exists($name, $propertyScopes) ? $class !== $propertyScopes[$name][0] : "\0" === ($name[0] ?? '')) {
                $needsGroup = true;
                break;
            }
        }

        $effectiveFlags = $flags & \DEEPCLONE_HYDRATE_CALL_HOOKS;
        if (\PHP_VERSION_ID >= 80400 && ($flags & \DEEPCLONE_HYDRATE_NO_LAZY_INIT)) {
            $r ??= self::$reflectors[$class] ?? new \ReflectionClass($class);
            if ($r->isUninitializedLazyObject($object)) {
                $effectiveFlags |= \DEEPCLONE_HYDRATE_NO_LAZY_INIT;
            }
        }

        $hasRefs = false;
        if ($flags & \DEEPCLONE_HYDRATE_PRESERVE_REFS) {
            foreach ($vars as $k => $_) {
                if (\ReflectionReference::fromArrayElement($vars, $k)) {
                    $hasRefs = true;
                    break;
                }
            }
        }

        if (!$needsGroup) {
            // Fast path: every key resolves to $class. Hand $vars straight
            // to the $class hydrator — no grouping, no re-keying.
            $cacheKey = $effectiveFlags ? $effectiveFlags.$class : $class;
            (self::$simpleHydrators[$cacheKey] ??= self::getSimpleHydrator($class, $effectiveFlags))($vars, $object, $hasRefs);

            return $object;
        }

        // Full parse: group values by their write scope and real name. A
        // NUL-prefixed key not in $propertyScopes cannot resolve to a
        // declared property, so we reject it here — which in turn means
        // every scope we hand to the hydrator is $class or a real parent.
        $scoped = [];
        foreach ($vars as $name => &$value) {
            if ([$scopeName, $realName] = $propertyScopes[$name] ?? null) {
                $scoped[$scopeName][$realName] = &$value;
                continue;
            }
            if (!\is_string($name) || "\0" !== ($name[0] ?? '')) {
                $scoped[$class][$name] = &$value;
                continue;
            }
            // NUL-prefixed key that isn't in $propertyScopes: either malformed
            // syntax, an unknown scope class, or a valid scope + unknown prop.
            // Differentiate to match the ext's error messages.
            $sep = strpos($name, "\0", 1);
            if (false === $sep || 1 === $sep || str_contains(substr($name, $sep + 1), "\0")) {
                throw new \ValueError('deepclone_hydrate(): Argument #2 ($vars) contains an invalid mangled key');
            }
            $scopeName = substr($name, 1, $sep - 1);
            $realName = substr($name, $sep + 1);
            if ('*' !== $scopeName && 'stdClass' !== $scopeName
                && $scopeName !== $class
                && (!is_a($class, $scopeName, true) || interface_exists($scopeName, false))
            ) {
                throw new \ValueError(\sprintf('deepclone_hydrate(): Argument #2 ($vars) key scope "%s" is not a parent of "%s"', $scopeName, $class));
            }
            // Valid scope, but the targeted slot isn't declared — reject
            // instead of silently creating a dynamic property, since the
            // mangled form specifically targets a declared protected/private slot.
            throw new \ValueError(\sprintf('deepclone_hydrate(): Argument #2 ($vars) key scope "%s" does not declare a "%s" property', '*' === $scopeName ? $class : $scopeName, $realName));
        }
        unset($value);

        foreach ($scoped as $scope => $properties) {
            $cacheKey = $effectiveFlags ? $effectiveFlags.$scope : $scope;
            (self::$simpleHydrators[$cacheKey] ??= self::getSimpleHydrator($scope, $effectiveFlags))($properties, $object, $hasRefs);
        }

        return $object;
    }

    /**
     * Builds a per-class map keyed by every shape a caller might use to
     * target a declared property:
     *   - bare "name"          (public/protected inherited or own; for private
     *                           declared on $class; also set to the most-derived
     *                           private when a parent-private shares the name)
     *   - "\0*\0name"          (for protected props — points to the declaring
     *                           class entry)
     *   - "\0ClassName\0name"  (for private props declared on ClassName, where
     *                           ClassName is $class or a parent).
     *
     * Each entry is [$declaringClass, $propertyName] — the scope and real
     * name to use for grouping + dispatch. Adapted from VarExporter's
     * LazyObjectRegistry::getPropertyScopes().
     */
    private static function getPropertyScopes(\ReflectionClass $class): array
    {
        $propertyScopes = [];
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $name = $property->name;
            $entry = [$property->class, $name];

            if ($property->isPrivate()) {
                $propertyScopes["\0".$property->class."\0".$name] = $propertyScopes[$name] = $entry;

                continue;
            }

            $propertyScopes[$name] = $entry;

            if ($property->isProtected()) {
                $propertyScopes["\0*\0".$name] = $entry;
            }
        }

        while ($class = $class->getParentClass()) {
            foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $entry = [$property->class, $property->name];
                $propertyScopes["\0".$property->class."\0".$property->name] = $entry;
                $propertyScopes[$property->name] ??= $entry;
            }
        }

        return $propertyScopes;
    }

    /**
     * Returns a type name matching zend_zval_value_name() output for
     * error messages compatible with the C extension.
     */
    private static function valueName(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }
        if (true === $value) {
            return 'true';
        }
        if (false === $value) {
            return 'false';
        }
        if (\is_object($value)) {
            return $value::class;
        }

        return match (true) {
            \is_int($value) => 'int',
            \is_float($value) => 'float',
            \is_string($value) => 'string',
            \is_array($value) => 'array',
            \is_resource($value) => 'resource',
            default => get_debug_type($value),
        };
    }

    private static function prepare($values, &$objectsPool, &$refsPool, &$objectsCount, &$valuesAreStatic, &$mask = null, ?array $allowedSet = null)
    {
        $sentinel = self::$sentinel ??= new \stdClass();
        $refs = $values;
        foreach ($values as $k => $value) {
            if (\is_resource($value)) {
                throw new \DeepClone\NotInstantiableException('Type "'.get_resource_type($value).' resource" is not instantiable.');
            }
            $refs[$k] = $sentinel;

            if ($isRef = !$valueIsStatic = $values[$k] !== $sentinel) {
                $values[$k] = &$value; // Break hard reference
                unset($value);
                $refs[$k] = $value = $values[$k];
                if ($value instanceof Reference && 0 > $value->id) {
                    $valuesAreStatic = false;
                    ++$value->count;
                    $mask[$k] = false;
                    continue;
                }
                $refsPool[] = [&$refs[$k], $value, &$value];
                $refs[$k] = $values[$k] = new Reference(-\count($refsPool), $value);
            }

            if (\is_array($value)) {
                if ($value) {
                    $m = null;
                    $value = self::prepare($value, $objectsPool, $refsPool, $objectsCount, $valueIsStatic, $m, $allowedSet);
                    if (null !== $m) {
                        $mask[$k] = $m;
                    }
                }
                goto handle_value;
            } elseif (!\is_object($value) || $value instanceof \UnitEnum) {
                goto handle_value;
            }

            $valueIsStatic = false;
            $oid = spl_object_id($value);
            if (isset($objectsPool[$oid])) {
                ++$objectsCount;
                $value = $objectsPool[$oid][0];
                $mask[$k] = true;
                goto handle_value;
            }

            if ($value instanceof \Closure && ($r = new \ReflectionFunction($value)) && !(\PHP_VERSION_ID >= 80200 ? $r->isAnonymous() : str_contains($r->name, "@anonymous\0"))) {
                if (null !== $allowedSet && !isset($allowedSet['closure'])) {
                    throw new \ValueError('deepclone_to_array(): class "Closure" is not allowed');
                }
                $callable = [$r->getClosureThis() ?? (\PHP_VERSION_ID >= 80111 ? $r->getClosureCalledClass() : $r->getClosureScopeClass())?->name, $r->name];
                $rm = $callable[0] ? new \ReflectionMethod(...$callable) : null;
                $unused = null;
                $callable = self::prepare($callable, $objectsPool, $refsPool, $objectsCount, $valueIsStatic, $unused, $allowedSet);
                $value = !($rm?->isPublic() ?? true) ? [$callable, $rm->class, $rm->name] : $callable;
                $mask[$k] = 0;

                goto handle_value;
            }

            $class = $value::class;

            if (null !== $allowedSet && !isset($allowedSet[strtolower($class)])) {
                throw new \ValueError('deepclone_to_array(): class "'.$class.'" is not allowed');
            }

            if ('stdClass' === $class) {
                self::$reflectors[$class] ??= self::getClassReflector($class);
                $arrayValue = (array) $value;
                $objectsPool[$oid] = [$id = \count($objectsPool)];
                $m = null;
                $properties = $arrayValue ? self::prepare(['stdClass' => $arrayValue], $objectsPool, $refsPool, $objectsCount, $valueIsStatic, $m, $allowedSet) : [];
                ++$objectsCount;
                $objectsPool[$oid] = [$id, 'stdClass', $properties, 0, $value, $m];
                $value = $id;
                $mask[$k] = true;
                goto handle_value;
            }

            $reflector = self::$reflectors[$class] ??= self::getClassReflector($class);
            $properties = [];
            $sleep = null;
            $proto = self::$prototypes[$class];

            if (self::$classInfo[$class][2] ??= $reflector->hasMethod('__serialize') ? ($reflector->getMethod('__serialize')->isPublic() ?: $reflector->getMethod('__serialize')) : false) {
                if (self::$classInfo[$class][2] instanceof \ReflectionMethod) {
                    throw new \Error('Call to '.(self::$classInfo[$class][2]->isProtected() ? 'protected' : 'private').' method "'.$class.'::__serialize()".');
                }

                if (!\is_array($arrayValue = $value->__serialize())) {
                    throw new \TypeError($class.'::__serialize() must return an array');
                }

                if ($hasUnserialize = self::$classInfo[$class][0] ??= $reflector->hasMethod('__unserialize')) {
                    $properties = $arrayValue;
                    goto prepare_value;
                }
            } elseif ($value instanceof \Serializable || $value instanceof \__PHP_Incomplete_Class) {
                ++$objectsCount;
                $objectsPool[$oid] = [$id = \count($objectsPool), serialize($value), [], 0, $value, null];
                $value = $id;
                $mask[$k] = true;
                goto handle_value;
            } else {
                if (self::$classInfo[$class][3] ??= $reflector->hasMethod('__sleep')) {
                    if (!\is_array($sleep = $value->__sleep())) {
                        trigger_error('serialize(): __sleep should return an array only containing the names of instance-variables to serialize', \E_USER_NOTICE);
                        $value = null;
                        goto handle_value;
                    }
                    $sleep = array_flip($sleep);
                }

                $arrayValue = (array) $value;
            }

            $proto = self::$protos[$class] ??= (array) $proto;

            if (null === $scopeMap = self::$scopeMaps[$class] ?? null) {
                $scopeMap = [];
                $parent = $reflector;
                do {
                    foreach ($parent->getProperties() as $p) {
                        if (!$p->isStatic() && !isset($scopeMap[$p->name])) {
                            $scopeMap[$p->name] = !$p->isPublic() || (\PHP_VERSION_ID >= 80400 ? $p->isProtectedSet() || $p->isPrivateSet() : $p->isReadOnly()) ? $p->class : 'stdClass';
                        }
                    }
                } while ($parent = $parent->getParentClass());
                self::$scopeMaps[$class] = $scopeMap;
            }

            foreach ($arrayValue as $name => $v) {
                $i = 0;
                $n = (string) $name;
                if ('' === $n || "\0" !== $n[0]) {
                    $c = $scopeMap[$n] ?? 'stdClass';
                } elseif ('*' === $n[1]) {
                    $n = substr($n, 3);
                    $c = $scopeMap[$n] ?? $reflector->getProperty($n)->class;
                } else {
                    $i = strpos($n, "\0", 2);
                    $c = substr($n, 1, $i - 1);
                    $n = substr($n, 1 + $i);
                }
                if (null !== $sleep) {
                    if (!isset($sleep[$name]) && (!isset($sleep[$n]) || ($i && $c !== $class))) {
                        unset($arrayValue[$name]);
                        continue;
                    }
                    unset($sleep[$name], $sleep[$n]);
                }
                if ("\x00Error\x00trace" === $name || "\x00Exception\x00trace" === $name || "\x00*\x00file" === $name || "\x00*\x00line" === $name || !\array_key_exists($name, $proto) || $proto[$name] !== $v) {
                    $properties[$c][$n] = $v;
                }
            }
            if ($sleep) {
                foreach ($sleep as $n => $v) {
                    if (\is_string($n) && $reflector->hasProperty($n)) {
                        continue;
                    }
                    trigger_error(\sprintf('serialize(): "%s" returned as member variable from __sleep() but does not exist', $n), \E_USER_NOTICE);
                }
            }
            if ($hasUnserialize = self::$classInfo[$class][0] ??= $reflector->hasMethod('__unserialize')) {
                $properties = $arrayValue;
            }

            prepare_value:
            $objectsPool[$oid] = [$id = \count($objectsPool)];
            $m = null;
            $properties = self::prepare($properties, $objectsPool, $refsPool, $objectsCount, $valueIsStatic, $m, $allowedSet);
            ++$objectsCount;
            $objectsPool[$oid] = [$id, $class, $properties, $hasUnserialize ? -$objectsCount : ((self::$classInfo[$class][1] ??= $reflector->hasMethod('__wakeup')) ? $objectsCount : 0), $value, $m];

            $value = $id;
            $mask[$k] = true;

            handle_value:
            if ($isRef) {
                $mask[$k] = false;
                unset($value); // Break the hard reference created above
            } elseif (!$valueIsStatic) {
                $values[$k] = $value;
            }
            $valuesAreStatic = $valueIsStatic && $valuesAreStatic;
        }

        return $values;
    }

    private static function reconstruct($prepared, $objectMeta, $numObjects, $properties, $resolve, $states, $refs, $preparedMask = null, $refMasks = [], ?array $allowedClasses = null, array $expectedStates = [])
    {
        $objects = [];

        foreach ($objectMeta as $id => [$class]) {
            if (':' === ($class[1] ?? null)) {
                $objects[$id] = unserialize($class, null !== $allowedClasses ? ['allowed_classes' => $allowedClasses] : []);
                continue;
            }
            try {
                self::$reflectors[$class] ??= self::getClassReflector($class);
            } catch (\DeepClone\ClassNotFoundException) {
                throw new \DeepClone\ClassNotFoundException('Class "'.$class.'" not found.');
            }

            if (self::$needsFullUnserialize[$class] ?? false) {
                $objects[$id] = null; // placeholder — finalized below or in states loop
            } elseif (self::$cloneable[$class]) {
                $objects[$id] = clone self::$prototypes[$class];
            } elseif (self::$instantiableWithoutConstructor[$class]) {
                $objects[$id] = self::$reflectors[$class]->newInstanceWithoutConstructor();
            } elseif (null === self::$prototypes[$class]) {
                throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.');
            } elseif (self::$reflectors[$class]->implementsInterface('Serializable') && !method_exists($class, '__unserialize')) {
                $objects[$id] = unserialize('C:'.\strlen($class).':"'.$class.'":0:{}');
            } else {
                $objects[$id] = unserialize('O:'.\strlen($class).':"'.$class.'":0:{}');
            }
        }

        // Eagerly finalize deferred objects whose state has no object-ref masks,
        // so they are real instances when the properties loop resolves references to them.
        foreach ($states as $state) {
            if (!\is_array($state) || null !== $objects[$state[0]] || isset($state[2])) {
                continue;
            }
            $class = $objectMeta[$state[0]][0];
            $ser = serialize($state[1] ?? []);
            if (false === $obj = unserialize('O:'.\strlen($class).':"'.$class.'"'.substr($ser, strpos($ser, ':', 1)))) {
                throw new \ValueError('deepclone_from_array(): could not reconstruct "'.$class.'" via __unserialize()');
            }
            $objects[$state[0]] = $obj;
        }

        foreach ($refMasks as $k => $m) {
            $refs[$k] = self::resolveWithMask($refs[$k], $m, $objects, $refs);
        }

        foreach ($properties as $scope => $scopeProps) {
            if (!\is_string($scope)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "properties" keys must be of type string');
            }
            if (!\is_array($scopeProps)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "properties" entry for scope "'.$scope.'" must be of type array, '.self::valueName($scopeProps).' given');
            }
            if ('stdClass' !== $scope && !class_exists($scope, false)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "properties" scope "'.$scope.'" is not a loaded class name');
            }
            $resolveScope = null;
            if (isset($resolve[$scope])) {
                if (!\is_array($resolve[$scope])) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "resolve" entry for scope "'.$scope.'" must be of type array, '.self::valueName($resolve[$scope]).' given');
                }
                $resolveScope = $resolve[$scope];
            }
            foreach ($scopeProps as $name => $idValues) {
                if (!\is_string($name)) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "properties" inner keys must be of type string');
                }
                if (!\is_array($idValues)) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "properties" value for "'.$scope.'::'.$name.'" must be of type array, '.self::valueName($idValues).' given');
                }
                $resolveIds = null;
                if ($resolveScope && isset($resolveScope[$name])) {
                    if (!\is_array($resolveScope[$name])) {
                        throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "resolve" value for "'.$scope.'::'.$name.'" must be of type array, '.self::valueName($resolveScope[$name]).' given');
                    }
                    $resolveIds = $resolveScope[$name];
                }
                if (null === $resolveIds) {
                    continue;
                }
                foreach ($resolveIds as $id => $marker) {
                    if (true === $marker) {
                        if (null === ($v = $scopeProps[$name][$id] ?? null) || !\is_int($v)) {
                            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, object reference value must be of type int, '.self::valueName($v).' given');
                        }
                        if ($v >= 0) {
                            if (!isset($objects[$v])) {
                                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, unknown object id '.$v);
                            }
                            $scopeProps[$name][$id] = $objects[$v];
                        } else {
                            if (\PHP_INT_MIN === $v) {
                                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, ref id out of range');
                            }
                            if (!isset($refs[-$v])) {
                                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, unknown ref id '.(-$v));
                            }
                            $scopeProps[$name][$id] = $refs[-$v];
                        }
                    } elseif (0 === $marker) {
                        $scopeProps[$name][$id] = self::resolveNamedClosureScalar($scopeProps[$name][$id] ?? null, $objects, $refs);
                    } else {
                        $scopeProps[$name][$id] = self::resolveWithMask($scopeProps[$name][$id] ?? null, $marker, $objects, $refs);
                    }
                }
            }
            (self::$hydrators[$scope] ??= self::getHydrator($scope))($scopeProps, $objects);
        }

        foreach ($states as $state) {
            if (\is_array($state)) {
                $zid = $state[0] ?? null;
                $sprops = $state[1] ?? null;
                if (!\is_int($zid) || !\array_key_exists(1, $state)) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed "states" entry: expected [int, mixed, mixed?]');
                }
                if ($zid < 0 || $zid >= $numObjects) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "states" entry references unknown object id '.$zid);
                }
                if (!isset($expectedStates[$zid]) || $expectedStates[$zid] >= 0) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "states" has an __unserialize entry for object id '.$zid.' but "objectMeta" does not flag it for __unserialize');
                }
                unset($expectedStates[$zid]);
                if (null === $obj = $objects[$zid]) {
                    // Internal final class with __unserialize that rejects empty unserialize
                    // reconstruct via the full O: serialization form (same as PHP's unserialize).
                    $class = $objectMeta[$zid][0];
                    $resolvedProps = isset($state[2]) ? self::resolveWithMask($sprops, $state[2], $objects, $refs) : $sprops;
                    $ser = serialize($resolvedProps);
                    if (false === $obj = unserialize('O:'.\strlen($class).':"'.$class.'"'.substr($ser, strpos($ser, ':', 1)))) {
                        throw new \ValueError('deepclone_from_array(): could not reconstruct "'.$class.'" via __unserialize()');
                    }
                    $objects[$zid] = $obj;
                    continue;
                }
                $objClass = $obj::class;
                if (!method_exists($obj, '__unserialize')) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "states" entry references object id '.$zid.' whose class '.$objClass.' has no __unserialize() method');
                }
                $resolvedProps = isset($state[2]) ? self::resolveWithMask($sprops, $state[2], $objects, $refs) : $sprops;
                $obj->__unserialize($resolvedProps);
            } elseif (\is_int($state)) {
                if ($state < 0 || $state >= $numObjects) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "states" entry references unknown object id '.$state);
                }
                if (!isset($expectedStates[$state]) || $expectedStates[$state] <= 0) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "states" has a __wakeup entry for object id '.$state.' but "objectMeta" does not flag it for __wakeup');
                }
                unset($expectedStates[$state]);
                if (method_exists($objects[$state], '__wakeup')) {
                    $objects[$state]->__wakeup();
                }
            } else {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "states" entry must be of type int|array, '.self::valueName($state).' given');
            }
        }

        if ($expectedStates) {
            $id = array_key_first($expectedStates);
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry '.$id.' flags object for state replay but no matching "states" entry was found');
        }

        if (\is_int($prepared)) {
            if ($prepared >= 0) {
                if ($prepared >= $numObjects) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "prepared" references unknown object id '.$prepared);
                }

                return $objects[$prepared];
            }
            if (!isset($refs[-$prepared])) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) "prepared" references unknown ref id '.(-$prepared));
            }

            return $refs[-$prepared];
        }

        if (null !== $preparedMask) {
            return self::resolveWithMask($prepared, $preparedMask, $objects, $refs);
        }

        return $prepared;
    }

    private static function resolveWithMask($value, $mask, $objects, &$refs)
    {
        if (true === $mask) {
            if (!\is_int($value)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, object reference value must be of type int, '.self::valueName($value).' given');
            }
            if ($value >= 0) {
                if (!isset($objects[$value])) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, unknown object id '.$value);
                }

                return $objects[$value];
            }
            if (!isset($refs[-$value])) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, unknown ref id '.(-$value));
            }

            return $refs[-$value];
        }

        if (false === $mask) {
            if (!\is_int($value)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, hard-ref value must be of type int, '.self::valueName($value).' given');
            }
            if ($value >= 0 || \PHP_INT_MIN === $value) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, ref id out of range');
            }
            $rid = -$value;
            if (!isset($refs[$rid])) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, unknown ref id '.$rid);
            }

            return $refs[$rid];
        }

        if (0 === $mask) {
            return self::resolveNamedClosureScalar($value, $objects, $refs);
        }

        if ('e' === $mask) {
            if (!\is_string($value)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, enum value must be of type string, '.self::valueName($value).' given');
            }
            if (false === $sepPos = strpos($value, '::')) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, enum value must match "Class::Case"');
            }
            $className = substr($value, 0, $sepPos);
            if (!enum_exists($className)) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, enum class "'.$value.'" not found');
            }
            $caseName = substr($value, $sepPos + 2);
            try {
                return \constant($value);
            } catch (\Error) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, enum case "'.$value.'" not found');
            }
        }

        if (!\is_array($mask)) {
            return $value;
        }

        if (!\is_array($value)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, array-mask value must be of type array, '.self::valueName($value).' given');
        }

        foreach ($mask as $k => $m) {
            if (false === $m) {
                $slot = $value[$k] ?? null;
                if (!\is_int($slot)) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, hard-ref slot must be of type int, '.self::valueName($slot).' given');
                }
                if ($slot >= 0 || \PHP_INT_MIN === $slot) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, ref id out of range');
                }
                $rid = -$slot;
                if (!isset($refs[$rid])) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, unknown ref id '.$rid);
                }
                $value[$k] = &$refs[$rid];
            } else {
                $value[$k] = self::resolveWithMask($value[$k] ?? null, $m, $objects, $refs);
            }
        }

        return $value;
    }

    private static function resolveNamedClosureScalar($value, $objects, $refs)
    {
        if (!\is_array($value)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure value must be of type array, '.self::valueName($value).' given');
        }
        if (!\array_key_exists(0, $value) || !\array_key_exists(1, $value)) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure value must have at least 2 elements');
        }

        $method = null;

        if (\is_array($value[0])) {
            $callable = $value[0];
            if (!\is_string($value[1])) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure private class name must be of type string, '.self::valueName($value[1]).' given');
            }
            if (!\array_key_exists(2, $value) || !\is_string($value[2])) {
                throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure private method name must be of type string');
            }
            $method = new \ReflectionMethod($value[1], $value[2]);
        } else {
            $callable = $value;
        }

        if (!\array_key_exists(0, $callable) || !\array_key_exists(1, $callable) || !\is_string($callable[1])) {
            throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure callable must be [obj_or_class_or_null, string]');
        }

        $obj = $callable[0];
        if (\is_int($obj)) {
            if ($obj >= 0) {
                if (!isset($objects[$obj])) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure references unknown id '.$obj);
                }
                $obj = $objects[$obj];
            } else {
                if (!isset($refs[-$obj])) {
                    throw new \ValueError('deepclone_from_array(): Argument #1 ($data) malformed payload, named-closure references unknown id '.$obj);
                }
                $obj = $refs[-$obj];
            }
        }
        $name = $callable[1];

        if (!($method?->isPublic() ?? true)) {
            return $method->getClosure(\is_object($obj) ? $obj : null);
        }

        if (!$obj) {
            return $name(...);
        }

        return \is_object($obj) ? $obj->$name(...) : $obj::$name(...);
    }

    private static function maskHasClosure($mask): bool
    {
        if (0 === $mask) {
            return true;
        }
        if (\is_array($mask)) {
            foreach ($mask as $v) {
                if (self::maskHasClosure($v)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function replaceRefs($value, &$mask)
    {
        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                if ($v instanceof Reference || \is_array($v)) {
                    $m = null;
                    $value[$k] = self::replaceRefs($v, $m);
                    if (null !== $m) {
                        $mask[$k] = $m;
                    }
                }
            }
        }

        if (!$value instanceof Reference) {
            return $value;
        }

        if ($value->id >= 0) {
            $mask = true;

            return $value->id;
        }
        if ($value->count) {
            $mask = false;

            return $value->id;
        }

        // Unshared hard ref (count=0): unwrap the inner value
        return self::replaceRefs($value->value, $mask);
    }

    private static function getClassReflector($class, $instantiableWithoutConstructor = false, $cloneable = null)
    {
        if (!($isClass = class_exists($class)) && !interface_exists($class, false) && !trait_exists($class, false)) {
            throw new \DeepClone\ClassNotFoundException('Class "'.$class.'" not found.');
        }
        if (isset(self::NOT_ROUND_TRIPPABLE[$class])) {
            throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.');
        }
        $reflector = new \ReflectionClass($class);

        if ($instantiableWithoutConstructor) {
            $proto = $reflector->newInstanceWithoutConstructor();
        } elseif (!$isClass || $reflector->isAbstract() || $reflector->isEnum()) {
            throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.');
        } elseif ($reflector->name !== $class) {
            $reflector = self::$reflectors[$name = $reflector->name] ??= self::getClassReflector($name, false, $cloneable);
            self::$cloneable[$class] = self::$cloneable[$name];
            self::$instantiableWithoutConstructor[$class] = self::$instantiableWithoutConstructor[$name];
            self::$prototypes[$class] = self::$prototypes[$name];

            return $reflector;
        } else {
            try {
                $proto = $reflector->newInstanceWithoutConstructor();
                $instantiableWithoutConstructor = true;
            } catch (\ReflectionException) {
                $proto = $reflector->implementsInterface('Serializable') && !method_exists($class, '__unserialize') ? 'C:' : 'O:';
                if ('C:' === $proto && !$reflector->getMethod('unserialize')->isInternal()) {
                    $proto = null;
                } else {
                    try {
                        $proto = @unserialize($proto.\strlen($class).':"'.$class.'":0:{}');
                    } catch (\Exception $e) {
                        if (__FILE__ !== $e->getFile()) {
                            throw $e;
                        }
                        if (!method_exists($class, '__unserialize')) {
                            throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.', 0, $e);
                        }
                        self::$needsFullUnserialize[$class] = true;
                        $proto = null;
                    }
                    if (false === $proto) {
                        if (!method_exists($class, '__unserialize')) {
                            throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.');
                        }
                        self::$needsFullUnserialize[$class] = true;
                        $proto = null;
                    }
                }
            }
            if (null !== $proto && !$proto instanceof \Throwable && !$proto instanceof \Serializable && !method_exists($class, '__sleep') && !method_exists($class, '__serialize')) {
                try {
                    serialize($proto);
                } catch (\Exception $e) {
                    throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.', 0, $e);
                }
            }
        }

        if (null === $cloneable) {
            if (($proto instanceof \Reflector || $proto instanceof \ReflectionGenerator || $proto instanceof \ReflectionType || $proto instanceof \IteratorIterator || $proto instanceof \RecursiveIteratorIterator) && (!$proto instanceof \Serializable && !method_exists($class, '__wakeup') && !method_exists($class, '__unserialize'))) {
                throw new \DeepClone\NotInstantiableException('Type "'.$class.'" is not instantiable.');
            }

            $cloneable = $reflector->isCloneable() && !$reflector->hasMethod('__clone');
        }

        self::$cloneable[$class] = $cloneable;
        self::$instantiableWithoutConstructor[$class] = $instantiableWithoutConstructor;
        self::$prototypes[$class] = $proto;

        if ($proto instanceof \Throwable) {
            static $setTrace;

            if (null === $setTrace) {
                $setTrace = [
                    new \ReflectionProperty(\Error::class, 'trace'),
                    new \ReflectionProperty(\Exception::class, 'trace'),
                ];
                $setTrace[0] = $setTrace[0]->setValue(...);
                $setTrace[1] = $setTrace[1]->setValue(...);
            }

            $setTrace[$proto instanceof \Exception]($proto, []);
        }

        return $reflector;
    }

    private static function getHydrator($class)
    {
        $baseHydrator = self::$hydrators['stdClass'] ??= static function ($properties, $objects) {
            foreach ($properties as $name => $values) {
                foreach ($values as $i => $v) {
                    $objects[$i]->$name = $v;
                }
            }
        };

        if ('stdClass' === $class) {
            return $baseHydrator;
        }

        if ('TypeError' === $class) {
            $class = 'Error';
        } elseif ('ErrorException' === $class) {
            $class = 'Exception';
        }

        // self::$reflectors must be populated via getClassReflector() (companion caches), so read with ??.
        $classReflector = self::$reflectors[$class] ?? new \ReflectionClass($class);

        if (!$classReflector->isInternal()) {
            return $baseHydrator->bindTo(null, $class);
        }

        if ($classReflector->name !== $class) {
            return self::$hydrators[$classReflector->name] ??= self::getHydrator($classReflector->name);
        }

        $propertySetters = [];
        foreach ($classReflector->getProperties() as $propertyReflector) {
            if (!$propertyReflector->isStatic()) {
                $propertySetters[$propertyReflector->name] = $propertyReflector->setValue(...);
            }
        }

        if (!$propertySetters) {
            return $baseHydrator;
        }

        return static function ($properties, $objects) use ($propertySetters) {
            foreach ($properties as $name => $values) {
                if ($setValue = $propertySetters[$name] ?? null) {
                    foreach ($values as $i => $v) {
                        $setValue($objects[$i], $v);
                    }
                    continue;
                }
                foreach ($values as $i => $v) {
                    $objects[$i]->$name = $v;
                }
            }
        };
    }

    private static function getSimpleHydrator(string $class, int $flags = 0): \Closure
    {
        $callHooks = (bool) ($flags & \DEEPCLONE_HYDRATE_CALL_HOOKS);
        $noLazyInit = \PHP_VERSION_ID >= 80400 && ($flags & \DEEPCLONE_HYDRATE_NO_LAZY_INIT);
        $baseHydrator = self::$simpleHydrators['stdClass'] ??= static function ($properties, $object, $hasRefs) {
            if ($hasRefs) {
                foreach ($properties as $name => &$value) {
                    $object->$name = $value;
                    $object->$name = &$value;
                }
            } else {
                foreach ($properties as $name => $value) {
                    $object->$name = $value;
                }
            }
        };

        if ('stdClass' === $class) {
            return $baseHydrator;
        }

        if ('TypeError' === $class) {
            $class = 'Error';
        } elseif ('ErrorException' === $class) {
            $class = 'Exception';
        } elseif (!class_exists($class, false)) {
            throw new \DeepClone\ClassNotFoundException('Class "'.$class.'" not found.');
        }
        $classReflector = self::$reflectors[$class] ?? new \ReflectionClass($class);

        if ($classReflector->isInternal()) {
            if ($classReflector->name !== $class) {
                return self::$simpleHydrators[$classReflector->name] ??= self::getSimpleHydrator($classReflector->name);
            }

            $propertySetters = [];
            foreach ($classReflector->getProperties() as $propertyReflector) {
                if (!$propertyReflector->isStatic()) {
                    $propertySetters[$propertyReflector->name] = $propertyReflector->setValue(...);
                }
            }

            if (!$propertySetters) {
                return $baseHydrator;
            }

            return static function ($properties, $object) use ($propertySetters) {
                foreach ($properties as $name => $value) {
                    if ($setValue = $propertySetters[$name] ?? null) {
                        $setValue($object, $value);
                        continue;
                    }
                    $object->$name = $value;
                }
            };
        }

        $notByRef = [];
        $unsetOnNull = [];
        $backedEnum = [];
        foreach ($classReflector->getProperties() as $propertyReflector) {
            if ($propertyReflector->isStatic()) {
                continue;
            }
            if ($noLazyInit && !$propertyReflector->isVirtual()) {
                $notByRef[$propertyReflector->name] = $propertyReflector->setRawValueWithoutLazyInitialization(...);
                continue;
            }
            $t = null;
            if (\PHP_VERSION_ID >= 80400 && !$propertyReflector->isAbstract() && $propertyReflector->getHooks()) {
                if ($propertyReflector->isVirtual()) {
                    $notByRef[$propertyReflector->name] = true;
                } else {
                    $notByRef[$propertyReflector->name] = $callHooks ? $propertyReflector->setValue(...) : $propertyReflector->setRawValue(...);
                }
            } elseif ($propertyReflector->isReadOnly()) {
                $notByRef[$propertyReflector->name] = static function ($object, $value) use ($propertyReflector) {
                    if (!$propertyReflector->isInitialized($object) || $propertyReflector->getValue($object) !== $value) {
                        $propertyReflector->setValue($object, $value);
                    }
                };
            } elseif (($t = $propertyReflector->getType()) && !$t->allowsNull()) {
                $unsetOnNull[$propertyReflector->name] = true;
            }

            // Property-type-only decision: hook presence and CALL_HOOKS don't influence it.
            if (($t ??= $propertyReflector->getType()) instanceof \ReflectionNamedType
                && !$t->isBuiltin()
                && enum_exists($enumName = $t->getName())
                && (new \ReflectionEnum($enumName))->getBackingType()
            ) {
                $backedEnum[$propertyReflector->name] = $enumName;
            }
        }

        // Four variants. When the class has no hooked/readonly/unsetOnNull/backedEnum
        // props, the hottest path skips the $notByRef table lookup entirely. Each
        // hasRefs branch is hoisted out of the loop so it's branch-predicted once.
        if (!$unsetOnNull && !$backedEnum) {
            if (!$notByRef) {
                return \Closure::bind(static function ($properties, $object, $hasRefs): void {
                    if ($hasRefs) {
                        foreach ($properties as $name => &$value) {
                            $object->$name = $value;
                            $object->$name = &$value;
                        }
                    } else {
                        foreach ($properties as $name => $value) {
                            $object->$name = $value;
                        }
                    }
                }, null, $class);
            }

            return \Closure::bind(static function ($properties, $object, $hasRefs) use ($notByRef): void {
                if ($hasRefs) {
                    foreach ($properties as $name => &$value) {
                        if (!$noRef = $notByRef[$name] ?? false) {
                            $object->$name = $value;
                            $object->$name = &$value;
                        } elseif (true !== $noRef) {
                            $noRef($object, $value);
                        } else {
                            $object->$name = $value;
                        }
                    }
                } else {
                    foreach ($properties as $name => $value) {
                        if (!$noRef = $notByRef[$name] ?? false) {
                            $object->$name = $value;
                        } elseif (true !== $noRef) {
                            $noRef($object, $value);
                        } else {
                            $object->$name = $value;
                        }
                    }
                }
            }, null, $class);
        }
        if (!$backedEnum) {
            return \Closure::bind(static function ($properties, $object, $hasRefs) use ($notByRef, $unsetOnNull): void {
                if ($hasRefs) {
                    foreach ($properties as $name => &$value) {
                        if (null === $value && isset($unsetOnNull[$name])) {
                            unset($object->$name);
                            continue;
                        }
                        if (!$noRef = $notByRef[$name] ?? false) {
                            $object->$name = $value;
                            $object->$name = &$value;
                        } elseif (true !== $noRef) {
                            $noRef($object, $value);
                        } else {
                            $object->$name = $value;
                        }
                    }
                } else {
                    foreach ($properties as $name => $value) {
                        if (null === $value && isset($unsetOnNull[$name])) {
                            unset($object->$name);
                            continue;
                        }
                        if (!$noRef = $notByRef[$name] ?? false) {
                            $object->$name = $value;
                        } elseif (true !== $noRef) {
                            $noRef($object, $value);
                        } else {
                            $object->$name = $value;
                        }
                    }
                }
            }, null, $class);
        }

        return \Closure::bind(static function ($properties, $object, $hasRefs) use ($notByRef, $unsetOnNull, $backedEnum): void {
            if ($hasRefs) {
                foreach ($properties as $name => &$value) {
                    if (null === $value && isset($unsetOnNull[$name])) {
                        unset($object->$name);
                        continue;
                    }
                    if (isset($backedEnum[$name]) && (\is_int($value) || \is_string($value))) {
                        $value = $backedEnum[$name]::from($value);
                    }
                    if (!$noRef = $notByRef[$name] ?? false) {
                        $object->$name = $value;
                        $object->$name = &$value;
                    } elseif (true !== $noRef) {
                        $noRef($object, $value);
                    } else {
                        $object->$name = $value;
                    }
                }
            } else {
                foreach ($properties as $name => $value) {
                    if (null === $value && isset($unsetOnNull[$name])) {
                        unset($object->$name);
                        continue;
                    }
                    if (isset($backedEnum[$name]) && (\is_int($value) || \is_string($value))) {
                        $value = $backedEnum[$name]::from($value);
                    }
                    if (!$noRef = $notByRef[$name] ?? false) {
                        $object->$name = $value;
                    } elseif (true !== $noRef) {
                        $noRef($object, $value);
                    } else {
                        $object->$name = $value;
                    }
                }
            }
        }, null, $class);
    }
}

/**
 * Internal marker for hard references discovered during the walk.
 *
 * @internal
 */
final class Reference
{
    public int $count = 0;

    public function __construct(
        public readonly int $id,
        public readonly mixed $value = null,
    ) {
    }
}
