<?php

namespace Random;

if (\PHP_VERSION_ID < 80200) {
    interface Engine
    {
        public function generate(): string;
    }
}
