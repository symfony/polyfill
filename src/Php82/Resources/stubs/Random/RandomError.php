<?php

namespace Random;

if (\PHP_VERSION_ID < 80200) {
    class RandomError extends \Error
    {
    }
}
