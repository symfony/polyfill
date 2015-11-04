Symfony Polyfill / Intl: Grapheme
=================================

The [`symfony/polyfill`](https://github.com/symfony/polyfill) library backports
features found in the latest PHP versions and provides compatibility layers for
some extensions and functions.

This `intl-grapheme` component provides a partial, native PHP implementation of the
[Grapheme functions](http://php.net/manual/en/ref.intl.grapheme.php) from the
[Intl](http://php.net/intl) extension.

- `grapheme_extract`: Extract a sequence of grapheme clusters from a text buffer, which must be encoded in UTF-8
- `grapheme_stripos`: Find position (in grapheme units) of first occurrence of a case-insensitive string
- `grapheme_stristr`: Returns part of haystack string from the first occurrence of case-insensitive needle to the end of haystack
- `grapheme_strlen`: Get string length in grapheme units
- `grapheme_strpos`: Find position (in grapheme units) of first occurrence of a string
- `grapheme_strripos`: Find position (in grapheme units) of last occurrence of a case-insensitive string
- `grapheme_strrpos`: Find position (in grapheme units) of last occurrence of a string
- `grapheme_strstr`: Returns part of haystack string from the first occurrence of needle to the end of haystack
- `grapheme_substr`: Return part of a string

More information can be found in the [root README](../../../README.md).

License
=======

This library is released under the [MIT license](LICENSE).