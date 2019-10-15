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
 * - create        - Opens a Transliterator by id. (WARNING: Transliterator::REVERSE is not implemented)
 * - transliterate - Transforms a string or part thereof using an ICU transliterator.
 *
 * @author Lars Moelleken <lars@moelleken.org>
 *
 * @internal
 */
class Transliterator
{
    const FORWARD = \Transliterator::FORWARD;
    const REVERSE = \Transliterator::REVERSE;

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
    private $direction = \Transliterator::FORWARD;

    /**
     * @var array|null
     */
    private static $TRANSLIT;

    /**
     * @var array<string, array<string, string>>|null
     */
    private static $ASCII_MAPS;

    /**
     * @var array<string, int>|null
     */
    private static $ORD;

    /**
     * @var string
     */
    private static $ASCII = "\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";

    private function __construct()
    {
    }

    public static function create($id, $direction = \Transliterator::FORWARD)
    {
        $transliterator = new self();

        $transliterator->id = self::clean_id($id);

        if (\Transliterator::FORWARD !== $direction && null !== $direction) {
            throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator->direction".'));
        }

        return $transliterator;
    }

    private static function clean_id($s)
    {
        return rtrim($s, ';');
    }

    public static function createFromRules($rules, $direction = \Transliterator::FORWARD)
    {
        return self::create($rules, $direction);
    }

    public static function createInverse()
    {
        throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator::createInverse".'));
    }

    public static function listIDs()
    {
        return array_values(self::$LOCALE_TO_TRANSLITERATOR_ID);
    }

