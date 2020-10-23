# 1.19.0

  * Add a polyfill for the `Attribute` class
  * Fix the name of arguments for PHP 8
  * Improve performances of `array_key_last()`
  * Fix polyfill for `mb_strrchr()`
  * Skip loading `Stringable` on PHP 8
  * Fix passing `$length=null` to `grapheme_substr()`
  * Fix `iconv_substr()` and `grapheme_substr()` on PHP 8
  * Fix using any IDNA constants

# 1.18.1

  * Don't force labels containing URL delimiters to stay in their Unicode form when using `idn_to_ascii()`

# 1.18.0

  * improve polyfill-idn to make it compliant with latest Unicode specs
  * added `UnhandledMatchError` to the PHP 8 polyfill

# 1.17.1

  * fix accuracy of `Normalizer::isNormalized()`

# 1.17.0

  * added `get_resource_id()` to the PHP 8 polyfill
  * fix declaring extra constants when `intl` is loaded

# 1.16.0

  * added `str_starts_with()` and `str_ends_with()` to the PHP 8 polyfill
  * added polyfill for `PHP_FLOAT_*` constants
  * fixed `spl_object_id()` on 32-bit systems
  * fixed `idn_to_ascii()` not failing on leading or trailing hyphen-minus

# 1.15.0

  * added interface `Stringable` to the PHP 8 polyfill
  * added `get_debug_type()` to the PHP 8 polyfill
  * added `str_contains()` to the PHP 8 polyfill
  * added `preg_last_error_msg()` to the PHP 8 polyfill
  * added support for UUID V3 and V5
  * added support for UUID on 32-bit systems
  * fixed support for preloading

# 1.14.0

  * added PHP 8.0 polyfill

# 1.13.2

  * use correct block size for SHA1 in `hash_pbkdf2()` polyfill
  * fixed `mb_str_split()` ignoring new-line characters
  * updated Unicode maps

# 1.13.1

  * fixed issues with the uuid polyfill

# 1.13.0

  * added polyfills for `uuid_*` functions
  * optimized `mb_str_split()`
  * fixed handling negative offsets for grapheme and mbstring functions

# 1.12.0

  * added PHP 7.4 polyfill

# 1.11.0

  * fixed support for IDN with trailing dot
  * added missing polyfill for `JsonException`

# 1.10.0

  * added polyfill for `idn_to_ascii()` and `idn_to_utf8()`
  * added polyfill for intl's `MessageFormatter`
  * prevented DoS via long passwords when using `hash_pbkdf2()` polyfill
  * duplicated `mb_ord()`, `mb_chr()` and `mb_scrub()` polyfills in the `php72` one

# 1.9.0

  * added polyfill for `hrtime()`
  * added polyfills for `array_key_first()` and `array_key_last()`
  * fixed infinite loop in `iconv()` polyfill when using translit mode
  * fixed converting to title case with mbstring polyfill

# 1.8.0

  * added PHP 7.3 polyfill
  * added polyfills for `ctype_*` functions

# 1.7.0

  * added logic to new stream functions on Windows (sapi_windows_vt100_support)
  * added polyfills for mb_*_numericentity
  * made translit/ignore flags order-insensitive

# 1.6.0

  * add `SessionUpdateTimestampHandlerInterface` in PHP 7.0 polyfill
  * fixed loading of Apcu polyfill when Zend Server's Data Cache is used

# 1.5.0

  * added polyfill for spl_object_id()
  * fixed apcu function when apc ones are polyfilled with Zend Server's Data Cache
  * added `PHP_OS_FAMILY` polyfill

# 1.4.0

  * added PHP 7.2 polyfill

# 1.3.1

  * added missing validation to mb_convert_case()
  * added missing PHP_INT_MIN constant
  * fixed iconv_substr(): Detected an illegal character in input string
  * provided APCUIterator for APCu 4.x users

# 1.3.0

  * added polyfill for `is_iterable()`
  * added polyfills for `mb_chr()`, `mb_ord()` and `mb_scrub()`
  * added support for PHP 7.1
  * silenced `iconv_strlen()` in `mb_strlen()` polyfill
  * bypassed iconv for some charsets in mb_strlen
  * fixed `mb_convert_variables()` poylfill

# 1.2.0

  * bug #61 Normalizer::decompose() should reorder "recursive" combining chars (nicolas-grekas)
  * bug #59 Normalizer::recompose() should reset the last combining class on ASCII (nicolas-grekas)
  * bug #59 Normalizer::isNormalized() should fail with Normalizer::NONE (nicolas-grekas)
  * bug #59 Normalizer::isNormalized() and ::normalize() should check for multibyte string function overload (nicolas-grekas)
  * feature #44/#53 allow paragonie/random_compat 2.0 (ickbinhier)
  * feature #51 Use plain PHP for data maps to benefit from OPcache on PHP 5.6+ (nicolas-grekas)
  * bug #49 Fix hex2bin return null (fuhry, binwiederhier)

# 1.1.1

  * bug #40 [Apcu] Load APCUIterator only when APCIterator exists (nicolas-grekas)
  * bug #37 [Iconv] Fix wrong use in bootstrap.php (tucksaun)
  * bug #31 Fix class_uses polyfill (WouterJ)

# 1.1.0

  * feature #22 [APCu] A new polyfill for the legacy APC users (nicolas-grekas)
  * bug #28 [Php70] Workaround https://bugs.php.net/63206 (nicolas-grekas)

# 1.0.1

  * bug #14 ldap_escape does not encode leading/trailing spaces. (ChadSikorra)
  * bug #17 Fix #16 - gzopen() / gzopen64() - 32 bit builds of Ubuntu 14.04 (fisharebest)

# 1.0.0

  * Hello symfony/polyfill
