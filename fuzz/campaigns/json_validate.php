<?php

/** @var \PhpFuzzer\Config $config */

require_once \dirname(__DIR__).'/_bootstrap.php';

if (!\function_exists('json_validate')) {
    throw new \RuntimeException('This campaign requires PHP 8.3+ as the native oracle.');
}

$config->setMaxLen(2048);
$config->addDictionary(\dirname(__DIR__).'/dictionary/json.dict');

polyfillFuzzConfigure(
    $config,
    static fn (string $input): array => [json_validate($input, 32), json_last_error()],
    static fn (string $input): array => [\Symfony\Polyfill\Php83\Php83::json_validate($input, 32), json_last_error()]
);