    public function transliterate($s, $start = null, $end = null)
    {
        if ('' === $s) {
            return '';
        }

        $s_start = '';
        $s_end = '';
        if (null !== $start || null !== $end) {
            if (null !== $start) {
                if ($start < 0) {
                    return false;
                }

                $s_len = mb_strlen($s);
                if ($start > $s_len) {
                    return false;
                }
                if ($start === $s_len) {
                    $end = null;
                }

                $s_start = mb_substr($s, null, $start);
            } else {
                $s_start = '';
            }

            if (null !== $end) {
                if ($end < 0) {
                    return false;
                }

                $s_end = mb_substr($s, -$end);
                $s = mb_substr($s, $start, -$end);
            } else {
                $s = mb_substr($s, $start);
            }
        }

        // DEBUG
        //var_dump($s_start, $s_end, $s, "\n");

        foreach (explode(';', $this->id) as $rule) {
            $rule_orig_trim = trim(
                str_ireplace(
                    array('Nonspacing Mark'),
                    array('NonspacingMark'),
                    rtrim($rule, ' ()')
                )

            );
            $rule = trim(
                str_ireplace(
                    array('/BGN', 'ANY-', ' '),
                    '',
                    strtoupper($rule_orig_trim)
                )
            );

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
            } elseif ('DE-ASCII' === $rule) {
                $s = self::to_ascii($s, 'de');
            } elseif ('LATIN-ASCII' === $rule) {
                $s = self::to_ascii($s);
            } elseif (false !== strpos($rule, 'UPPER')) {
                $s = mb_strtoupper($s);
            } elseif (false !== strpos($rule, 'LOWER')) {
                $s = mb_strtolower($s);
            } elseif (false !== strpos($rule, 'LATIN')) {
                $s = self::to_translit($s);
            } elseif ($lang = array_search($rule, self::$LOCALE_TO_TRANSLITERATOR_ID)) {
                $s = self::to_ascii($s, $lang);
            } elseif (
                false !== strpos($rule, '-ASCII')
                &&
                $lang = str_replace('-ASCII', '', $rule)
            ) {
                $s = self::to_ascii($s, $lang);
            }

            if (
                false !== strpos($rule, '[')
                &&
                false !== strpos($rule, ']')
            ) {
                $rule_regex_orig_trim_tmp = $rule_orig_trim;
                $rule_regex_extra_helper = array();
                preg_match('/[^]+]+$/', $rule_orig_trim, $rule_regex_extra_helper);
                $rule_regex_extra = isset($rule_regex_extra_helper[0]) ? $rule_regex_extra_helper[0] : '';

                if (stripos($rule_regex_orig_trim_tmp, '[:NONSPACINGMARK:]') !== false) {
                    $rule_regex_orig_trim_tmp = str_ireplace('[:NONSPACINGMARK:]', '\p{Mn}+', $rule);
                }

                $space_regex_found = false;
                if (stripos($rule_regex_orig_trim_tmp, '[[:SPACE:]]') !== false) {
                    $space_regex_found = true;
                    $rule_regex_orig_trim_tmp = str_ireplace('[[:SPACE:]]', '[[:space:]]', $rule_regex_orig_trim_tmp);
                }
                if (stripos($rule_regex_orig_trim_tmp, '][:SPACE:]') !== false) {
                    $space_regex_found = true;
                    $rule_regex_orig_trim_tmp = str_ireplace('][:SPACE:]', '][:space:]', $rule_regex_orig_trim_tmp);
                }
                if (stripos($rule_regex_orig_trim_tmp, '[:SPACE:][') !== false) {
                    $space_regex_found = true;
                    $rule_regex_orig_trim_tmp = str_ireplace('[:SPACE:][', '[:space:][', $rule_regex_orig_trim_tmp);
                }
                if (
                    $space_regex_found === false
                    &&
                    stripos($rule_regex_orig_trim_tmp, '[:SPACE:]') !== false
                ) {
                    $rule_regex_orig_trim_tmp = str_ireplace('[:SPACE:]', '[[:space:]]', $rule_regex_orig_trim_tmp);
                }

                $punct_regex_found = false;
                if (stripos($rule_regex_orig_trim_tmp, '[[:PUNCTUATION:]]') !== false) {
                    $punct_regex_found = true;
                    $rule_regex_orig_trim_tmp = str_ireplace('[[:PUNCTUATION:]]', '[[:punct:]]', $rule_regex_orig_trim_tmp);
                }
                if (stripos($rule_regex_orig_trim_tmp, '][:PUNCTUATION:]') !== false) {
                    $punct_regex_found = true;
                    $rule_regex_orig_trim_tmp = str_ireplace('][:PUNCTUATION:]', '][:punct:]', $rule_regex_orig_trim_tmp);
                }
                if (stripos($rule_regex_orig_trim_tmp, '[:PUNCTUATION:][') !== false) {
                    $punct_regex_found = true;
                    $rule_regex_orig_trim_tmp = str_ireplace('[:PUNCTUATION:][', '[:punct:][', $rule_regex_orig_trim_tmp);
                }
                if (
                    $punct_regex_found === false
                    &&
                    stripos($rule_regex_orig_trim_tmp, '[:PUNCTUATION:]') !== false
                ) {
                    $rule_regex_orig_trim_tmp = str_ireplace('[:PUNCTUATION:]', '[[:punct:]]', $rule_regex_orig_trim_tmp);
                }

                if (
                    false !== strpos($rule_regex_orig_trim_tmp, '[')
                    &&
                    false !== strpos($rule_regex_orig_trim_tmp, ']')
                ) {
                    $rule_regex = preg_replace('/[^]+]+$/', '', $rule_regex_orig_trim_tmp);
                } elseif (false !== stripos($rule_regex_orig_trim_tmp, 'REMOVE')) {
                    $rule_regex = str_ireplace('REMOVE', '', $rule_regex_orig_trim_tmp);
                } else {
                    $rule_regex = '';
                }

                // DEBUG
                //var_dump($rule_regex);

                if (
                    $rule_regex
                    &&
                    preg_match('/' . $rule_regex . '/u', $s)
                ) {
                    if (stripos($rule_regex_extra, 'REMOVE') !== false) {
                        $s = preg_replace('/' . $rule_regex . '/u', '', $s);
                    } elseif (strpos($rule_regex_extra, '>') !== false) {
                        $rule_regex_extra = str_replace('>', '', $rule_regex_extra);
                        $rule_regex_extra_replacement_helper = array();
                        preg_match('/\'(?<replacement>.*?)\'/', $rule_regex_extra, $rule_regex_extra_replacement_helper);
                        $rule_regex_extra_replacement = isset($rule_regex_extra_replacement_helper['replacement']) ? $rule_regex_extra_replacement_helper['replacement'] : '';
                        
                        $s = preg_replace('/' . $rule_regex . '/u', $rule_regex_extra_replacement, $s);
                    }
                }
            } elseif (strpos($rule, '<>') !== false) {
                $rule_replacer_helper = array();
                preg_match('/(?<search>.*)\s*<>\s*(?<replace>.*)/', $rule_orig_trim, $rule_replacer_helper);

                if (isset($rule_replacer_helper['search'])) {
                    $s = str_replace(
                        trim($rule_replacer_helper['search']),
                        isset($rule_replacer_helper['replace']) ? trim($rule_replacer_helper['replace']) : '',
                        $s
                    );
                }
            }
        }

