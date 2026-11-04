<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('iconv_mime_decode_headers')) {
    throw new \RuntimeException('This campaign requires ext-iconv as the native oracle.');
}

$config->setMaxLen(256);
$config->addDictionary(\dirname(__DIR__).'/dictionary/mime.dict');

polyfillFuzzConfigure(
    $config,
    static fn (string $input) => iconv_mime_decode_headers($input, \ICONV_MIME_DECODE_STRICT, 'UTF-8'),
    static fn (string $input) => \Symfony\Polyfill\Iconv\Iconv::iconv_mime_decode_headers($input, \ICONV_MIME_DECODE_STRICT, 'UTF-8'),
    static function (string $nativeResult, string $polyfillResult): bool {
        $nativeReturned = polyfillFuzzObservationReturn($nativeResult, $nativeValue);
        $polyfillReturned = polyfillFuzzObservationReturn($polyfillResult, $polyfillValue);

        return $polyfillReturned
            && \is_array($polyfillValue)
            && (!$nativeReturned || !\is_array($nativeValue) || serialize($nativeValue) !== serialize($polyfillValue));
    }
);
