<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Apcu as p;

if (!extension_loaded('apc') && !extension_loaded('apcu')) {
    return;
}

if (\PHP_VERSION_ID >= 80000) {
    return require __DIR__.'/bootstrap80.php';
}

if (extension_loaded('Zend Data Cache')) {
    if (!function_exists('apcu_add')) {
        function apcu_add($key, $value = null, $ttl = 0) { return p\Apcu::apcu_add($key, $value, $ttl); }
    }
    if (!function_exists('apcu_delete')) {
        function apcu_delete($key) { return p\Apcu::apcu_delete($key); }
    }
    if (!function_exists('apcu_exists')) {
        function apcu_exists($key) { return p\Apcu::apcu_exists($key); }
    }
    if (!function_exists('apcu_fetch')) {
        function apcu_fetch($key, &$success = null) { return p\Apcu::apcu_fetch($key, $success); }
    }
    if (!function_exists('apcu_store')) {
        function apcu_store($key, $value = null, $ttl = 0) { return p\Apcu::apcu_store($key, $value, $ttl); }
    }
} else {
    if (!function_exists('apcu_add')) {
        function apcu_add($key, $value = null, $ttl = 0) { return apc_add($key, $value, $ttl); }
    }
    if (!function_exists('apcu_delete')) {
        function apcu_delete($key) { return apc_delete($key); }
    }
    if (!function_exists('apcu_exists')) {
        function apcu_exists($key) { return apc_exists($key); }
    }
    if (!function_exists('apcu_fetch')) {
        function apcu_fetch($key, &$success = null) { return apc_fetch($key, $success); }
    }
    if (!function_exists('apcu_store')) {
        function apcu_store($key, $value = null, $ttl = 0) { return apc_store($key, $value, $ttl); }
    }
}

if (!function_exists('apcu_cache_info')) {
    function apcu_cache_info($limited = false) { return apc_cache_info('user', $limited); }
}
if (!function_exists('apcu_cas')) {
    function apcu_cas($key, $old, $new) { return apc_cas($key, $old, $new); }
}
if (!function_exists('apcu_clear_cache')) {
    function apcu_clear_cache() { return apc_clear_cache('user'); }
}
if (!function_exists('apcu_dec')) {
    function apcu_dec($key, $step = 1, &$success = false) { return apc_dec($key, $step, $success); }
}
if (!function_exists('apcu_inc')) {
    function apcu_inc($key, $step = 1, &$success = false) { return apc_inc($key, $step, $success); }
}
if (!function_exists('apcu_sma_info')) {
    function apcu_sma_info($limited = false) { return apc_sma_info($limited); }
}

if (!class_exists('APCuIterator', false) && class_exists('APCIterator', false)) {
    class APCuIterator extends APCIterator
    {
        public function __construct($search = null, $format = \APC_ITER_ALL, $chunk_size = 100, $list = \APC_LIST_ACTIVE)
        {
            parent::__construct('user', $search, $format, $chunk_size, $list);
        }
    }
}
