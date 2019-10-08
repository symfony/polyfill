<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Transliterator;

/**
 * Partial intl implementation in pure PHP.
 *
 * Implemented:
 *
 * @author Lars Moelleken <lars@moelleken.org>
 *
 * @internal
 */
final class Transliterator
{
    const FORWARD = 0;
    const REVERSE = 1;

    private static $LOCALE_TO_TRANSLITERATOR_ID = array(
        'am' => 'Amharic-Latin',
        'ar' => 'Arabic-Latin',
        'az' => 'Azerbaijani-Latin',
        'be' => 'Belarusian-Latin',
        'bg' => 'Bulgarian-Latin',
        'bn' => 'Bengali-Latin',
        'el' => 'Greek-Latin',
        'fa' => 'Persian-Latin',
        'he' => 'Hebrew-Latin',
        'hy' => 'Armenian-Latin',
        'ka' => 'Georgian-Latin',
        'kk' => 'Kazakh-Latin',
        'ky' => 'Kirghiz-Latin',
        'ko' => 'Korean-Latin',
        'mk' => 'Macedonian-Latin',
        'mn' => 'Mongolian-Latin',
        'or' => 'Oriya-Latin',
        'ps' => 'Pashto-Latin',
        'ru' => 'Russian-Latin',
        'sr' => 'Serbian-Latin',
        'th' => 'Thai-Latin',
        'tk' => 'Turkmen-Latin',
        'uk' => 'Ukrainian-Latin',
        'uz' => 'Uzbek-Latin',
        'zh' => 'Han-Latin',
    );

    /**
     * @var string|null
     */
    public $id;

    /**
     * @var int
     */
    private $direction = self::FORWARD;

    /**
     * Private constructor to deny instantiation.
     *
     * @see https://php.net/manual/en/transliterator.construct.php
     */
    private function __construct() {
    }

    public static function create($id, $direction = null) {
        $transliterator = new self();

        $transliterator->id = self::clean_id($id);

        if (null !== $direction) {
            throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator->direction".'));
        }

        return $transliterator;
    }

    private static function clean_id($s) {
        return rtrim(
            str_replace(
                array(' ', ':]', 'NonspacingMark'),
                array('', ':] ', 'Nonspacing Mark'),
                $s
            ),
            ';'
        );
    }

    public static function createFromRules($rules, $direction = null) {
        $transliterator = new self();

        $transliterator->id = self::clean_id($rules);

        if (null !== $direction) {
            throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator->direction".'));
        }

        return $transliterator;
    }

    public static function createInverse() {
        throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator::createInverse".'));
    }

    public static function listIDs() {
        return array_values(self::$LOCALE_TO_TRANSLITERATOR_ID);
    }

