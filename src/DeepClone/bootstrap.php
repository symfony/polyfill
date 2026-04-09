<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\DeepClone as p;

if (extension_loaded('deepclone')) {
    return;
}

if (!function_exists('deepclone_to_array')) {
    function deepclone_to_array(mixed $value, ?array $allowedClasses = null): array { return p\DeepClone::deepclone_to_array($value, $allowedClasses); }
}
if (!function_exists('deepclone_from_array')) {
    function deepclone_from_array(array $data, ?array $allowedClasses = null): mixed { return p\DeepClone::deepclone_from_array($data, $allowedClasses); }
}
