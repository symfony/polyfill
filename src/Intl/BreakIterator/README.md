Symfony Polyfill / Intl: BreakIterator
======================================

This component provides a partial, native PHP implementation of the
[`IntlBreakIterator`](https://php.net/IntlBreakIterator) class provided by the
[Intl](https://php.net/intl) extension:

- `IntlBreakIterator::createWordInstance()`, `createCharacterInstance()` and
  `createCodePointInstance()`;
- boundary navigation (`first()`, `last()`, `next()`, `previous()`, `current()`,
  `following()`, `preceding()`, `isBoundary()`), iteration and `getPartsIterator()`;
- `IntlRuleBasedBreakIterator::getRuleStatus()` and `getRuleStatusVec()`;
- `IntlCodePointBreakIterator::getLastCodePoint()`.

Offsets are UTF-8 byte offsets, as with the Intl extension.

Word boundaries implement the default rules of
[UAX #29](https://www.unicode.org/reports/tr29/#Word_Boundary_Rules), and character
boundaries use PCRE's `\X` (or the grapheme cluster pattern of
`symfony/polyfill-intl-grapheme` when it is installed, as `\X` misses some rules
before PCRE 10.44). Boundaries are computed lazily, so that moving around a
large text only costs the segments that are visited.

Known differences with ICU:

- ICU uses dictionaries for Chinese, Japanese, Thai, Lao, Khmer and Myanmar
  (`你好世界` → `你好|世界`); this polyfill applies the UAX #29 rules only, which
  break between every ideograph, Hiragana or Thai character, and keep Katakana runs
  together;
- the locale argument is ignored;
- line, sentence and title instances, and custom rules, are not provided.

More information can be found in the
[main Polyfill README](https://github.com/symfony/polyfill/blob/main/README.md).

License
=======

This library is released under the [MIT license](LICENSE).
