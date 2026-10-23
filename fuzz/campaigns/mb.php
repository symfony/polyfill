<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('mb_check_encoding')) {
    throw new \RuntimeException('This campaign requires ext-mbstring as the native oracle.');
}

$defaultDifferential = static function (string $nativeResult, string $polyfillResult): bool {
    return polyfillFuzzReturnedValueDiffers($nativeResult, $polyfillResult);
};
$checkEncodingInput = static function (string $input) {
    $data = new FuzzDataProvider($input);
    $asArray = $data->consumeBool();
    $payload = $data->consumeRemainingBytes();
    if ($asArray) {
        [$key, $value] = array_pad(explode("\0", $payload, 2), 2, '');

        return [$key => $value];
    }

    return $payload;
};
$conversionInput = static function (string $input): array {
    $pairs = [
        ['UTF-8', 'UTF-8'],
        ['UTF-8', 'UTF-16BE'],
        ['UTF-16BE', 'UTF-8'],
        ['UTF-8', 'ISO-8859-1'],
        ['ISO-8859-1', 'UTF-8'],
        ['UTF-8', 'Windows-1252'],
        ['Windows-1252', 'UTF-8'],
        ['HTML-ENTITIES', 'UTF-8'],
        ['UTF-8', 'HTML-ENTITIES'],
        ['BASE64', 'UTF-8'],
        ['UTF-8', 'BASE64'],
    ];
    $data = new FuzzDataProvider($input);

    return [$data->consumeChoice($pairs), $data->consumeRemainingBytes()];
};
$checkEncodingDifferential = static function (string $nativeResult, string $polyfillResult): bool {
    $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
    $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

    return $polyfillReturned && true === $polyfillValue && (!$nativeReturned || true !== $nativeValue);
};
$scrubDifferential = static function (string $nativeResult, string $polyfillResult): bool {
    if (!polyfillFuzzObservationReturn($nativeResult, $nativeValue) || !polyfillFuzzObservationReturn($polyfillResult, $polyfillValue)) {
        return false;
    }

    return \is_string($nativeValue)
        && \is_string($polyfillValue)
        && false !== strpos($nativeValue, '?')
        && false === strpos($polyfillValue, '?')
        && \strlen($polyfillValue) < \strlen($nativeValue)
        && 1 === preg_match('/^[\x20-\x7E]{2,}$/D', $polyfillValue);
};

$scenarios = [
    'mb_check_encoding' => [
        static fn (string $input): bool => mb_check_encoding($checkEncodingInput($input), 'UTF-8'),
        static fn (string $input): bool => \Symfony\Polyfill\Mbstring\Mbstring::mb_check_encoding($checkEncodingInput($input), 'UTF-8'),
        $checkEncodingDifferential,
    ],
    'mb_convert_encoding' => [
        static function (string $input) use ($conversionInput) {
            [[$to, $from], $payload] = $conversionInput($input);

            return mb_convert_encoding($payload, $to, $from);
        },
        static function (string $input) use ($conversionInput) {
            [[$to, $from], $payload] = $conversionInput($input);

            return \Symfony\Polyfill\Mbstring\Mbstring::mb_convert_encoding($payload, $to, $from);
        },
        $defaultDifferential,
    ],
    'mb_decode_mimeheader' => [
        static fn (string $input): string => mb_decode_mimeheader($input),
        static fn (string $input): string => \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_mimeheader($input),
        $defaultDifferential,
    ],
    'mb_ord' => [
        static fn (string $input) => mb_ord($input, 'UTF-8'),
        static fn (string $input) => \Symfony\Polyfill\Mbstring\Mbstring::mb_ord($input, 'UTF-8'),
        $defaultDifferential,
    ],
    'mb_parse_str' => [
        static function (string $input): array {
            $result = [];
            mb_parse_str($input, $result);

            return $result;
        },
        static function (string $input): array {
            $result = [];
            parse_str($input, $result);

            return $result;
        },
        $defaultDifferential,
    ],
    'mb_scrub' => [
        static fn (string $input): string => mb_scrub($input, 'UTF-8'),
        static fn (string $input): string => \Symfony\Polyfill\Mbstring\Mbstring::mb_scrub($input, 'UTF-8'),
        $scrubDifferential,
    ],
];

$config->setMaxLen(258);
$config->addDictionary(\dirname(__DIR__).'/dictionary/conversion.dict');
$config->addDictionary(\dirname(__DIR__).'/dictionary/mime.dict');
$config->addDictionary(\dirname(__DIR__).'/dictionary/query.dict');
$config->addDictionary(\dirname(__DIR__).'/dictionary/utf8.dict');
$config->setTarget(static function (string $input) use ($scenarios): void {
    $data = new FuzzDataProvider(polyfillFuzzDecodeHexSeed($input));
    $scenario = $data->consumeChoice(array_keys($scenarios));
    [$native, $polyfill, $isDifferential] = $scenarios[$scenario];
    $payload = $data->consumeRemainingBytes();

    $nativeResult = polyfillFuzzObserve(static fn () => $native($payload));
    $polyfillResult = polyfillFuzzObserve(static fn () => $polyfill($payload));

    if ($isDifferential($nativeResult, $polyfillResult)) {
        throw new \Error("Native/polyfill differential in $scenario");
    }
});
