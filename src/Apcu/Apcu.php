<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Apcu;

/**
 * @internal
 */
final class Apcu
{
    public static function apcu_entry($key, $generator, $ttl = 0)
    {
        if (!is_scalar($key)) {
            throw new \InvalidArgumentException(sprintf('apcu_entry() expects parameter 1 to be string, %s given', __FUNCTION__, gettype($value)));
        }

        $entry = apcu_fetch($key, $success);

        if ($success) {
            return $entry;
        }

        $h = fopen(__FILE__, 'r');
        flock($h, LOCK_EX);
        try {
            $entry = apcu_fetch($key, $success);

            if (!$success) {
                $entry = call_user_func($generator, $key);
                apc_store($key, $entry, $ttl);
            }
        } catch (\Exception $e) {
        }
        flock($h, LOCK_UN);

        if (isset($e)) {
            throw $e;
        }

        return $entry;
    }
}
