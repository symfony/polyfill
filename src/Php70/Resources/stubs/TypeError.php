<?php

if (PHP_VERSION_ID < 70000) {
    class TypeError extends Error
    {
    }
}