        return $s_start . $s . $s_end;
    }

    private static function to_translit($s)
    {
        if (self::$TRANSLIT === null) {
            self::$TRANSLIT = self::getData('translit');
        }

        return str_replace(self::$TRANSLIT['orig'], self::$TRANSLIT['replace'], $s);
    }

    public function getErrorCode()
    {
        return 0;
    }

    public function getErrorMessage()
    {
        return '';
    }

    /**
     * Returns an replacement array for ASCII methods with one language.
     *
     * For example, German will map 'ä' to 'ae', while other languages
     * will simply return e.g. 'a'.
     *
     * @psalm-suppress InvalidNullableReturnType - we use the prepare* methods here, so we don't get NULL here
     *
     * @param string $language [optional] <p>Language of the source string e.g.: en, de_at, or de-ch.
     *                         (default is 'en') | ASCII::*_LANGUAGE_CODE</p>
     *
     * @return array{orig: string[], replace: string[]}
     *                     <p>An array of replacements.</p>
     */
    private static function charsArrayWithOneLanguage($language = 'en')
    {
        $language = self::get_language($language);

        // init
        static $CHARS_ARRAY = array();

        // check static cache
        if (isset($CHARS_ARRAY[$language])) {
            return $CHARS_ARRAY[$language];
        }

        self::prepareAsciiMaps();

        if (isset(self::$ASCII_MAPS[$language])) {
            $tmpArray = self::$ASCII_MAPS[$language];

            $CHARS_ARRAY[$language] = array(
                'orig' => array_keys($tmpArray),
                'replace' => array_values($tmpArray),
            );
        } else {
            $CHARS_ARRAY[$language] = array(
                'orig' => array(),
                'replace' => array(),
            );
        }

        return $CHARS_ARRAY[$language];
    }

    /**
     * Returns an replacement array for ASCII methods with multiple languages.
     *
     * @return array{orig: string[], replace: string[]}
     *                     <p>An array of replacements.</p>
     */
    private static function charsArrayWithSingleLanguageValues()
    {
        // init
        static $CHARS_ARRAY = null;

        if (isset($CHARS_ARRAY)) {
            return $CHARS_ARRAY;
        }

        self::prepareAsciiMaps();

        /* @noinspection AlterInForeachInspection */
        /* @psalm-suppress PossiblyNullIterator - we use the prepare* methods here, so we don't get NULL here */
        foreach (self::$ASCII_MAPS as &$map) {
            $CHARS_ARRAY[] = $map;
        }

        $CHARS_ARRAY = \call_user_func_array('array_merge', $CHARS_ARRAY + array());

        $CHARS_ARRAY = array(
            'orig' => array_keys($CHARS_ARRAY),
            'replace' => array_values($CHARS_ARRAY),
        );

        return $CHARS_ARRAY;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * by default. The language or locale of the source string can be supplied
     * for language-specific transliteration in any of the following formats:
     * en, en_GB, or en-GB. For example, passing "de" results in "äöü" mapping
     * to "aeoeue" rather than "aou" as in other languages.
     *
     * @param string $s        <p>The input string.</p>
     * @param string $language [optional] <p>Language of the source string.
     *                         (default is 'XXX') | ASCII::*_LANGUAGE_CODE</p>
     *
     * @return string
     *                <p>A string that contains only ASCII characters.</p>
     */
    private static function to_ascii($s, $language = 'XXX')
    {
        if ('' === $s) {
            return '';
        }

        $language = self::get_language($language);

        $language_specific_chars = self::charsArrayWithOneLanguage($language);
        if (!empty($language_specific_chars['orig'])) {
            $s = str_replace($language_specific_chars['orig'], $language_specific_chars['replace'], $s);
        }

        $language_all_chars = self::charsArrayWithSingleLanguageValues();
        $s = str_replace($language_all_chars['orig'], $language_all_chars['replace'], $s);

        /* @psalm-suppress PossiblyNullOperand - we use the prepare* methods here, so we don't get NULL here */
        if (!isset(self::$ASCII_MAPS[$language])) {
            $use_transliterate = true;
        } else {
            $use_transliterate = false;
        }

        if (true === $use_transliterate) {
            $s = self::to_transliterate($s, null);
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
        if (\strlen($s) === strspn($s, self::$ASCII)) {
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
        if ('' === $language) {
            return '';
        }

        if (
            false === strpos($language, '_')
            &&
            false === strpos($language, '-')
        ) {
            return strtolower($language);
        }

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
        return include __DIR__ . '/Resources/unidata/' . $file . '.php';
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
        $file = __DIR__ . '/Resources/unidata/' . $file . '.php';
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
    private static function prepareAsciiMaps()
    {
        if (null === self::$ASCII_MAPS) {
            self::$ASCII_MAPS = self::getData('ascii_by_languages');
        }
    }
}
