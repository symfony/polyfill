<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Intl\MessageFormatter\MessageFormatter as p;

if (!function_exists('msgfmt_format_message')) {
    function msgfmt_format_message($locale, $pattern, array $args) { return p::formatMessage($locale, $pattern, $args); }
}
