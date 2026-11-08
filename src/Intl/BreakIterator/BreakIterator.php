<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\BreakIterator;

use Symfony\Polyfill\Intl\Grapheme\Grapheme;

/**
 * Partial implementation of the IntlBreakIterator class provided by the intl extension.
 *
 * Word boundaries follow the default rules of UAX #29 (https://unicode.org/reports/tr29/),
 * without the dictionaries ICU uses for Chinese, Japanese, Thai, Lao, Khmer and Myanmar.
 * Character boundaries use PCRE's \X, or the pattern of symfony/polyfill-intl-grapheme when available.
 * Offsets are UTF-8 byte offsets, as with ext-intl.
 *
 * Boundaries are computed lazily, one chunk at a time, with a single PCRE pass per chunk.
 * Moving to an arbitrary offset resynchronizes at a nearby position that is always a
 * boundary, so that a call like following($offset) does not depend on the text length.
 *
 * @internal
 */
class BreakIterator implements \IteratorAggregate
{
    public const DONE = -1;
    public const WORD_NONE = 0;
    public const WORD_NONE_LIMIT = 100;
    public const WORD_NUMBER = 100;
    public const WORD_NUMBER_LIMIT = 200;
    public const WORD_LETTER = 200;
    public const WORD_LETTER_LIMIT = 300;
    public const WORD_KANA = 300;
    public const WORD_KANA_LIMIT = 400;
    public const WORD_IDEO = 400;
    public const WORD_IDEO_LIMIT = 500;
    public const LINE_SOFT = 0;
    public const LINE_SOFT_LIMIT = 100;
    public const LINE_HARD = 100;
    public const LINE_HARD_LIMIT = 200;
    public const SENTENCE_TERM = 0;
    public const SENTENCE_TERM_LIMIT = 100;
    public const SENTENCE_SEP = 100;
    public const SENTENCE_SEP_LIMIT = 200;

    private const TYPE_CODE_POINT = 0;
    private const TYPE_CHARACTER = 1;
    private const TYPE_WORD = 2;

    // Classes of chars that define the status of words
    private const CLASS_OTHER = 0;
    private const CLASS_EXTEND = 1;
    private const CLASS_EXTEND_NUM_LET = 2;
    private const CLASS_SINGLE_QUOTE = 3;
    private const CLASS_HANGUL_SYLLABLE = 4;
    private const CLASS_LETTER = 5;
    private const CLASS_NUMBER = 6;
    private const CLASS_KANA_IDEO = 7;
    private const CLASS_STATUSES = [
        -1 => self::WORD_NONE,
        self::CLASS_OTHER => self::WORD_NONE,
        self::CLASS_EXTEND => self::WORD_NONE,
        self::CLASS_EXTEND_NUM_LET => self::WORD_NONE,
        self::CLASS_SINGLE_QUOTE => self::WORD_NONE,
        self::CLASS_HANGUL_SYLLABLE => self::WORD_LETTER,
        self::CLASS_LETTER => self::WORD_LETTER,
        self::CLASS_NUMBER => self::WORD_NUMBER,
        self::CLASS_KANA_IDEO => self::WORD_IDEO,
    ];

    // Sequential iteration computes boundaries by large chunks, random access by small ones
    private const CHUNK_SIZE = 2048;
    private const SEEK_CHUNK_SIZE = 64;
    private const MAX_CACHED_BOUNDARIES = 4096;

    // Word_Break classes of the ASCII range, see Resources/unidata/wordBreak.php
    private const ASCII_CLASSES = [
        'A' => 'A-Za-z',
        'L' => 'A-Za-z',
        'H' => '',
        'N' => '0-9',
        'K' => '',
        'X' => '_',
        'MidA' => '\x27.:',
        'MidN' => '\x27,.;',
        'SQ' => '\x27',
        'DQ' => '"',
        'WS' => '\x20',
        'NL' => '\x0B\x0C',
        'RI' => '',
        'E' => '',
        'EP' => '',
        'Ideo' => '',
        'SA' => '',
        'Hira' => '',
        'HS' => '',
    ];

