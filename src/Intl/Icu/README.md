Symfony Polyfill / Intl: ICU
============================

This component maps the following functions to the
[`symfony/intl`](https://github.com/symfony/intl) component in the abscense of the
[Intl](http://php.net/intl) extension.

- `intl_is_failure($errorCode)` -> `IntlGlobals::isFailure($errorCode)`
- `intl_get_error_code()` -> `IntlGlobals::getErrorCode()`
- `intl_get_error_message()` -> `IntlGlobals::getErrorMessage()`
- `intl_error_name($errorCode)` -> `IntlGlobals::getErrorName($errorCode)`

More information can be found in the 
[root Polyfill README](https://github.com/symfony/polyfill/blob/master/README.md).

License
=======

This library is released under the [MIT license](LICENSE).