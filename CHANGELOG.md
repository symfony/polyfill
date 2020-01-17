* v1.14.0

  * added PHP 8.0 polyfill

* v1.13.2

  * use correct block size for SHA1 in `hash_pbkdf2()` polyfill
  * fixed `mb_str_split()` ignoring new-line characters
  * updated Unicode maps

* v1.13.1

  * fixed issues with the uuid polyfill

* v1.13.0

  * added polyfills for `uuid_*` functions
  * optimized `mb_str_split()`
  * fixed handling negative offsets for grapheme and mbstring functions

* v1.12.0

  * added PHP 7.4 polyfill

* v1.11.0

  * fixed support for IDN with trailing dot
  * added missing polyfill for `JsonException`

* v1.10.0

  * added polyfill for `idn_to_ascii()` and `idn_to_utf8()`
  * added polyfill for intl's `MessageFormatter`
  * prevented DoS via long passwords when using `hash_pbkdf2()` polyfill
  * duplicated `mb_ord()`, `mb_chr()` and `mb_scrub()` polyfills in the `php72` one

* v1.9.0

  * added polyfill for `hrtime()`
  * added polyfills for `array_key_first()` and `array_key_last()`
  * fixed infinite loop in `iconv()` polyfill when using translit mode
  * fixed converting to title case with mbstring polyfill

* v1.8.0

  * added PHP 7.3 polyfill
  * added polyfills for `ctype_*` functions

* v1.7.0

  * added logic to new stream functions on Windows (sapi_windows_vt100_support)
  * added polyfills for mb_*_numericentity
  * made translit/ignore flags order-insensitive

* v1.6.0

  * add `SessionUpdateTimestampHandlerInterface` in PHP 7.0 polyfill
  * fixed loading of Apcu polyfill when Zend Server's Data Cache is used

* v1.5.0

  * added polyfill for spl_object_id()
  * fixed apcu function when apc ones are polyfilled with Zend Server's Data Cache
  * added `PHP_OS_FAMILY` polyfill

* v1.4.0

  * added PHP 7.2 polyfill

* v1.3.1

  * added missing validation to mb_convert_case()
  * added missing PHP_INT_MIN constant
  * fixed iconv_substr(): Detected an illegal character in input string
  * provided APCUIterator for APCu 4.x users

* v1.3.0

  * added polyfill for `is_iterable()`
  * added polyfills for `mb_chr()`, `mb_ord()` and `mb_scrub()`
  * added support for PHP 7.1
  * silenced `iconv_strlen()` in `mb_strlen()` polyfill
  * bypassed iconv for some charsets in mb_strlen
  * fixed `mb_convert_variables()` poylfill

* v1.2.0

  * bug #61 Normalizer::decompose() should reorder "recursive" combining chars (nicolas-grekas)
  * bug #59 Normalizer::recompose() should reset the last combining class on ASCII (nicolas-grekas)
  * bug #59 Normalizer::isNormalized() should fail with Normalizer::NONE (nicolas-grekas)
  * bug #59 Normalizer::isNormalized() and ::normalize() should check for multibyte string function overload (nicolas-grekas)
  * feature #44/#53 allow paragonie/random_compat 2.0 (ickbinhier)
  * feature #51 Use plain PHP for data maps to benefit from OPcache on PHP 5.6+ (nicolas-grekas)
  * bug #49 Fix hex2bin return null (fuhry, binwiederhier)

* v1.1.1

  * bug #40 [Apcu] Load APCUIterator only when APCIterator exists (nicolas-grekas)
  * bug #37 [Iconv] Fix wrong use in bootstrap.php (tucksaun)
  * bug #31 Fix class_uses polyfill (WouterJ)

* v1.1.0

  * feature #22 [APCu] A new polyfill for the legacy APC users (nicolas-grekas)
  * bug #28 [Php70] Workaround https://bugs.php.net/63206 (nicolas-grekas)

* v1.0.1

  * bug #14 ldap_escape does not encode leading/trailing spaces. (ChadSikorra)
  * bug #17 Fix #16 - gzopen() / gzopen64() - 32 bit builds of Ubuntu 14.04 (fisharebest)

* v1.0.0

  * Hello symfony/polyfill
