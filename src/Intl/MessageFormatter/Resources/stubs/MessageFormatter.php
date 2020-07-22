<?php

if (!class_exists('MessageFormatter')) {
    class MessageFormatter extends Symfony\Polyfill\Intl\MessageFormatter\MessageFormatter
    {
    }
}
