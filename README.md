Symfony Polyfill
================

This project backports features found in the latest PHP versions and provides
compatibility layers for some extensions and functions. It is intended to be
used when portability across PHP versions and extensions is desired.

Polyfills are provided for:
- the `mbstring` and `iconv` extensions;
- the `Normalizer` class and the `grapheme_*` functions;
- the `utf8_encode` and `utf8_decode` functions from the `xml` extension;
- the `Collator`, `NumberFormatter`, `Locale` and `IntlDateFormatter` classes;
- the `intl_error_name`, `intl_get_error_code`, `intl_get_error_message` and
  `intl_is_failure` functions;
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
- a `Binary` utility class to be used when compatibility with
  `mbstring.func_overload` is required.

It is strongly recommended to upgrade your PHP version and/or install the missing
extensions whenever possible. This polyfill should be used only when there is no
better choice or when portability is a requirement.

Compatibility notes
===================

To write portable code between PHP5 and PHP7, some care must be taken:
- `\*Error` exceptions must by caught before `\Exception`;
- after calling `error_clear_last()`, the result of `$e = error_get_last()` must be
  verified using `isset($e['message'][0])` instead of `null === $e`.

Design
======

This package is designed for low overhead and high quality polyfilling.

It adds only a few lightweight `require` statements to the bootstrap process
to support all polyfills. Implementations are then loaded on-demand when
needed during code execution.

Polyfills are unit-tested alongside their native implementation so that
feature and behavior parity can be proven and enforced in the long run.

License
=======

This library is released under the [MIT license](LICENSE).