    // Positions that are always a boundary, used to compute boundaries from the middle of the text:
    // - code points start at any byte that is not a continuation byte;
    // - characters break after a line feed, and between two ASCII chars except CR LF;
    // - words break after a line feed, between a space and a printable ASCII char, and before the chars
    //   whose Word_Break is Other and that are not Extended_Pictographic: ASCII ones, CJK ideographs
    //   (U+4E00-U+9FFF), Hiragana letters (U+3041-U+3096) and Thai consonants (U+0E01-U+0E2E).
    private const SAFE_RX = [
        self::TYPE_CODE_POINT => '/(?=[\x00-\x7F\xC0-\xFF])/',
        self::TYPE_CHARACTER => '/\n\K|(?<=[\x00-\x7F])(?=[\x00-\x7F])(?!(?<=\r)\n)/',
        self::TYPE_WORD => '/\n\K| \K(?=[!-~])|(?=[\x00-\x09\x0E-\x1F!#-&(-+\-\/<-@\[-^`{-~\x7F]|\xE4[\xB8-\xBF]|[\xE5-\xE9]|\xE3\x81[\x81-\xBF]|\xE3\x82[\x80-\x96]|\xE0\xB8[\x81-\xAE])/',
    ];

    private const UTF8_CHAR_OR_SUBPART_RX = '/[\x00-\x7F]++|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|[\xEE-\xEF][\x80-\xBF]{2}|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}|(\xF0[\x90-\xBF][\x80-\xBF]?|[\xF1-\xF3][\x80-\xBF]{1,2}|\xF4[\x80-\x8F][\x80-\xBF]?|\xE0[\xA0-\xBF]|[\xE1-\xEC][\x80-\xBF]|\xED[\x80-\x9F]|[\xEE-\xEF][\x80-\xBF]|[\x80-\xFF])/';

    // Same-length replacements for ill-formed subsequences, all with Word_Break=Other and Grapheme_Cluster_Break=Other like U+FFFD
    private const REPLACEMENT_CHARS = [1 => '#', 2 => "\xC3\x97", 3 => "\xE2\x80\xA2"];

    private static $patterns = [];
    private static $charClasses = [];
    private static $charClassPattern;
    private static $unicodeClasses;

    private $type;
    private $text;
    private $scan = '';
    private $length = 0;
    private $sanitized = false;
    private $boundaries = [0];
    private $index = 0;
    private $lastCodePoint = -1;

    private function __construct()
    {
    }

    public static function createCharacterInstance($locale = null)
    {
        return self::create(self::TYPE_CHARACTER, 'RuleBasedBreakIterator', 'IntlRuleBasedBreakIterator');
    }

    public static function createCodePointInstance()
    {
        return self::create(self::TYPE_CODE_POINT, 'CodePointBreakIterator', 'IntlCodePointBreakIterator');
    }

    public static function createWordInstance($locale = null)
    {
        return self::create(self::TYPE_WORD, 'RuleBasedBreakIterator', 'IntlRuleBasedBreakIterator');
    }

    public function current()
    {
        return $this->boundaries[$this->index];
    }

    public function first()
    {
        if (0 !== $this->boundaries[0]) {
            $this->boundaries = [0];
        }
        $this->lastCodePoint = -1;

        return $this->boundaries[$this->index = 0];
    }

    public function following($offset)
    {
        if (0 > $offset = (int) $offset) {
            if (self::TYPE_CODE_POINT !== $this->type) {
                return $this->first();
            }
            $offset = 0;
        }

        if ($offset >= $this->length) {
            $this->last();

            return self::DONE;
        }

        $this->index = $this->locate($this->snap($offset) + 1);
        $this->setLastCodePoint(-1);

        return $this->boundaries[$this->index];
    }

    public function getErrorCode()
    {
        return 0;
    }

    public function getErrorMessage()
    {
        return 'U_ZERO_ERROR';
    }

    public function getLocale($type)
    {
        return 1 === (int) $type && self::TYPE_CODE_POINT !== $this->type ? 'en' : '';
    }

    public function getIterator(): \Iterator
    {
        yield 0 => $this->first();

        for ($key = 1;; ++$key) {
            if (isset($this->boundaries[$i = $this->index + 1])) {
                if (self::TYPE_CODE_POINT === $this->type) {
                    $this->lastCodePoint = $this->getCodePoint($this->boundaries[$this->index], $this->boundaries[$i]);
                }
                yield $key => $this->boundaries[$this->index = $i];
            } elseif (self::DONE !== $boundary = $this->next()) {
                yield $key => $boundary;
            } else {
                break;
            }
        }
    }

    public function getPartsIterator($type = PartsIterator::KEY_SEQUENTIAL)
    {
        return 0 === strpos(static::class, __NAMESPACE__.'\\') ? new PartsIterator($this, (int) $type) : new \IntlPartsIterator($this, (int) $type);
    }

