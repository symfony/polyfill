<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('uuid_is_valid')) {
    throw new \RuntimeException('This campaign requires ext-uuid as the native oracle.');
}

$validateParse = static function (string $input, callable $isValid, callable $parse, callable $unparse, callable $type, callable $variant): array {
    if (!$isValid($input)) {
        return [false];
    }

    $bytes = $parse($input);

    return [true, bin2hex($bytes), $unparse($bytes), $type($input), $variant($input)];
};
$split = static function (string $input): array {
    $data = new FuzzDataProvider($input);

    return [$data->consumeBytes(64), $data->consumeRemainingBytes()];
};
$validateDifferential = static function (string $nativeResult, string $polyfillResult): bool {
    $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
    $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

    return $polyfillReturned
        && true === ($polyfillValue[0] ?? false)
        && (!$nativeReturned || serialize($nativeValue) !== serialize($polyfillValue));
};
$acceptedDifferential = static function (string $nativeResult, string $polyfillResult): bool {
    $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
    $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);
    if (!$polyfillReturned) {
        return false;
    }

    return $nativeReturned
        ? serialize($nativeValue) !== serialize($polyfillValue)
        : false !== $polyfillValue;
};
$scenarios = [
    'uuid_validate_parse' => [
        static fn (string $input): array => $validateParse($input, 'uuid_is_valid', 'uuid_parse', 'uuid_unparse', 'uuid_type', 'uuid_variant'),
        static fn (string $input): array => $validateParse(
            $input,
            [\Symfony\Polyfill\Uuid\Uuid::class, 'uuid_is_valid'],
            [\Symfony\Polyfill\Uuid\Uuid::class, 'uuid_parse'],
            [\Symfony\Polyfill\Uuid\Uuid::class, 'uuid_unparse'],
            [\Symfony\Polyfill\Uuid\Uuid::class, 'uuid_type'],
            [\Symfony\Polyfill\Uuid\Uuid::class, 'uuid_variant']
        ),
        $validateDifferential,
    ],
    'uuid_unparse' => [
        static fn (string $input) => uuid_unparse($input),
        static fn (string $input) => \Symfony\Polyfill\Uuid\Uuid::uuid_unparse($input),
        $acceptedDifferential,
    ],
    'uuid_compare' => [
        static function (string $input) use ($split) {
            [$first, $second] = $split($input);

            return uuid_compare($first, $second);
        },
        static function (string $input) use ($split) {
            [$first, $second] = $split($input);

            return \Symfony\Polyfill\Uuid\Uuid::uuid_compare($first, $second);
        },
        $acceptedDifferential,
    ],
    'uuid_is_null' => [
        static fn (string $input) => uuid_is_null($input),
        static fn (string $input) => \Symfony\Polyfill\Uuid\Uuid::uuid_is_null($input),
        $acceptedDifferential,
    ],
    'uuid_time' => [
        static fn (string $input) => uuid_time($input),
        static fn (string $input) => \Symfony\Polyfill\Uuid\Uuid::uuid_time($input),
        $acceptedDifferential,
    ],
    'uuid_mac' => [
        static fn (string $input) => uuid_mac($input),
        static fn (string $input) => \Symfony\Polyfill\Uuid\Uuid::uuid_mac($input),
        $acceptedDifferential,
    ],
    'uuid_generate_md5' => [
        static function (string $input) use ($split) {
            [$namespace, $name] = $split($input);

            return uuid_generate_md5($namespace, $name);
        },
        static function (string $input) use ($split) {
            [$namespace, $name] = $split($input);

            return \Symfony\Polyfill\Uuid\Uuid::uuid_generate_md5($namespace, $name);
        },
        $acceptedDifferential,
    ],
    'uuid_generate_sha1' => [
        static function (string $input) use ($split) {
            [$namespace, $name] = $split($input);

            return uuid_generate_sha1($namespace, $name);
        },
        static function (string $input) use ($split) {
            [$namespace, $name] = $split($input);

            return \Symfony\Polyfill\Uuid\Uuid::uuid_generate_sha1($namespace, $name);
        },
        $acceptedDifferential,
    ],
];

$config->setMaxLen(258);
$config->addDictionary(\dirname(__DIR__).'/dictionary/uuid.dict');
$config->setTarget(static function (string $input) use ($scenarios): void {
    $data = new FuzzDataProvider($input);
    $scenario = $data->consumeChoice(array_keys($scenarios));
    [$native, $polyfill, $isDifferential] = $scenarios[$scenario];
    $payload = $data->consumeRemainingBytes();

    $nativeResult = polyfillFuzzObserve(static fn () => $native($payload));
    $polyfillResult = polyfillFuzzObserve(static fn () => $polyfill($payload));

    if ($isDifferential($nativeResult, $polyfillResult)) {
        throw new \Error("Native/polyfill differential in $scenario");
    }
});
