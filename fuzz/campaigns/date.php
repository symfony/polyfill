<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\class_exists('IntlDateFormatter')) {
    throw new \RuntimeException('This campaign requires ext-intl as the native oracle.');
}

$patterns = [
    'yyyy-MM-dd HH:mm:ss',
    'yyyy-MM-dd\'T\'HH:mm:ss',
    'yyyyMMddHHmmss',
    'MM/dd/yyyy hh:mm:ss a',
    'yyyy-DDD HH:mm:ss',
    'yyyy-MM-dd HH:mm:ss z',
];
$decode = static function (string $input) use ($patterns): array {
    $data = new FuzzDataProvider($input);
    $scenario = $data->consumeChoice(['date_parse', 'date_parse_patterns']);
    $pattern = 'date_parse' === $scenario ? $patterns[0] : $data->consumeChoice($patterns);
    $payload = $data->consumeRemainingBytes();

    return [$pattern, 'date_parse' === $scenario ? polyfillFuzzDecodeHexSeed($payload) : $payload];
};

$config->setMaxLen(97);
$config->addDictionary(\dirname(__DIR__).'/dictionary/date.dict');
$config->addDictionary(\dirname(__DIR__).'/dictionary/date-patterns.dict');
polyfillFuzzConfigure(
    $config,
    static function (string $input) use ($decode) {
        [$pattern, $payload] = $decode($input);
        $formatter = new \IntlDateFormatter('en', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::GREGORIAN, $pattern);
        $formatter->setLenient(false);

        return $formatter->parse($payload);
    },
    static function (string $input) use ($decode) {
        [$pattern, $payload] = $decode($input);
        $formatter = new class('en', -1, -1, 'UTC', 1, $pattern) extends \Symfony\Polyfill\Intl\Icu\IntlDateFormatter {};
        $formatter->setLenient(false);

        return $formatter->parse($payload);
    },
    static function (string $nativeResult, string $polyfillResult): bool {
        $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
        $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

        return $polyfillReturned && false !== $polyfillValue && (!$nativeReturned || false === $nativeValue);
    }
);