    public function getText()
    {
        return $this->text;
    }

    public function isBoundary($offset)
    {
        $offset = (int) $offset;

        if (self::TYPE_CODE_POINT === $this->type) {
            // Unlike other moves, this one does not change the last code point
            $lastCodePoint = $this->lastCodePoint;
            $this->index = $this->locate($start = $this->snap(max(0, min($offset, $this->length))));
            $this->lastCodePoint = $lastCodePoint;

            return $offset === $start;
        }

        if ($offset < 0) {
            $this->first();

            return false;
        }

        if ($offset > $this->length) {
            $this->last();

            return false;
        }

        $this->index = $this->locate($offset);

        return $offset === $this->boundaries[$this->index];
    }

    public function last()
    {
        $n = \count($this->boundaries) - 1;

        if ($this->length !== $this->boundaries[$n]) {
            $this->boundaries = [$this->length];
            $n = 0;
        }
        $this->lastCodePoint = -1;

        return $this->boundaries[$this->index = $n];
    }

    public function next($offset = null)
    {
        if (null === $offset && isset($this->boundaries[$i = $this->index + 1])) {
            if (self::TYPE_CODE_POINT === $this->type) {
                $this->lastCodePoint = $this->getCodePoint($this->boundaries[$this->index], $this->boundaries[$i]);
            }

            return $this->boundaries[$this->index = $i];
        }

        if (null !== $offset && (1 !== ($offset = (int) $offset) || self::TYPE_CODE_POINT === $this->type)) {
            $boundary = $this->boundaries[$this->index];
            for (; 0 < $offset && self::DONE !== $boundary; --$offset) {
                $boundary = $this->next();
            }
            for (; 0 > $offset && self::DONE !== $boundary; ++$offset) {
                $boundary = $this->previous();
            }

            if (self::TYPE_CODE_POINT === $this->type) {
                // Like ICU, report the code point that follows the new position
                $end = $this->boundaries[$this->index + 1] ?? (self::DONE !== $boundary && $boundary < $this->length ? $this->fill($boundary, $boundary + 1)[1] : null);
                $this->lastCodePoint = null === $end || self::DONE === $boundary ? -1 : $this->getCodePoint($boundary, $end);
            }

            return $boundary;
        }

        if (!isset($this->boundaries[$this->index + 1])) {
            $end = $this->boundaries[$this->index];

            if ($end === $this->length) {
                $this->lastCodePoint = -1;

                return self::DONE;
            }

            $this->append($this->fill($end, $end + 1, $this->getChunkSize()));
        }

        ++$this->index;
        $this->setLastCodePoint(-1);

        return $this->boundaries[$this->index];
    }

    public function preceding($offset)
    {
        if ($this->length < $offset = (int) $offset) {
            if (self::TYPE_CODE_POINT !== $this->type) {
                return $this->last();
            }
            $offset = $this->length;
        }

        if (0 >= $offset = $this->snap($offset)) {
            $this->first();

            return self::DONE;
        }

        $this->index = $this->locate($offset);

        if (0 === $this->index) {
            $this->prepend();
        }

        --$this->index;
        $this->setLastCodePoint(1);

        return $this->boundaries[$this->index];
    }

    public function previous()
    {
        if (0 === $this->index) {
            if (0 === $this->boundaries[0]) {
                $this->lastCodePoint = -1;

                return self::DONE;
            }

            $this->prepend($this->getChunkSize());
        }

        --$this->index;
        $this->setLastCodePoint(1);

        return $this->boundaries[$this->index];
    }

    public function setText(string $text)
    {
        $this->text = $text;
        $this->length = \strlen($text);
        $this->boundaries = [0];
        $this->index = 0;
        $this->lastCodePoint = -1;

        $this->scan = $text;
        $this->sanitized = false;

        return true;
    }

    /**
     * @internal
     */
    protected function getLastCodePointInternal()
    {
        return $this->lastCodePoint;
    }

