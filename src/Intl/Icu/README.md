Symfony Polyfill / Intl: ICU
============================

The [`symfony/polyfill`](https://github.com/symfony/polyfill) library backports
features found in the latest PHP versions and provides compatibility layers for
some extensions and functions.

This `intl-icu` component maps the following functions to the
[`symfony/intl`](https://github.com/symfony/intl) component in the abscense of the
[Intl](http://php.net/intl) extension.

- `intl_is_failure($errorCode)` -> `IntlGlobals::isFailure($errorCode)`
- `intl_get_error_code()` -> `IntlGlobals::getErrorCode()`
- `intl_get_error_message()` -> `IntlGlobals::getErrorMessage()`
- `intl_error_name($errorCode)` -> `IntlGlobals::getErrorName($errorCode)`

More information can be found in the [root README](../../../README.md).

License
=======

This library is released under the [MIT license](LICENSE).