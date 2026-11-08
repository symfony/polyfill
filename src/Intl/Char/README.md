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
- `hasBinaryProperty()` for all the binary properties of ICU (emoji ones,
  `NFC_Inert`, `Segment_Starter`... included), and `isUAlphabetic()`,
  `isULowercase()`, `isUUppercase()` and `isUWhiteSpace()`;
- `getIntPropertyValue()`, `getIntPropertyMinValue()` and `getIntPropertyMaxValue()`
  for all the enumerated properties but the blocks and the scripts: the bidi class,
  the general category, the combining classes, the decomposition type, the East
  Asian width, the line, grapheme cluster, word and sentence breaks, the joining
  type and group, the Hangul syllable type, the numeric type, the normalization
  quick checks, the Indic categories, the vertical orientation...;
- `tolower()`, `toupper()`, `totitle()` and `foldCase()`, the simple case mappings;
- `getCombiningClass()`, `charDigitValue()`, `digit()`, `forDigit()`,
  `getNumericValue()` and `charAge()`;
- `ord()`, `chr()`, `getUnicodeVersion()` and all the constants of `IntlChar`.

Code points are given as integers or as strings of one UTF-8 character, as with
the Intl extension, and the data are those of Unicode 17.0: every code point gives
what Intl gives with ICU 78. The names of characters (`charName()`,
`charFromName()`, `enumCharNames()`), their blocks (`getBlockCode()`) and scripts,
whose numbers are ICU's own, and the names of properties and of their values are
not provided: these methods throw a `LogicException`.

The data take 495 KB, one file a property, loaded when a method needs it.

More information can be found in the
[main Polyfill README](https://github.com/symfony/polyfill/blob/main/README.md).

License
=======

This library is released under the [MIT license](LICENSE).