    /**
     * @internal
     */
    protected function getRuleStatusInternal()
    {
        if (self::TYPE_WORD !== $this->type || 0 === $end = $this->boundaries[$this->index]) {
            return 0;
        }

        if (0 === $this->index) {
            $this->prepend();
        }

        // Like ICU, the status follows the last char before an optional ExtendNumLet or Single_Quote, two ExtendNumLet
        // are a letter, and an ExtendNumLet or a Hangul syllable followed by Extend, Format or ZWJ is not a word
        $start = $this->boundaries[$this->index - 1];
        $class = $this->popCharClass($start, $end);

        if (self::CLASS_EXTEND === $class) {
            do {
                $class = $this->popCharClass($start, $end);
            } while (self::CLASS_EXTEND === $class);

            return self::CLASS_HANGUL_SYLLABLE === $class ? self::WORD_NONE : self::CLASS_STATUSES[$class];
        }

        if (self::CLASS_EXTEND_NUM_LET === $class || self::CLASS_SINGLE_QUOTE === $class) {
            $last = $class;
            do {
                $class = $this->popCharClass($start, $end);
            } while (self::CLASS_EXTEND === $class);

            if (self::CLASS_EXTEND_NUM_LET === $class && self::CLASS_EXTEND_NUM_LET === $last) {
                return self::WORD_LETTER;
            }
        }

        return self::CLASS_STATUSES[$class];
    }

    /**
     * Returns the class of the char that ends at $end, or -1 when $end is $start, and moves $end to the start of the char.
     */
    private function popCharClass(int $start, int &$end): int
    {
        if ($end <= $start) {
            return -1;
        }

        $i = $end - 1;
        while ($i > $start && 0x80 === (\ord($this->scan[$i]) & 0xC0)) {
            --$i;
        }
        $char = substr($this->scan, $i, $end - $i);
        $end = $i;

        if (isset(self::$charClasses[$char])) {
            return self::$charClasses[$char];
        }

        if (null === self::$charClassPattern) {
            $c = self::getUnicodeClasses();
            // The groups are numbered like the CLASS_* constants
            self::$charClassPattern = '/^(?:(['.$c['E'].'])|(['.$c['X'].'])|(['.$c['SQ'].'])|(['.$c['HS'].'])|(['.$c['A'].$c['SA'].'])|(['.$c['N'].'])|(['.$c['K'].$c['Ideo'].$c['Hira'].']))/u';
        }

        // Classes are cached by char, as matching large Unicode classes is slow
        if (4096 < \count(self::$charClasses)) {
            self::$charClasses = [];
        }
        preg_match(self::$charClassPattern, $char, $m);

        return self::$charClasses[$char] = max(self::CLASS_OTHER, \count($m) - 1);
    }

    private static function create($type, $class, $globalClass)
    {
        $class = 0 === strpos(static::class, __NAMESPACE__.'\\') ? __NAMESPACE__.'\\'.$class : $globalClass;
        $iterator = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $iterator->type = $type;

        return $iterator;
    }

    private static function getUnicodeClasses()
    {
        return self::$unicodeClasses ?? self::$unicodeClasses = require __DIR__.'/Resources/unidata/wordBreak.php';
    }

