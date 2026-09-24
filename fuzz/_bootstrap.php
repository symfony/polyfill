<?php

require_once __DIR__.'/FuzzDataProvider.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Symfony\\Polyfill\\';
    if (0 !== strncmp($class, $prefix, \strlen($prefix))) {
        return;
    }

    $file = \dirname(__DIR__).'/src/'.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
});

function polyfillFuzzObserve(callable $call): string
{
    $warnings = [];
    set_error_handler(static function (int $type) use (&$warnings): bool {
        $warnings[] = $type;

        return true;
    });

    try {
        $outcome = ['return', $call()];
    } catch (\Throwable $e) {
        $outcome = ['throw', \get_class($e), $e->getCode()];
    } finally {
        restore_error_handler();
    }

    return serialize([$outcome, $warnings]);
}

function polyfillFuzzObservationReturn(string $observation, &$value): bool
{
    $decoded = unserialize($observation, ['allowed_classes' => false]);
    if ('return' !== $decoded[0][0]) {
        return false;
    }

    $value = $decoded[0][1];

    return true;
}

function polyfillFuzzReturnedValueDiffers(string $nativeResult, string $polyfillResult): bool
{
    $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
    $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

    return $nativeReturned !== $polyfillReturned
        || ($nativeReturned && serialize($nativeValue) !== serialize($polyfillValue));
}

function polyfillFuzzDecodeHexSeed(string $input): string
{
    if (0 !== strncmp($input, 'hex:', 4)) {
        return $input;
    }

    $hex = rtrim(substr($input, 4), "\r\n");

    return '' !== $hex && ctype_xdigit($hex) && 0 === \strlen($hex) % 2 ? hex2bin($hex) : $input;
}

function polyfillFuzzConfigure(\PhpFuzzer\Config $config, \Closure $native, \Closure $polyfill, ?\Closure $isDifferential = null): void
{
    if (null === $isDifferential) {
        $isDifferential = static function (string $nativeResult, string $polyfillResult): bool {
            return polyfillFuzzReturnedValueDiffers($nativeResult, $polyfillResult);
        };
    }

    $config->setTarget(static function (string $input) use ($native, $polyfill, $isDifferential): void {
        $nativeResult = polyfillFuzzObserve(static function () use ($native, $input) {
            return $native($input);
        });
        $polyfillResult = polyfillFuzzObserve(static function () use ($polyfill, $input) {
            return $polyfill($input);
        });

        if ($isDifferential($nativeResult, $polyfillResult)) {
            throw new \Error('Native/polyfill differential');
        }
    });
}
