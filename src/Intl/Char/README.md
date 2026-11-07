Symfony Polyfill / Intl: Char
=============================

This component provides a partial, native PHP implementation of the
[`IntlChar`](https://php.net/IntlChar) class provided by the
[Intl](https://php.net/intl) extension: the properties of code points from the
Unicode Character Database, like those the
[Unicode Bidirectional Algorithm](https://unicode.org/reports/tr9/) needs.

- `charDirection()`, `charMirror()`, `isMirrored()` and `getBidiPairedBracket()`;
- `charType()` and `enumCharTypes()`, and the predicates derived from the general
  category, like ICU: `isalpha()`, `isdigit()`, `isspace()`, `isWhitespace()`,
  `ispunct()`, `isgraph()`, `isprint()`, `isIDStart()`, `isJavaIDPart()`...;
- `isUAlphabetic()`, `isULowercase()`, `isUUppercase()`, `isUWhiteSpace()`, and
  `hasBinaryProperty()` for the binary properties of the Unicode Character Database;
- `tolower()`, `toupper()`, `totitle()` and `foldCase()`, the simple case mappings;
- `getCombiningClass()`, `charDigitValue()`, `digit()`, `forDigit()`,
  `getNumericValue()` and `charAge()`;
- `getIntPropertyValue()` for the bidirectional class, the paired bracket type,
  the general category (and its mask), the combining class and the binary properties;
- `ord()`, `chr()`, `getUnicodeVersion()` and all the constants of `IntlChar`.

Code points are given as integers or as strings of one UTF-8 character, as with
the Intl extension, and the data are those of Unicode 17.0: every code point gives
what Intl gives with ICU 78. The names of characters (`charName()`,
`charFromName()`, `enumCharNames()`), their blocks (`getBlockCode()`), the names
of properties and of their values, and the other enumerated properties are not
provided: these methods throw a `LogicException`.

The data take 165 KB and are loaded when a method needs them.

More information can be found in the
[main Polyfill README](https://github.com/symfony/polyfill/blob/main/README.md).

License
=======

This library is released under the [MIT license](LICENSE).
