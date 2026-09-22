<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('grapheme_extract')) {
    throw new \RuntimeException('This campaign requires ext-intl as the native oracle.');
}

$boundaries = static function (string $input, callable $strlen, callable $substr, callable $extract) {
    $data = new FuzzDataProvider($input);
    $selector = $data->consumeInt(0, 2);
    $start = $data->consumeInt(-3, 3);
    $length = $data->consumeInt(0, 4);
    $size = $data->consumeInt(1, 4);
    $type = $data->consumeChoice([\GRAPHEME_EXTR_COUNT, \GRAPHEME_EXTR_MAXBYTES, \GRAPHEME_EXTR_MAXCHARS]);
    $payload = $data->consumeRemainingBytes();
    if (1 !== preg_match('//u', $payload)) {
        return false;
    }

    switch ($selector) {
        case 0:
            return $strlen($payload);
        case 1:
            return $substr($payload, $start, $length);
        default:
            $next = null;
            $result = $extract($payload, $size, $type, 0, $next);

            return [$result, $next];
    }
};
$striposInput = static function (string $input): array {
    return array_pad(explode("\0", polyfillFuzzDecodeHexSeed($input), 2), 2, '');
};
$defaultDifferential = static fn (string $nativeResult, string $polyfillResult): bool => polyfillFuzzReturnedValueDiffers($nativeResult, $polyfillResult);
$striposDifferential = static function (string $nativeResult, string $polyfillResult): bool {
    $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
    $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

    return $nativeReturned && $polyfillReturned && false !== $nativeValue && $nativeValue !== $polyfillValue;
};
$scenarios = [
    'grapheme_stripos' => [
        static function (string $input) use ($striposInput) {
            [$haystack, $needle] = $striposInput($input);

            return '' === $needle || !ctype_alnum($needle) ? false : grapheme_stripos($haystack, $needle);
        },
        static function (string $input) use ($striposInput) {
            [$haystack, $needle] = $striposInput($input);

            return '' === $needle || !ctype_alnum($needle) ? false : \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_stripos($haystack, $needle);
        },
        $striposDifferential,
    ],
    'grapheme_boundaries' => [
        static fn (string $input) => $boundaries($input, 'grapheme_strlen', 'grapheme_substr', 'grapheme_extract'),
        static fn (string $input) => $boundaries(
            $input,
            [\Symfony\Polyfill\Intl\Grapheme\Grapheme::class, 'grapheme_strlen'],
            [\Symfony\Polyfill\Intl\Grapheme\Grapheme::class, 'grapheme_substr'],
            [\Symfony\Polyfill\Intl\Grapheme\Grapheme::class, 'grapheme_extract']
        ),
        $defaultDifferential,
    ],
];

$config->setMaxLen(129);
$config->addDictionary(\dirname(__DIR__).'/dictionary/grapheme.dict');
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
