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
 * Apcu for Zend Server Data Cache.
 *
 * @author Kate Gray <opensource@codebykate.com>
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class Apcu
{
    public static function apcu_add($key, $var = null, $ttl = 0)
    {
        if (!\is_array($key)) {
            return apc_add($key, $var, $ttl);
        }

        $errors = [];
        foreach ($key as $k => $v) {
            if (!apc_add($k, $v, $ttl)) {
                $errors[$k] = -1;
            }
        }

        return $errors;
    }

    public static function apcu_store($key, $var = null, $ttl = 0)
    {
        if (!\is_array($key)) {
            return apc_store($key, $var, $ttl);
        }

        $errors = [];
        foreach ($key as $k => $v) {
            if (!apc_store($k, $v, $ttl)) {
                $errors[$k] = -1;
            }
        }

        return $errors;
    }

    public static function apcu_exists($keys)
    {
        if (!\is_array($keys)) {
            return apc_exists($keys);
        }

        $existing = [];
        foreach ($keys as $k) {
            if (apc_exists($k)) {
                $existing[$k] = true;
            }
        }

        return $existing;
    }

    public static function apcu_fetch($key, &$success = null)
    {
        if (!\is_array($key)) {
            return apc_fetch($key, $success);
        }

        $succeeded = true;
        $values = [];
        foreach ($key as $k) {
            $v = apc_fetch($k, $success);
            if ($success) {
                $values[$k] = $v;
            } else {
                $succeeded = false;
            }
        }
        $success = $succeeded;

        return $values;
    }

    public static function apcu_delete($key)
    {
        if (!\is_array($key)) {
            return apc_delete($key);
        }

        $success = true;
        foreach ($key as $k) {
            $success = apc_delete($k) && $success;
        }

        return $success;
    }
}
