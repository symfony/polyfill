<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Uuid as p;

if (!defined('UUID_TYPE_DEFAULT')) {
    define('UUID_VARIANT_NCS', 0);
    define('UUID_VARIANT_DCE', 1);
    define('UUID_VARIANT_MICROSOFT', 2);
    define('UUID_VARIANT_OTHER', 3);
    define('UUID_TYPE_DEFAULT', 0);
    define('UUID_TYPE_TIME', 1);
    define('UUID_TYPE_MD5', 3);
    define('UUID_TYPE_DCE', 4); // Deprecated alias
    define('UUID_TYPE_NAME', 1); // Deprecated alias
    define('UUID_TYPE_RANDOM', 4);
    define('UUID_TYPE_SHA1', 5);
    define('UUID_TYPE_NULL', -1);
    define('UUID_TYPE_INVALID', -42);
}

if (!function_exists('uuid_create')) {
    function uuid_create($type = UUID_TYPE_DEFAULT) { return p\Uuid::uuid_create($type); }
    function uuid_generate_md5($uuid_ns, $name) { return p\Uuid::uuid_generate_md5($uuid_ns, $name); }
    function uuid_generate_sha1($uuid_ns, $name) { return p\Uuid::uuid_generate_sha1($uuid_ns, $name); }
    function uuid_is_valid($uuid) { return p\Uuid::uuid_is_valid($uuid); }
    function uuid_compare($uuid1, $uuid2) { return p\Uuid::uuid_compare($uuid1, $uuid2); }
    function uuid_is_null($uuid) { return p\Uuid::uuid_is_null($uuid); }
    function uuid_type($uuid) { return p\Uuid::uuid_type($uuid); }
    function uuid_variant($uuid) { return p\Uuid::uuid_variant($uuid); }
    function uuid_time($uuid) { return p\Uuid::uuid_time($uuid); }
    function uuid_mac($uuid) { return p\Uuid::uuid_mac($uuid); }
    function uuid_parse($uuid) { return p\Uuid::uuid_parse($uuid); }
    function uuid_unparse($uuid) { return p\Uuid::uuid_unparse($uuid); }
}
