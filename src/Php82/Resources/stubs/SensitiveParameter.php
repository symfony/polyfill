<?php

if (\PHP_VERSION_ID < 80200) {
    #[Attribute(Attribute::TARGET_PARAMETER)]
    final class SensitiveParameter
    {
        public function __construct()
        {
        }
    }
}
