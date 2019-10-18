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
 * - create          - Opens a Transliterator by id. (WARNING: Transliterator::REVERSE is not implemented)
 * - createFromRules - Create Transliterator from rules. (WARNING: Transliterator::REVERSE is not implemented)
 * - transliterate   - Transforms a string or part thereof using an ICU transliterator.
 *
 * @author Lars Moelleken <lars@moelleken.org>
 *
 * @internal
 *
 * @property-read string $id
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

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var int
     */
    private $direction = \Transliterator::FORWARD;

    private function __construct()
    {
    }

    public function __get($name)
    {
        if ('id' === $name) {
            return $this->id;
        }

        return null;
    }

    public function __isset($name)
    {
        if ('id' === $name) {
            return isset($this->id);
        }

        return false;
    }

    public function __set($name, $value)
    {
        if ('id' === $name) {
            trigger_error('The property "id" is read-only', E_USER_WARNING);

            return;
        }

        if ('id_intern' === $name) {
            $this->id = $value;
        }
    }

    private static function charsArrayWithOneLanguage($language = '')
    {
        $language = self::getLanguage($language);

        static $CHARS_ARRAY = array();

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

    private static function charsArrayWithSingleLanguageValues()
    {
        static $CHARS_ARRAY = null;

        if (isset($CHARS_ARRAY)) {
            return $CHARS_ARRAY;
        }

        self::prepareAsciiMaps();

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

    private static function cleanId($s)
    {
        return rtrim($s, ';');
    }

    public static function create($id, $direction = \Transliterator::FORWARD)
    {
        if (
            false !== strpos($id, '<')
            ||
            false !== strpos($id, '>')
            ||
            false !== strpos($id, '::')
        ) {
            return null;
        }

        $transliterator = new self();

        $transliterator->id_intern = self::cleanId($id);

        if (\Transliterator::FORWARD !== $direction && null !== $direction) {
            throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator->direction".'));
        }

        return $transliterator;
    }

    public static function createFromRules($rules, $direction = \Transliterator::FORWARD)
    {
        if (
            false === strpos($rules, '<')
            &&
            false === strpos($rules, '>')
            &&
            false === strpos($rules, '::')
        ) {
            return null;
        }

        $transliterator = new self();

        $transliterator->id_intern = self::cleanId($rules);

        if (\Transliterator::FORWARD !== $direction && null !== $direction) {
            throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator->direction".'));
        }

        return $transliterator;
    }

    public static function createInverse()
    {
        throw new \DomainException(sprintf('The PHP intl extension is required for using "Transliterator::createInverse".'));
    }

    private static function getData($file)
    {
        return include __DIR__.'/Resources/unidata/'.$file.'.php';
    }

    private static function getDataIfExists($file)
    {
        $file = __DIR__.'/Resources/unidata/'.$file.'.php';
        if (file_exists($file)) {
            return include $file;
        }

        return array();
    }

    public function getErrorCode()
    {
        return 0;
    }

    public function getErrorMessage()
    {
        return '';
    }

    private static function getLanguage($language)
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

    public static function listIDs()
    {
        return array_values(self::$LOCALE_TO_TRANSLITERATOR_ID);
    }

    private function normalizeRegex($rule)
    {
        if (false !== stripos($rule, '[:NONSPACINGMARK:]')) {
            if (preg_match('/\[(.*?\[:NONSPACINGMARK:\].*?)\]/i', $rule)) {
                $rule = str_ireplace('[:NONSPACINGMARK:]', '\p{Mn}', $rule);
            } else {
                $rule = str_ireplace('[:NONSPACINGMARK:]', '[\p{Mn}]+', $rule);
            }
        }

        if (false !== stripos($rule, '[:SPACESEPARATOR:]')) {
            if (preg_match('/\[(.*?\[:SPACESEPARATOR:\].*?)\]/i', $rule)) {
                $rule = str_ireplace('[:SPACESEPARATOR:]', '\s', $rule);
            } else {
                $rule = str_ireplace('[:SPACESEPARATOR:]', '[\s]+', $rule);
            }
        }

        $space_regex_found = false;
        if (false !== stripos($rule, '[[:SPACE:]]')) {
            $space_regex_found = true;
            $rule = str_ireplace('[[:SPACE:]]', '[[:space:]]', $rule);
        }
        if (false !== stripos($rule, '][:SPACE:]')) {
            $space_regex_found = true;
            $rule = str_ireplace('][:SPACE:]', '][:space:]', $rule);
        }
        if (false !== stripos($rule, '[:SPACE:][')) {
            $space_regex_found = true;
            $rule = str_ireplace('[:SPACE:][', '[:space:][', $rule);
        }
        if (
            false === $space_regex_found
            &&
            false !== stripos($rule, '[:SPACE:]')
        ) {
            $rule = str_ireplace('[:SPACE:]', '[[:space:]]', $rule);
        }

        $punct_regex_found = false;
        if (false !== stripos($rule, '[[:PUNCTUATION:]]')) {
            $punct_regex_found = true;
            $rule = str_ireplace('[[:PUNCTUATION:]]', '[[:punct:]]', $rule);
        }
        if (false !== stripos($rule, '][:PUNCTUATION:]')) {
            $punct_regex_found = true;
            $rule = str_ireplace('][:PUNCTUATION:]', '][:punct:]', $rule);
        }
        if (false !== stripos($rule, '[:PUNCTUATION:][')) {
            $punct_regex_found = true;
            $rule = str_ireplace('[:PUNCTUATION:][', '[:punct:][', $rule);
        }
        if (
            false === $punct_regex_found
            &&
            false !== stripos($rule, '[:PUNCTUATION:]')
        ) {
            $rule = str_ireplace('[:PUNCTUATION:]', '[[:punct:]]', $rule);
        }

        return $rule;
    }

    private static function prepareAsciiMaps()
    {
        if (null === self::$ASCII_MAPS) {
            self::$ASCII_MAPS = self::getData('ascii_by_languages');
        }
    }

    private static function toAscii($s, $language = '')
    {
        if ('' === $s) {
            return '';
        }

        $language = self::getLanguage($language);

        $language_specific_chars = self::charsArrayWithOneLanguage($language);
        if (!empty($language_specific_chars['orig'])) {
            $s = str_replace($language_specific_chars['orig'], $language_specific_chars['replace'], $s);
        }

        $language_all_chars = self::charsArrayWithSingleLanguageValues();
        $s = str_replace($language_all_chars['orig'], $language_all_chars['replace'], $s);

        if (!isset(self::$ASCII_MAPS[$language])) {
            $use_transliterate = true;
        } else {
            $use_transliterate = false;
        }

        if (true === $use_transliterate) {
            $s = self::toTransliterate($s);
        }

        return $s;
    }

    private static function toTranslit($s)
    {
        if ('' === $s) {
            return '';
        }

        if (null === self::$TRANSLIT) {
            self::$TRANSLIT = self::getData('translit');
        }

        return str_replace(self::$TRANSLIT['orig'], self::$TRANSLIT['replace'], $s);
    }

    private static function toTransliterate($s)
    {
        static $UTF8_TO_TRANSLIT = null;
        static $TRANSLITERATOR = null;

        if ('' === $s) {
            return '';
        }

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
                $s_tmp .= $c;

                continue;
            }

            $bank = $ord >> 8;
            if (!isset($UTF8_TO_TRANSLIT[$bank])) {
                $UTF8_TO_TRANSLIT[$bank] = self::getDataIfExists(sprintf('x%02x', $bank));
            }

            $new_char = $ord & 255;

            if (isset($UTF8_TO_TRANSLIT[$bank][$new_char])) {
                $new_char = $UTF8_TO_TRANSLIT[$bank][$new_char];
                if (
                    '' !== $new_char
                    &&
                    '[?]' !== $new_char
                    &&
                    '[?] ' !== $new_char
                ) {
                    $c = $new_char;
                }
            }

            $s_tmp .= $c;
        }

        return $s_tmp;
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

        foreach (explode(';', $this->id) as $rule) {
            $rule_orig_trim = trim(
                str_ireplace(
                    array('Nonspacing Mark', 'Space Separator'),
                    array('NonspacingMark', 'SpaceSeparator'),
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

            $regex_rule_found = false;

            if (
                false !== strpos($rule, '[')
                &&
                false !== strpos($rule, ']')
            ) {
                $regex_rule_found = true;
            } elseif ('REMOVE' === $rule) {
                $s = '';
            } elseif ('NFC' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_C) ?: $s = normalizer_normalize($s, \Normalizer::NFC);
            } elseif ('NFD' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_D) ?: $s = normalizer_normalize($s, \Normalizer::NFD);
            } elseif ('NFKD' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_KD) ?: $s = normalizer_normalize($s, \Normalizer::NFKD);
            } elseif ('NFKC' === $rule) {
                normalizer_is_normalized($s, \Normalizer::FORM_KC) ?: $s = normalizer_normalize($s, \Normalizer::NFKC);
            } elseif ('DE-ASCII' === $rule) {
                $s = self::toAscii($s, 'de');
            } elseif ('LATIN-ASCII' === $rule) {
                $s = self::toAscii($s);
            } elseif ('UPPER' === $rule) {
                $s = mb_strtoupper($s);
            } elseif ('LOWER' === $rule) {
                $s = mb_strtolower($s);
            } elseif (false !== strpos($rule, 'LATIN')) {
                $s = self::toTranslit($s);
            } elseif ($lang = array_search($rule, self::$LOCALE_TO_TRANSLITERATOR_ID)) {
                $s = self::toAscii($s, $lang);
            } elseif (
                false !== strpos($rule, '-ASCII')
                &&
                $lang = str_replace('-ASCII', '', $rule)
            ) {
                $s = self::toAscii($s, $lang);
            }

            if (true === $regex_rule_found) {
                $rule_regex_extra_helper = array();
                preg_match('/[^]+]+$/', $rule_orig_trim, $rule_regex_extra_helper);
                $rule_regex_extra = isset($rule_regex_extra_helper[0]) ? $rule_regex_extra_helper[0] : '';

                $rule_regex = trim(
                    str_replace(
                        '\u',
                        '\\\u',
                        preg_replace(
                            '/[^]+]+$/',
                            '',
                            $this->normalizeRegex($rule_orig_trim)
                        )
                    ),
                    ' :'
                );

                /* @noinspection PhpUsageOfSilenceOperatorInspection */
                if (
                    $rule_regex
                    &&
                    false !== @preg_match('/'.$rule_regex.'/u', null)
                ) {
                    if (false !== strpos($rule_regex_extra, '>')) {
                        $rule_regex_extra = str_replace('>', '', $rule_regex_extra);
                        $rule_regex_extra_replacement_helper = array();
                        preg_match('/\'(?<replacement>.*?)\'/', $rule_regex_extra, $rule_regex_extra_replacement_helper);
                        $rule_regex_extra_replacement = isset($rule_regex_extra_replacement_helper['replacement']) ? $rule_regex_extra_replacement_helper['replacement'] : '';

                        $s = preg_replace('/'.$rule_regex.'/u', $rule_regex_extra_replacement, $s);
                    } else {
                        $that = clone $this;
                        $that->id_intern = $rule_regex_extra;
                        $s = preg_replace_callback(
                            '/'.$rule_regex.'/u',
                            function ($callback_matches) use ($that) {
                                return $that->transliterate($callback_matches[0]);
                            },
                            $s
                        );
                        unset($that);
                    }
                }
            } elseif (false !== strpos($rule, '>')) {
                $rule_replacer_helper = array();
                preg_match('/(?<search>.*)\s*<>\s*(?<replace>.*)/', $rule_orig_trim, $rule_replacer_helper);
                if ($rule_replacer_helper === array()) {
                    preg_match('/(?<search>.*)\s*>\s*(?<replace>.*)/', $rule_orig_trim, $rule_replacer_helper);
                }

                if (isset($rule_replacer_helper['search'])) {
                    $s = str_replace(
                        trim($rule_replacer_helper['search']),
                        isset($rule_replacer_helper['replace']) ? trim($rule_replacer_helper['replace']) : '',
                        $s
                    );
                }
            }
        }

        return $s_start.$s.$s_end;
    }
}
