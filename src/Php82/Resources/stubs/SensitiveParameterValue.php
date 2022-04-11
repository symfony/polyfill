<?php

if (\PHP_VERSION_ID < 80200) {
    final class SensitiveParameterValue extends Symfony\Polyfill\Php82\SensitiveParameterValue
    {
    }
}
