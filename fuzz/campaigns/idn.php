<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('idn_to_ascii') || !\function_exists('idn_to_utf8')) {
    throw new \RuntimeException('This campaign requires ext-intl as the native oracle.');
}

$config->setMaxLen(256);
$config->addDictionary(\dirname(__DIR__).'/dictionary/idna.dict');

$decode = static function (string $input): array {
    $data = new FuzzDataProvider($input);

    return [
        $data->consumeChoice(['idn_to_ascii', 'idn_to_utf8']),
        polyfillFuzzDecodeHexSeed($data->consumeRemainingBytes()),
    ];
};
$operation = static function (array $case, callable $toAscii, callable $toUtf8) {
    [$scenario, $domain] = $case;
    $info = [];

    if ('idn_to_ascii' === $scenario) {
        $options = \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ | \IDNA_USE_STD3_RULES | \IDNA_NONTRANSITIONAL_TO_ASCII;

        return $toAscii($domain, $options, \INTL_IDNA_VARIANT_UTS46, $info);
    }

    $options = \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ | \IDNA_USE_STD3_RULES | \IDNA_NONTRANSITIONAL_TO_UNICODE;

    return $toUtf8($domain, $options, \INTL_IDNA_VARIANT_UTS46, $info);
};

polyfillFuzzConfigure(
    $config,
    static function (string $input) use ($decode, $operation) {
        return $operation($decode($input), 'idn_to_ascii', 'idn_to_utf8');
    },
    static function (string $input) use ($decode, $operation) {
        return $operation(
            $decode($input),
            [\Symfony\Polyfill\Intl\Idn\Idn::class, 'idn_to_ascii'],
            [\Symfony\Polyfill\Intl\Idn\Idn::class, 'idn_to_utf8']
        );
    },
    static function (string $nativeResult, string $polyfillResult): bool {
        $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
        $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);
        $nativeAccepted = $nativeReturned && false !== $nativeValue;
        $polyfillAccepted = $polyfillReturned && false !== $polyfillValue;

        return $polyfillAccepted && (!$nativeAccepted || $nativeValue !== $polyfillValue);
    }
);
