<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\DeepClone as p;

if (extension_loaded('deepclone')) {
    return;
}

if (!function_exists('deepclone_to_array')) {
    function deepclone_to_array(mixed $value, ?array $allowed_classes = null): array { return p\DeepClone::deepclone_to_array($value, $allowed_classes); }
}
if (!function_exists('deepclone_from_array')) {
    function deepclone_from_array(array $data, ?array $allowed_classes = null): mixed { return p\DeepClone::deepclone_from_array($data, $allowed_classes); }
}
if (!function_exists('deepclone_hydrate')) {
    function deepclone_hydrate(object|string $object_or_class, array $scoped_vars = [], array $mangled_vars = []): object { return p\DeepClone::deepclone_hydrate($object_or_class, $scoped_vars, $mangled_vars); }
}