    private function getPattern(bool $utf8): string
    {
        $key = $this->type.($utf8 ? 'u' : '');

        if (isset(self::$patterns[$key])) {
            return self::$patterns[$key];
        }

        if (self::TYPE_CODE_POINT === $this->type) {
            return self::$patterns[$key] = $utf8 ? '/./su' : '/./s';
        }

        if (self::TYPE_CHARACTER === $this->type) {
            // Before PCRE 10.44, \X misses some rules; symfony/polyfill-intl-grapheme provides a pattern that implements them
            return self::$patterns[$key] = $utf8 ? '/'.(class_exists(Grapheme::class) ? \SYMFONY_GRAPHEME_CLUSTER_RX : '\X').'/u' : '/\r\n|./s';
        }

        if ($utf8) {
            // The largest classes are defined once and referenced as subroutines, to keep the compiled pattern under
            // the 64 KB limit of PCRE builds with the default link size; smaller classes are inlined, which is faster
            $c = self::getUnicodeClasses();
            $define = '';
            foreach (['A', 'L', 'E'] as $name) {
                $define .= '(?<'.$name.'>['.$c[$name].'])';
            }
            $define = '(?(DEFINE)'.$define.')';
            $k = static function (...$names) use ($c) {
                foreach ($names as $i => $name) {
                    $names[$i] = \in_array($name, ['A', 'L', 'E'], true) ? '(?&'.$name.')' : '['.$c[$name].']';
                }

                return 1 === \count($names) ? $names[0] : '(?:'.implode('|', $names).')';
            };
            $e = '(?&E)*+';
        } else {
            $c = self::ASCII_CLASSES;
            $define = '';
            $k = static function (...$names) use ($c) {
                $class = '';
                foreach ($names as $name) {
                    $class .= $c[$name];
                }

                return '' === $class ? '(?!)' : '['.$class.']';
            };
            $e = '';
        }
        $run = static function ($name) use ($k, $e) {
            return '' === $e ? $k($name).'++' : '(?:'.$k($name).$e.')++';
        };

        // UAX #29 word boundary rules; $e implements WB4 by attaching Extend, Format and ZWJ to the previous char.
        // A word is a sequence of runs that may follow each other, then a last run.
        $rx = '\r\n|[\r\n'.$c['NL'].']'                                                         // WB3, WB3a, WB3b
            .'|(?:'.$k('WS').'++'.$e                                                               // WB3d
            .'|(?:'
                .'(?:'.$run('L').'(?:'.$k('MidA').$e.'(?='.$k('A').'))?'                            // WB5, WB6, WB7
                .'|'.$k('H').$e.'(?:'.$k('MidA').$e.'(?='.$k('A').')|'.$k('DQ').$e.'(?='.$k('H').'))?' // WB7b, WB7c
                .'|'.$run('N').'(?:'.$k('MidN').$e.'(?='.$k('N').'))?'                              // WB8, WB11, WB12
                .')(?='.$k('A', 'N', 'X').')'                                                       // WB9, WB10, WB13a
                .'|'.$run('X').'(?='.$k('A', 'N', 'K').')'                                          // WB13a, WB13b
                .'|'.$run('K').'(?='.$k('X').')'                                                    // WB13, WB13a
            .')*+(?:'.$k('H').$e.'(?:'.$k('SQ').$e.')?|'.$run('L').'|'.$run('N').'|'.$run('K').'|'.$run('X').')' // WB7a
            .'|'.$k('RI').$e.'(?:'.$k('RI').$e.')?'                                                // WB15, WB16
            .'|'.$k('HS').'++'.$e                                                                   // Hangul syllables, like ICU
            .'|.'.$e                                                                                // WB999
            .')';

        if ($utf8) {
            $rx .= '(?:(?<=\x{200D})(?='.$k('EP').')(?R))?';                                         // WB3c
        }

        return self::$patterns[$key] = '/'.$define.'(?:'.$rx.')/s'.($utf8 ? 'u' : '');
    }

    /**
     * Computes the boundaries from $from, which must be a boundary, up to the first one at or after $to.
     */
    private function fill(int $from, int $to, int $chunkSize = self::CHUNK_SIZE): array
    {
        $boundaries = [$from];

        while ($from < $to) {
            $end = $this->findSafeAfter($from + $chunkSize);
            $chunk = substr($this->scan, $from, $end - $from);

            // Chunks without any non-ASCII byte use a smaller pattern without the "u" modifier
            if (!preg_match_all($this->getPattern((bool) preg_match('/[\x80-\xFF]/', $chunk)), $chunk, $m)) {
                if ($this->sanitized) {
                    throw new \RuntimeException('Cannot compute text boundaries: PCRE error '.preg_last_error().'.');
                }

                // Replace ill-formed subsequences with valid chars of the same length,
                // so that each of them is a single unit, like U+FFFD is for ICU
                $this->scan = preg_replace_callback(self::UTF8_CHAR_OR_SUBPART_RX, static function ($m) {
                    return isset($m[1]) ? self::REPLACEMENT_CHARS[\strlen($m[1])] : $m[0];
                }, $this->scan);
                $this->sanitized = true;

                continue;
            }

            foreach ($m[0] as $segment) {
                $boundaries[] = $from += \strlen($segment);
            }
        }

        return $boundaries;
    }

    private function findSafeAfter(int $offset): int
    {
        if ($offset >= $this->length || !preg_match(self::SAFE_RX[$this->type], $this->scan, $m, \PREG_OFFSET_CAPTURE, $offset)) {
            return $this->length;
        }

        return $m[0][1];
    }

    private function findSafeBefore(int $offset): int
    {
        for ($window = 16;; $window <<= 2) {
            $start = max(0, $offset - $window);

            if (preg_match_all(self::SAFE_RX[$this->type], substr($this->scan, $start, $offset - $start + 4), $m, \PREG_OFFSET_CAPTURE)) {
                for ($i = \count($m[0]) - 1; 0 <= $i; --$i) {
                    $safe = $start + $m[0][$i][1];

                    if ($safe <= $offset && ($safe > $start || 0 === $start)) {
                        return $safe;
                    }
                }
            }

            if (0 === $start) {
                return 0;
            }
        }
    }

