Symfony Polyfill
================

This project backports features found in the latest PHP versions and provides
compatibility layers for some extensions and functions. It is intended to be
used when portability across PHP versions and extensions is desired.

Polyfills are provided for:
- the `apcu` extension when the legacy `apc` extension is installed;
- the `ctype` extension when PHP is compiled without ctype;
- the `mbstring` and `iconv` extensions;
- the `uuid` extension;
- the `MessageFormatter` class and the `msgfmt_format_message` functions;
- the `Normalizer` class and the `grapheme_*` functions;
- the `utf8_encode` and `utf8_decode` functions from the `xml` extension or PHP-7.2 core;
- the `Collator`, `NumberFormatter`, `Locale` and `IntlDateFormatter` classes;
- the `intl_error_name`, `intl_get_error_code`, `intl_get_error_message` and
  `intl_is_failure` functions;
- the `idn_to_ascii` and `idn_to_utf8` functions;
- the `hex2bin` function, the `CallbackFilterIterator`,
  `RecursiveCallbackFilterIterator` and `SessionHandlerInterface` classes
  introduced in PHP 5.4;
- the `array_column`, `boolval`, `json_last_error_msg` and `hash_pbkdf2`
  functions introduced in PHP 5.5;
- the `password_hash` and `password_*` related functions introduced in PHP 5.5,
  provided by the `ircmaxell/password-compat` package;
- the `hash_equals` and `ldap_escape` functions introduced in PHP 5.6;
- the `*Error` classes, the `error_clear_last`, `preg_replace_callback_array` and
  `intdiv` functions introduced in PHP 7.0;
- the `random_bytes` and `random_int` functions introduced in PHP 7.0,
  provided by the `paragonie/random_compat` package;
- the `PHP_INT_MIN` constant introduced in PHP 7.0,
- the `SessionUpdateTimestampHandlerInterface` interface introduced in PHP 7.0,
- the `is_iterable` function introduced in PHP 7.1;
- a `Binary` utility class to be used when compatibility with
  `mbstring.func_overload` is required;
- the `spl_object_id` and `stream_isatty` functions introduced in PHP 7.2;
- the `sapi_windows_vt100_support` function (Windows only) introduced in PHP 7.2;
- the `PHP_FLOAT_*` constant introduced in PHP 7.2;
- the `PHP_OS_FAMILY` constant introduced in PHP 7.2;
- the `is_countable` function introduced in PHP 7.3;
- the `array_key_first` and `array_key_last` functions introduced in PHP 7.3;
- the `hrtime` function introduced in PHP 7.3;
- the `JsonException` class introduced in PHP 7.3;
- the `get_mangled_object_vars`, `mb_str_split` and `password_algos` functions
  introduced in PHP 7.4;
- the `fdiv` function introduced in PHP 8.0;
- the `get_debug_type` function introduced in PHP 8.0;
- the `preg_last_error_msg` function introduced in PHP 8.0;
- the `str_contains` function introduced in PHP 8.0;
- the `str_starts_with` and `str_ends_with` functions introduced in PHP 8.0;
- the `ValueError` class introduced in PHP 8.0;
- the `UnhandledMatchError` class introduced in PHP 8.0;
- the `FILTER_VALIDATE_BOOL` constant introduced in PHP 8.0;
- the `get_resource_id` function introduced in PHP 8.0;
- the `Attribute` class introduced in PHP 8.0;
- the `Stringable` interface introduced in PHP 8.0;

It is strongly recommended to upgrade your PHP version and/or install the missing
extensions whenever possible. This polyfill should be used only when there is no
better choice or when portability is a requirement.

Compatibility notes
===================

To write portable code between PHP5 and PHP7, some care must be taken:
- `\*Error` exceptions must be caught before `\Exception`;
- after calling `error_clear_last()`, the result of `$e = error_get_last()` must be
  verified using `isset($e['message'][0])` instead of `null !== $e`.

Usage
=====

When using [Composer](https://getcomposer.org/) to manage your dependencies, you
should **not** `require` the `symfony/polyfill` package, but the standalone ones:
- `symfony/polyfill-apcu` for using the `apcu_*` functions,
- `symfony/polyfill-ctype` for using the ctype functions,
- `symfony/polyfill-php54` for using the PHP 5.4 functions,
- `symfony/polyfill-php55` for using the PHP 5.5 functions,
- `symfony/polyfill-php56` for using the PHP 5.6 functions,
- `symfony/polyfill-php70` for using the PHP 7.0 functions,
- `symfony/polyfill-php71` for using the PHP 7.1 functions,
- `symfony/polyfill-php72` for using the PHP 7.2 functions,
- `symfony/polyfill-php73` for using the PHP 7.3 functions,
- `symfony/polyfill-php74` for using the PHP 7.4 functions,
- `symfony/polyfill-php80` for using the PHP 8.0 functions,
- `symfony/polyfill-iconv` for using the iconv functions,
- `symfony/polyfill-intl-grapheme` for using the `grapheme_*` functions,
- `symfony/polyfill-intl-idn` for using the `idn_to_ascii` and `idn_to_utf8` functions,
- `symfony/polyfill-intl-icu` for using the intl functions and classes,
- `symfony/polyfill-intl-messageformatter` for using the intl messageformatter,
- `symfony/polyfill-intl-normalizer` for using the intl normalizer,
- `symfony/polyfill-mbstring` for using the mbstring functions,
- `symfony/polyfill-util` for using the polyfill utility helpers.
- `symfony/polyfill-uuid` for using the `uuid_*` functions,

Requiring `symfony/polyfill` directly would prevent Composer from sharing
correctly polyfills in dependency graphs. As such, it would likely install
more code than required.

Design
======

This package is designed for low overhead and high quality polyfilling.

It adds only a few lightweight `require` statements to the bootstrap process
to support all polyfills. Implementations are then loaded on-demand when
needed during code execution.

If your project requires a minimum PHP version it is advisable to add polyfills
for lower PHP versions to the `replace` section of your `composer.json`.
This removes any overhead from these polyfills as they are no longer part of your project.
The same can be done for polyfills for extensions that you require.

If your project requires php 7.0, and needs the mb extension, the replace section would look
something like this:

```json
{
    "replace": {
        "symfony/polyfill-php54": "*",
        "symfony/polyfill-php55": "*",
        "symfony/polyfill-php56": "*",
        "symfony/polyfill-php70": "*",
        "symfony/polyfill-mbstring": "*"
    }
}
```

Polyfills are unit-tested alongside their native implementation so that
feature and behavior parity can be proven and enforced in the long run.

License
=======

This library is released under the [MIT license](LICENSE).
