<?php

namespace Random\Engine;

use \Symfony\Polyfill\Php82 as p;

if (\PHP_VERSION_ID < 80200) {
    final class Secure extends p\Random\Engine\Secure implements \Random\CryptoSafeEngine
    {
    }
}