    /**
     * Makes the cached boundaries cover $offset and returns the index of the first boundary at or after it.
     */
    private function locate(int $offset): int
    {
        $b = $this->boundaries;
        $n = \count($b) - 1;

        if ($offset < $b[0] || $offset > $b[$n] + self::CHUNK_SIZE) {
            $this->boundaries = $b = $this->fill($this->findSafeBefore($offset), $offset, self::SEEK_CHUNK_SIZE);
            $n = \count($b) - 1;
        } elseif ($offset > $b[$n]) {
            $this->append($this->fill($b[$n], $offset, self::SEEK_CHUNK_SIZE));
            $b = $this->boundaries;
            $n = \count($b) - 1;
        }

        for ($lo = 0, $hi = $n; $lo < $hi;) {
            $mid = ($lo + $hi) >> 1;

            if ($b[$mid] < $offset) {
                $lo = $mid + 1;
            } else {
                $hi = $mid;
            }
        }

        return $lo;
    }

    private function append(array $boundaries)
    {
        unset($boundaries[0]);

        foreach ($boundaries as $boundary) {
            $this->boundaries[] = $boundary;
        }

        // Drop the oldest boundaries, but keep the current one and the one before it
        if (self::MAX_CACHED_BOUNDARIES < $n = \count($this->boundaries) - self::MAX_CACHED_BOUNDARIES) {
            if (0 < $n = min($n, $this->index - 1)) {
                $this->boundaries = \array_slice($this->boundaries, $n);
                $this->index -= $n;
            }
        }
    }

    /**
     * Grows chunks with the span of the cached boundaries, so that a single move stays cheap
     * after a random access, while iterations use large chunks.
     */
    private function getChunkSize(): int
    {
        return min(self::CHUNK_SIZE, max(self::SEEK_CHUNK_SIZE, $this->boundaries[\count($this->boundaries) - 1] - $this->boundaries[0]));
    }

    /**
     * Computes the boundaries that precede the first cached one, from about $chunkSize bytes before it.
     */
    private function prepend(int $chunkSize = self::SEEK_CHUNK_SIZE)
    {
        $first = $this->boundaries[0];
        $boundaries = $this->fill($this->findSafeBefore(max(0, $first - $chunkSize)), $first, $chunkSize);

        while (end($boundaries) >= $first) {
            array_pop($boundaries);
        }

        $this->index += \count($boundaries);
        $this->boundaries = array_merge($boundaries, $this->boundaries);

        // Drop the newest boundaries, but keep the current one and the one after it
        if (2 * self::MAX_CACHED_BOUNDARIES < \count($this->boundaries)) {
            array_splice($this->boundaries, max(self::MAX_CACHED_BOUNDARIES, $this->index + 2));
        }
    }

    private function setLastCodePoint(int $direction)
    {
        if (self::TYPE_CODE_POINT !== $this->type) {
            return;
        }

        if (-1 === $direction && 0 === $this->index) {
            // The previous code point starts at most 4 bytes before
            $this->prepend(4);
        }

        $start = $this->boundaries[$this->index];
        $end = $this->boundaries[$this->index + $direction];

        $this->lastCodePoint = -1 === $direction ? $this->getCodePoint($end, $start) : $this->getCodePoint($start, $end);
    }

    /**
     * Decodes the code point between two boundaries, which delimit a well-formed sequence unless the text was sanitized.
     */
    private function getCodePoint(int $start, int $end): int
    {
        if (1 === $len = $end - $start) {
            $code = \ord($this->text[$start]);

            return 0x80 > $code ? $code : 0xFFFD;
        }

        $c = substr($this->text, $start, $len);

        if ($this->sanitized && $c !== substr($this->scan, $start, $len)) {
            return 0xFFFD;
        }

        $code = \ord($c[0]) & (0x7F >> $len);
        for ($i = 1; $i < $len; ++$i) {
            $code = ($code << 6) | (\ord($c[$i]) & 0x3F);
        }

        return $code;
    }

    /**
     * Moves an offset back to the start of the code point it points into, as ICU does.
     */
    private function snap(int $offset): int
    {
        while (0 < $offset && $offset < $this->length && 0x80 === (\ord($this->scan[$offset]) & 0xC0)) {
            --$offset;
        }

        return $offset;
    }
}
