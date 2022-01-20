<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Util;

if (version_compare(\PHPUnit\Runner\Version::id(), '9.1.0', '<')) {
    class_alias('Symfony\Polyfill\Util\TestListenerForV7', 'Symfony\Polyfill\Util\TestListener');
} else {
    class_alias('Symfony\Polyfill\Util\TestListenerForV9', 'Symfony\Polyfill\Util\TestListener');
}

if (false) {
    class TestListener
    {
    }
}
