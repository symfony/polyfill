<?php

if (\PHP_VERSION_ID < 80200) {
    #[Attribute(Attribute::TARGET_CLASS)]
    final class AllowDynamicProperties
    {
        public function __construct()
        {
        }
    }
}
