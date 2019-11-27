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

if (!function_exists('uuid_create')) {
    define(UUID_VARIANT_NCS, p\Uuid::UUID_VARIANT_NCS);
    define(UUID_VARIANT_DCE, p\Uuid::UUID_VARIANT_DCE);
    define(UUID_VARIANT_MICROSOFT, p\Uuid::UUID_VARIANT_MICROSOFT);
    define(UUID_VARIANT_OTHER, p\Uuid::UUID_VARIANT_OTHER);
    define(UUID_TYPE_DEFAULT, p\Uuid::UUID_TYPE_DEFAULT);
    define(UUID_TYPE_TIME, p\Uuid::UUID_TYPE_TIME);
    define(UUID_TYPE_DCE, p\Uuid::UUID_TYPE_DCE);
    define(UUID_TYPE_NAME, p\Uuid::UUID_TYPE_NAME);
    define(UUID_TYPE_RANDOM, p\Uuid::UUID_TYPE_RANDOM);
    define(UUID_TYPE_NULL, p\Uuid::UUID_TYPE_NULL);
    define(UUID_TYPE_INVALID, p\Uuid::UUID_TYPE_INVALID);

    function uuid_create($type = UUID_TYPE_DEFAULT) { return p\Uuid::uuid_create($type); }
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