    public function transliterate($subject, $start = null, $end = null) {
        if (null !== $start) {
            $s = mb_substr($subject, $start, $end);
        } else {
            $s = $subject;
        }

        foreach (explode(';', $this->id) as $rule) {
            $rule = str_replace(array('/BGN', 'ANY-'), '', strtoupper($rule));

            // DEBUG
            //var_dump($rule);

            if ('NFC' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_C) ?: $s = normalizer_normalize($s, \Normalizer::NFC);
            } elseif ('NFD' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_D) ?: $s = normalizer_normalize($s, \Normalizer::NFD);
            } elseif ('NFKD' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_KD) ?: $s = normalizer_normalize($s, \Normalizer::NFKD);
            } elseif ('NFKC' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_KC) ?: $s = normalizer_normalize($s, \Normalizer::NFKC);
            } elseif ('[:NONSPACING MARK:] REMOVE' === $rule) {
                $s = preg_replace('/\p{Mn}++/u', '', $s);
            } elseif (false !== strpos($rule, 'LATIN-ASCII')) {
                $s = self::to_ascii($s);
            } elseif (false !== strpos($rule, 'UPPER')) {
                $s = mb_strtoupper($s);
            } elseif (false !== strpos($rule, 'LOWER')) {
                $s = mb_strtolower($s);
            } elseif (false !== strpos($rule, 'LATIN')) {
                $s = self::normalize_latin_symbols($s);
            } elseif ($lang = array_search($rule, self::$LOCALE_TO_TRANSLITERATOR_ID)) {
                $s = self::to_ascii($s, $lang);
            } elseif (
                false !== strpos($rule, '-ASCII')
                &&
                $lang = str_replace('-ASCII', '', $rule)
            ) {
                $s = self::to_ascii($s, $lang);
            }
        }

        return $s.(null !== $start ? mb_substr($subject, $end) : '');
    }

    public function getErrorCode() {
        return 0;
    }

    public function getErrorMessage() {
        return '';
    }

    /**
     * @var array<string, array<string, string>>|null
     */
    private static $ASCII_MAPS;

    /**
     * @var array<string, array<string, string>>|null
     */
    private static $ASCII_MAPS_AND_EXTRAS;

    /**
     * @var array<string, int>|null
     */
    private static $ORD;

    /**
     * url: https://en.wikipedia.org/wiki/Wikipedia:ASCII#ASCII_printable_characters.
     *
     * @var string
     */
    private static $REGEX_ASCII = '/[^\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F]/';

    /**
     * bidirectional text chars.
     *
     * url: https://www.w3.org/International/questions/qa-bidi-unicode-controls
     *
     * @var array<int, string>
     */
    private static $BIDI_UNI_CODE_CONTROLS_TABLE = array(
        // LEFT-TO-RIGHT EMBEDDING (use -> dir = "ltr")
        8234 => "\xE2\x80\xAA",
        // RIGHT-TO-LEFT EMBEDDING (use -> dir = "rtl")
        8235 => "\xE2\x80\xAB",
        // POP DIRECTIONAL FORMATTING // (use -> </bdo>)
        8236 => "\xE2\x80\xAC",
        // LEFT-TO-RIGHT OVERRIDE // (use -> <bdo dir = "ltr">)
        8237 => "\xE2\x80\xAD",
        // RIGHT-TO-LEFT OVERRIDE // (use -> <bdo dir = "rtl">)
        8238 => "\xE2\x80\xAE",
        // LEFT-TO-RIGHT ISOLATE // (use -> dir = "ltr")
        8294 => "\xE2\x81\xA6",
        // RIGHT-TO-LEFT ISOLATE // (use -> dir = "rtl")
        8295 => "\xE2\x81\xA7",
        // FIRST STRONG ISOLATE // (use -> dir = "auto")
        8296 => "\xE2\x81\xA8",
        // POP DIRECTIONAL ISOLATE
        8297 => "\xE2\x81\xA9",
    );

    /**
     * Returns an replacement array for ASCII methods with one language.
     *
     * For example, German will map 'ä' to 'ae', while other languages
     * will simply return e.g. 'a'.
     *
     * @psalm-suppress InvalidNullableReturnType - we use the prepare* methods here, so we don't get NULL here
     *
     * @param string $language              [optional] <p>Language of the source string e.g.: en, de_at, or de-ch.
     *                                      (default is 'en') | ASCII::*_LANGUAGE_CODE</p>
     * @param bool   $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @return array{orig: string[], replace: string[]}
     *                     <p>An array of replacements.</p>
     */
    private static function charsArrayWithOneLanguage(
        $language = 'en',
        $replace_extra_symbols = false
    ) {
        $language = self::get_language($language);

        // init
        static $CHARS_ARRAY = array();
        $cacheKey = ''.$replace_extra_symbols;

        // check static cache
        if (isset($CHARS_ARRAY[$cacheKey][$language])) {
            return $CHARS_ARRAY[$cacheKey][$language];
        }

        if ($replace_extra_symbols) {
            self::prepareAsciiAndExtrasMaps();

            if (isset(self::$ASCII_MAPS_AND_EXTRAS[$language])) {
                $tmpArray = self::$ASCII_MAPS_AND_EXTRAS[$language];

                $CHARS_ARRAY[$cacheKey][$language] = array(
                    'orig' => array_keys($tmpArray),
                    'replace' => array_values($tmpArray),
                );
            } else {
                $CHARS_ARRAY[$cacheKey][$language] = array(
                    'orig' => array(),
                    'replace' => array(),
                );
            }
        } else {
            self::prepareAsciiMaps();

            if (isset(self::$ASCII_MAPS[$language])) {
                $tmpArray = self::$ASCII_MAPS[$language];

                $CHARS_ARRAY[$cacheKey][$language] = array(
                    'orig' => array_keys($tmpArray),
                    'replace' => array_values($tmpArray),
                );
            } else {
                $CHARS_ARRAY[$cacheKey][$language] = array(
                    'orig' => array(),
                    'replace' => array(),
                );
            }
        }

        return $CHARS_ARRAY[$cacheKey][$language];
    }

    /**
     * Returns an replacement array for ASCII methods with multiple languages.
     *
     * @param bool $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @return array{orig: string[], replace: string[]}
     *                     <p>An array of replacements.</p>
     */
    private static function charsArrayWithSingleLanguageValues($replace_extra_symbols = false)
    {
        // init
        static $CHARS_ARRAY = array();
        $cacheKey = ''.$replace_extra_symbols;

        if (isset($CHARS_ARRAY[$cacheKey])) {
            return $CHARS_ARRAY[$cacheKey];
        }

        if ($replace_extra_symbols) {
            self::prepareAsciiAndExtrasMaps();

            /* @noinspection AlterInForeachInspection */
            /* @psalm-suppress PossiblyNullIterator - we use the prepare* methods here, so we don't get NULL here */
            foreach (self::$ASCII_MAPS_AND_EXTRAS as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }
        } else {
            self::prepareAsciiMaps();

            /* @noinspection AlterInForeachInspection */
            /* @psalm-suppress PossiblyNullIterator - we use the prepare* methods here, so we don't get NULL here */
            foreach (self::$ASCII_MAPS as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }
        }

        $CHARS_ARRAY[$cacheKey] = \call_user_func_array('array_merge', $CHARS_ARRAY[$cacheKey] + array());

        $CHARS_ARRAY[$cacheKey] = array(
            'orig' => array_keys($CHARS_ARRAY[$cacheKey]),
            'replace' => array_values($CHARS_ARRAY[$cacheKey]),
        );

        return $CHARS_ARRAY[$cacheKey];
    }

    /**
     * Accepts a string and removes all non-UTF-8 characters from it + extras if needed.
     *
     * @param string $s <p>The string to be sanitized.</p>
     *
     * @return string
     *                <p>A clean UTF-8 string.</p>
     */
    private static function clean($s) {
        // http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
        // caused connection reset problem on larger strings

        $regex = '/
          (
            (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
            |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
            |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
            |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
            ){1,100}                      # ...one or more times
          )
        | ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
        | ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
        /x';
        $s = (string) preg_replace($regex, '$1', $s);

        $s = self::normalize_whitespace($s);

        $s = self::remove_invisible_characters($s);

        return $s;
    }

    /**
     * Checks if a string is 7 bit ASCII.
     *
     * @param string $s <p>The string to check.</p>
     *
     * @return bool
     *              <p>
     *              <strong>true</strong> if it is ASCII<br>
     *              <strong>false</strong> otherwise
     *              </p>
     */
    private static function is_ascii($s)
    {
        if ('' === $s) {
            return true;
        }

        return !preg_match(self::$REGEX_ASCII, $s);
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * @param string $s <p>The string to be normalized.</p>
     *
     * @return string
     *                <p>A string with normalized characters for commonly used chars in Word documents.</p>
     */
    private static function normalize_latin_symbols($s)
    {
        if ('' === $s) {
            return '';
        }

        // init
        static $MSWORD_CACHE = array();

        if (!isset($MSWORD_CACHE['orig'])) {
            self::prepareAsciiAndExtrasMaps();

            /**
             * @psalm-suppress PossiblyNullArrayAccess - we use the prepare* methods here, so we don't get NULL here
             *
             * @var array
             */
            $map = self::$ASCII_MAPS_AND_EXTRAS['latin_symbols'];

            $MSWORD_CACHE = array(
                'orig' => array_keys($map),
                'replace' => array_values($map),
            );
        }

        return str_replace($MSWORD_CACHE['orig'], $MSWORD_CACHE['replace'], $s);
    }

    /**
     * Normalize the whitespace.
     *
     * @param string $s <p>The string to be normalized.</p>
     *
     * @return string
     *                <p>A string with normalized whitespace.</p>
     */
    private static function normalize_whitespace($s) {
        if ('' === $s) {
            return '';
        }

        static $WHITESPACE_CACHE = null;
        if (null === $WHITESPACE_CACHE) {
            self::prepareAsciiMaps();

            /* @psalm-suppress PossiblyNullArrayAccess - we use the prepare* methods here, so we don't get NULL here */
            $WHITESPACE_CACHE = array_keys(self::$ASCII_MAPS[' ']);
        }
        $s = str_replace($WHITESPACE_CACHE, ' ', $s);

        static $BIDI_UNICODE_CONTROLS_CACHE = null;
        if (null === $BIDI_UNICODE_CONTROLS_CACHE) {
            $BIDI_UNICODE_CONTROLS_CACHE = array_values(self::$BIDI_UNI_CODE_CONTROLS_TABLE);
        }
        $s = str_replace($BIDI_UNICODE_CONTROLS_CACHE, '', $s);

        return $s;
    }

    /**
     * Remove invisible characters from a string.
     *
     * e.g.: This prevents sandwiching null characters between ascii characters, like Java\0script.
     *
     * copy&past from https://github.com/bcit-ci/CodeIgniter/blob/develop/system/core/Common.php
     *
     * @param string $s
     * @param bool   $url_encoded
     * @param string $replacement
     *
     * @return string
     */
    private static function remove_invisible_characters(
        $s,
        $url_encoded = true,
        $replacement = ''
    ) {
        // init
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcefBCEF]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-fA-F]/'; // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $s = (string) preg_replace($non_displayables, $replacement, $s, -1, $count);
        } while (0 !== $count);

        return $s;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * by default. The language or locale of the source string can be supplied
     * for language-specific transliteration in any of the following formats:
     * en, en_GB, or en-GB. For example, passing "de" results in "äöü" mapping
     * to "aeoeue" rather than "aou" as in other languages.
     *
     * @param string $s                        <p>The input string.</p>
     * @param string $language                 [optional] <p>Language of the source string.
     *                                         (default is 'en') | ASCII::*_LANGUAGE_CODE</p>
     * @param bool   $remove_unsupported_chars [optional] <p>Whether or not to remove the
     *                                         unsupported characters.</p>
     * @param bool   $replace_extra_symbols    [optional]  <p>Add some more replacements e.g. "£" with " pound ".</p>
     * @param bool   $use_transliterate        [optional]  <p>Use ASCII::to_transliterate() for unknown chars.</p>
     *
     * @return string
     *                <p>A string that contains only ASCII characters.</p>
     */
    private static function to_ascii(
        $s,
        $language = 'en',
        $remove_unsupported_chars = true,
        $replace_extra_symbols = false,
        $use_transliterate = false
    ) {
        if ('' === $s) {
            return '';
        }

        $language_specific_chars = self::charsArrayWithOneLanguage($language, $replace_extra_symbols);
        if (!empty($language_specific_chars['orig'])) {
            $s = str_replace($language_specific_chars['orig'], $language_specific_chars['replace'], $s);
        }

        $language_all_chars = self::charsArrayWithSingleLanguageValues($replace_extra_symbols);
        $s = str_replace($language_all_chars['orig'], $language_all_chars['replace'], $s);

        /* @psalm-suppress PossiblyNullOperand - we use the prepare* methods here, so we don't get NULL here */
        if (!isset(self::$ASCII_MAPS[$language])) {
            $use_transliterate = true;
        }

        if (true === $use_transliterate) {
            $s = self::to_transliterate($s, null);
        }

        if (true === $remove_unsupported_chars) {
            $s = (string) str_replace(array("\n\r", "\n", "\r", "\t"), ' ', $s);
            $s = (string) preg_replace(self::$REGEX_ASCII, '', $s);
        }

        return $s;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * @param string      $s       <p>The input string.</p>
     * @param string|null $unknown [optional] <p>Character use if character unknown. (default is '?')
     *                             But you can also use NULL to keep the unknown chars.</p>
     *
     * @return string
     *                <p>A String that contains only ASCII characters.</p>
     */
    private static function to_transliterate(
        $s,
        $unknown = '?'
    ) {
        static $UTF8_TO_TRANSLIT = null;
        static $TRANSLITERATOR = null;

        if ('' === $s) {
            return '';
        }

        // check if we only have ASCII, first (better performance)
        $s_tmp = $s;
        if (true === self::is_ascii($s)) {
            return $s;
        }

        $s = self::clean($s);

        // check again, if we only have ASCII, now ...
        if (
            $s_tmp !== $s
            &&
            true === self::is_ascii($s)
        ) {
            return $s;
        }

        if (null === self::$ORD) {
            self::$ORD = self::getData('ascii_ord');
        }

        preg_match_all('/.|[^\x00]$/us', $s, $array_tmp);
        $chars = $array_tmp[0];
        $ord = null;
        $s_tmp = '';
        foreach ($chars as &$c) {
            $ordC0 = self::$ORD[$c[0]];

            if ($ordC0 >= 0 && $ordC0 <= 127) {
                $s_tmp .= $c;

                continue;
            }

            $ordC1 = self::$ORD[$c[1]];

            // ASCII - next please
            if ($ordC0 >= 192 && $ordC0 <= 223) {
                $ord = ($ordC0 - 192) * 64 + ($ordC1 - 128);
            }

            if ($ordC0 >= 224) {
                $ordC2 = self::$ORD[$c[2]];

                if ($ordC0 <= 239) {
                    $ord = ($ordC0 - 224) * 4096 + ($ordC1 - 128) * 64 + ($ordC2 - 128);
                }

                if ($ordC0 >= 240) {
                    $ordC3 = self::$ORD[$c[3]];

                    if ($ordC0 <= 247) {
                        $ord = ($ordC0 - 240) * 262144 + ($ordC1 - 128) * 4096 + ($ordC2 - 128) * 64 + ($ordC3 - 128);
                    }

                    if ($ordC0 >= 248) {
                        $ordC4 = self::$ORD[$c[4]];

                        if ($ordC0 <= 251) {
                            $ord = ($ordC0 - 248) * 16777216 + ($ordC1 - 128) * 262144 + ($ordC2 - 128) * 4096 + ($ordC3 - 128) * 64 + ($ordC4 - 128);
                        }

                        if ($ordC0 >= 252) {
                            $ordC5 = self::$ORD[$c[5]];

                            if ($ordC0 <= 253) {
                                $ord = ($ordC0 - 252) * 1073741824 + ($ordC1 - 128) * 16777216 + ($ordC2 - 128) * 262144 + ($ordC3 - 128) * 4096 + ($ordC4 - 128) * 64 + ($ordC5 - 128);
                            }
                        }
                    }
                }
            }

            if (
                254 === $ordC0
                ||
                255 === $ordC0
                ||
                null === $ord
            ) {
                $s_tmp .= null === $unknown ? $c : $unknown;

                continue;
            }

            $bank = $ord >> 8;
            if (!isset($UTF8_TO_TRANSLIT[$bank])) {
                $UTF8_TO_TRANSLIT[$bank] = self::getDataIfExists(sprintf('x%02x', $bank));
                if (false === $UTF8_TO_TRANSLIT[$bank]) {
                    $UTF8_TO_TRANSLIT[$bank] = array();
                }
            }

            $new_char = $ord & 255;

            if (isset($UTF8_TO_TRANSLIT[$bank][$new_char])) {
                // keep for debugging
                /*
                echo "file: " . sprintf('x%02x', $bank) . "\n";
                echo "char: " . $c . "\n";
                echo "ord: " . $ord . "\n";
                echo "new_char: " . $new_char . "\n";
                echo "new_char: " . mb_chr($new_char) . "\n";
                echo "ascii: " . $UTF8_TO_TRANSLIT[$bank][$new_char] . "\n";
                echo "bank:" . $bank . "\n\n";
                 */

                if (null === $unknown && '' === $UTF8_TO_TRANSLIT[$bank][$new_char]) {
                    $c = null === $unknown ? $c : $unknown;
                } elseif ('[?]' === $UTF8_TO_TRANSLIT[$bank][$new_char]) {
                    $c = null === $unknown ? $c : $unknown;
                } else {
                    $c = $UTF8_TO_TRANSLIT[$bank][$new_char];
                }
            } else {
                // keep for debugging missing chars
                /*
                echo "file: " . sprintf('x%02x', $bank) . "\n";
                echo "char: " . $c . "\n";
                echo "ord: " . $ord . "\n";
                echo "new_char: " . $new_char . "\n";
                echo "new_char: " . mb_chr($new_char) . "\n";
                echo "bank:" . $bank . "\n\n";
                 */

                $c = null === $unknown ? $c : $unknown;
            }

            $s_tmp .= $c;
        }

        return $s_tmp;
    }

    /**
     * Get the language from a string.
     *
     * e.g.: de_at -> de_at
     *       de_DE -> de
     *       DE_DE -> de
     *       de-de -> de
     *
     * @param string $language
     *
     * @return string
     */
    private static function get_language($language)
    {
        $regex = '/(?<first>[a-z]+)[\-_]\g{first}/i';

        return str_replace(
            '-',
            '_',
            strtolower(
                (string) preg_replace($regex, '$1', $language)
            )
        );
    }

    /**
     * Get data from "/data/*.php".
     *
     * @param string $file
     *
     * @return array
     */
    private static function getData($file)
    {
        /* @noinspection PhpIncludeInspection */
        /* @noinspection UsingInclusionReturnValueInspection */
        /* @psalm-suppress UnresolvableInclude */
        return include __DIR__.'/data/'.$file.'.php';
    }

    /**
     * Get data from "/data/*.php".
     *
     * @param string $file
     *
     * @return array|false
     *                     <p>Will return <strong>false</strong> on error.</p>
     */
    private static function getDataIfExists($file)
    {
        $file = __DIR__.'/data/'.$file.'.php';
        if (file_exists($file)) {
            /* @noinspection PhpIncludeInspection */
            /* @noinspection UsingInclusionReturnValueInspection */
            return include $file;
        }

        return false;
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    private static function prepareAsciiAndExtrasMaps()
    {
        if (null === self::$ASCII_MAPS_AND_EXTRAS) {
            self::prepareAsciiMaps();

            /* @psalm-suppress PossiblyNullArgument - we use the prepare* methods here, so we don't get NULL here */
            self::$ASCII_MAPS_AND_EXTRAS = array_merge_recursive(
                (array) self::$ASCII_MAPS,
                (array) self::getData('ascii_extras_by_languages')
            );
        }
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    private static function prepareAsciiMaps()
    {
        if (null === self::$ASCII_MAPS) {
            self::$ASCII_MAPS = self::getData('ascii_by_languages');
        }
    }
}
