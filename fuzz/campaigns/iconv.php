<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('iconv_mime_decode')) {
    throw new \RuntimeException('This campaign requires ext-iconv as the native oracle.');
}

$pairs = [
    ['UTF-8', 'UTF-8'],
    ['UTF-8', 'US-ASCII'],
    ['UTF-8', 'US-ASCII//IGNORE'],
    ['UTF-8', 'US-ASCII//TRANSLIT'],
    ['UTF-8', 'ISO-8859-1'],
    ['ISO-8859-1', 'UTF-8'],
    ['UTF-8', 'Windows-1252'],
    ['Windows-1252', 'UTF-8'],
];
$decodeConversion = static function (string $input) use ($pairs): array {
    $data = new FuzzDataProvider($input);

    return [$data->consumeChoice($pairs), $data->consumeRemainingBytes()];
};
$scenarios = [
    'iconv' => [
        static function (string $input) use ($decodeConversion) {
            [[$from, $to], $payload] = $decodeConversion($input);

            return iconv($from, $to, $payload);
        },
        static function (string $input) use ($decodeConversion) {
            [[$from, $to], $payload] = $decodeConversion($input);

            return \Symfony\Polyfill\Iconv\Iconv::iconv($from, $to, $payload);
        },
    ],
    'iconv_mime_decode' => [
        static fn (string $input) => iconv_mime_decode($input, \ICONV_MIME_DECODE_STRICT, 'UTF-8'),
        static fn (string $input) => \Symfony\Polyfill\Iconv\Iconv::iconv_mime_decode($input, \ICONV_MIME_DECODE_STRICT, 'UTF-8'),
    ],
];

$config->setMaxLen(257);
$config->addDictionary(\dirname(__DIR__).'/dictionary/conversion.dict');
$config->addDictionary(\dirname(__DIR__).'/dictionary/mime.dict');
polyfillFuzzConfigure(
    $config,
    static function (string $input) use ($scenarios) {
        $data = new FuzzDataProvider($input);
        $scenario = $data->consumeChoice(array_keys($scenarios));
        [$native] = $scenarios[$scenario];

        return $native($data->consumeRemainingBytes());
    },
    static function (string $input) use ($scenarios) {
        $data = new FuzzDataProvider($input);
        $scenario = $data->consumeChoice(array_keys($scenarios));
        [, $polyfill] = $scenarios[$scenario];

        return $polyfill($data->consumeRemainingBytes());
    },
    static function (string $nativeResult, string $polyfillResult): bool {
        $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
        $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

        return $polyfillReturned
            && false !== $polyfillValue
            && (!$nativeReturned || false === $nativeValue || serialize($nativeValue) !== serialize($polyfillValue));
    }
);
