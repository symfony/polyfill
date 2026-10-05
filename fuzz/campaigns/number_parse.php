<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\class_exists('NumberFormatter')) {
    throw new \RuntimeException('This campaign requires ext-intl as the native oracle.');
}

$config->setMaxLen(64);
$config->addDictionary(\dirname(__DIR__).'/dictionary/number.dict');

polyfillFuzzConfigure(
    $config,
    static function (string $input) {
        $formatter = new \NumberFormatter('en', \NumberFormatter::DECIMAL);

        return $formatter->parse(polyfillFuzzDecodeHexSeed($input), \NumberFormatter::TYPE_DOUBLE);
    },
    static function (string $input) {
        $formatter = new class('en', 1) extends \Symfony\Polyfill\Intl\Icu\NumberFormatter {};

        return $formatter->parse(polyfillFuzzDecodeHexSeed($input), 3);
    },
    static function (string $nativeResult, string $polyfillResult): bool {
        $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
        $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);
        $nativeAccepted = $nativeReturned && false !== $nativeValue;
        $polyfillAccepted = $polyfillReturned && false !== $polyfillValue;

        return $polyfillAccepted && (!$nativeAccepted || serialize($nativeValue) !== serialize($polyfillValue));
    }
);
