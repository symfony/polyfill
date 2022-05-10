<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (\PHP_VERSION_ID < 70200) {
    require __DIR__.'/Php72/bootstrap.php';
}

if (\PHP_VERSION_ID < 70300) {
    require __DIR__.'/Php73/bootstrap.php';
}

if (\PHP_VERSION_ID < 70400) {
    require __DIR__.'/Php74/bootstrap.php';
}

if (\PHP_VERSION_ID < 80000) {
    require __DIR__.'/Php80/bootstrap.php';
}

if (\PHP_VERSION_ID < 80100) {
    require __DIR__.'/Php81/bootstrap.php';
}

if (\PHP_VERSION_ID < 80200) {
    require __DIR__.'/Php82/bootstrap.php';
}
