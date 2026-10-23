<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\class_exists('Normalizer')) {
    throw new \RuntimeException('This campaign requires ext-intl as the native oracle.');
}

$config->setMaxLen(64);
$config->addDictionary(\dirname(__DIR__).'/dictionary/unicode.dict');
$forms = [\Normalizer::NFC, \Normalizer::NFD, \Normalizer::NFKC, \Normalizer::NFKD];
$decode = static function (string $input) use ($forms): array {
    $data = new FuzzDataProvider($input);

    return [$data->consumeChoice($forms), $data->consumeRemainingBytes()];
};

polyfillFuzzConfigure(
    $config,
    static function (string $input) use ($decode): array {
        [$form, $payload] = $decode($input);

        return [\Normalizer::isNormalized($payload, $form), \Normalizer::normalize($payload, $form)];
    },
    static function (string $input) use ($decode): array {
        [$form, $payload] = $decode($input);

        return [\Symfony\Polyfill\Intl\Normalizer\Normalizer::isNormalized($payload, $form), \Symfony\Polyfill\Intl\Normalizer\Normalizer::normalize($payload, $form)];
    },
    static function (string $nativeResult, string $polyfillResult): bool {
        if (!polyfillFuzzObservationReturn($nativeResult, $nativeValue) || !polyfillFuzzObservationReturn($polyfillResult, $polyfillValue)) {
            return false;
        }

        if (true === ($polyfillValue[0] ?? false) && true !== ($nativeValue[0] ?? false)) {
            return true;
        }

        $nativeNormalized = $nativeValue[1] ?? false;
        $polyfillNormalized = $polyfillValue[1] ?? false;

        return \is_string($nativeNormalized)
            && \is_string($polyfillNormalized)
            && $nativeNormalized !== $polyfillNormalized
            && '' !== $nativeNormalized
            && 1 === preg_match('/^[A-Za-z0-9_.:\/-]+$/D', $nativeNormalized);
    }
);
