<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\BreakIterator;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\BreakIterator\BreakIterator as p;
use Symfony\Polyfill\Intl\BreakIterator\PartsIterator;
use Symfony\Polyfill\Intl\Grapheme\Grapheme;

/**
 * @group class-polyfill
 *
 * @covers \Symfony\Polyfill\Intl\BreakIterator\BreakIterator::<!public>
 */
class BreakIteratorTest extends TestCase
{
    private const TEXT = "Hello, world! It's 3.14 o'clock.\n€5 – don't 👍🏽 stop";

    /**
     * @dataProvider provideWordBreakTests
     */
    public function testWordBreakConformance(string $text, array $expected)
    {
        $it = p::createWordInstance();
        $it->setText($text);

        $this->assertSame($expected, iterator_to_array($it));
    }

    public static function provideWordBreakTests(): iterable
    {
        return self::parseBreakTests(__DIR__.'/WordBreakTest.txt');
    }

    /**
     * Character boundaries are the ones of the grapheme polyfill, which do not implement all the rules of UAX #29.
     *
     * @dataProvider provideGraphemeBreakTests
     */
    public function testCharacterBoundariesMatchGraphemePolyfill(string $text)
    {
        $it = p::createCharacterInstance();
        $it->setText($text);

        class_exists(Grapheme::class);
        preg_match_all('/'.\SYMFONY_GRAPHEME_CLUSTER_RX.'/u', $text, $m);
        $expected = [0];
        foreach ($m[0] as $grapheme) {
            $expected[] = end($expected) + \strlen($grapheme);
        }

        $this->assertSame($expected, iterator_to_array($it));
    }

    public static function provideGraphemeBreakTests(): iterable
    {
        foreach (self::parseBreakTests(__DIR__.'/GraphemeBreakTest.txt') as $test) {
            yield [$test[0]];
        }
    }

    public function testWordInstance()
    {
        $it = p::createWordInstance('en');
        $this->assertNull($it->getText());
        $this->assertSame(0, $it->current());
        $this->assertSame(p::DONE, $it->next());

        $this->assertTrue($it->setText('héllo 你好世界, ok'));
        $this->assertSame('héllo 你好世界, ok', $it->getText());

        // ICU uses a dictionary and returns [0, 6, 7, 13, 19, 20, 21, 23]
        $this->assertSame([0, 6, 7, 10, 13, 16, 19, 20, 21, 23], iterator_to_array($it));
        $this->assertSame(['héllo', ' ', '你', '好', '世', '界', ',', ' ', 'ok'], iterator_to_array($it->getPartsIterator()));
    }

    public function testRuleStatus()
    {
        $it = p::createWordInstance();
        $it->setText(self::TEXT);

        $statuses = [];
        foreach ($it as $boundary) {
            $statuses[$boundary] = $it->getRuleStatus();
            $this->assertSame([$statuses[$boundary]], $it->getRuleStatusVec());
        }

        $this->assertSame([0 => 0, 5 => 200, 6 => 0, 7 => 0, 12 => 200, 13 => 0, 14 => 0, 18 => 200, 19 => 0, 23 => 100, 24 => 0, 31 => 200, 32 => 0, 33 => 0, 36 => 0, 37 => 100, 38 => 0, 41 => 0, 42 => 0, 47 => 200, 48 => 0, 56 => 0, 57 => 0, 61 => 200], $statuses);
    }

    /**
     * @dataProvider provideRuleStatuses
     */
    public function testRuleStatusOfSegment(string $segment, int $status)
    {
        $it = p::createWordInstance();
        $it->setText($segment);
        $it->last();

        $this->assertSame(0, $it->previous());
        $this->assertSame(0, $it->getRuleStatus());
        $it->next();
        $this->assertSame($status, $it->getRuleStatus());
    }

    public static function provideRuleStatuses(): iterable
    {
        yield ['word', p::WORD_LETTER];
        yield ['été', p::WORD_LETTER];
        yield ['3.14', p::WORD_NUMBER];
        yield ['a1', p::WORD_NUMBER];
        yield ['1a', p::WORD_LETTER];
        yield ['_', p::WORD_NONE];
        yield ['__', p::WORD_LETTER];
        yield ['1_', p::WORD_NUMBER];
        yield ['_1', p::WORD_NUMBER];
        yield ['アア', p::WORD_IDEO];
        yield ['ア_', p::WORD_IDEO];
        yield ['あ', p::WORD_IDEO];
        yield ['漢', p::WORD_IDEO];
        yield ['ก', p::WORD_LETTER];
        yield ['한국', p::WORD_LETTER];
        yield ["\u{5D0}'", p::WORD_LETTER];
        yield ['😀', p::WORD_NONE];
        yield ["a\u{200D}😀", p::WORD_NONE];
        yield ['  ', p::WORD_NONE];
        yield [',', p::WORD_NONE];
    }

    public function testNavigation()
    {
        $it = p::createWordInstance();
        $it->setText(self::TEXT);

        $this->assertSame(5, $it->following(0));
        $this->assertSame(6, $it->following(5));
        $this->assertSame(31, $it->following(30));
        $this->assertSame(31, $it->current());
        $this->assertSame(0, $it->following(-1));
        $this->assertSame(p::DONE, $it->following(61));
        $this->assertSame(61, $it->current());

        $this->assertSame(12, $it->preceding(13));
        $this->assertSame(13, $it->preceding(14));
        $this->assertSame(p::DONE, $it->preceding(0));
        $this->assertSame(0, $it->current());
        $this->assertSame(61, $it->preceding(100));
        $this->assertSame(47, $it->preceding(50), 'Offsets inside a code point move to its start');

        $this->assertTrue($it->isBoundary(5));
        $this->assertSame(5, $it->current());
        $this->assertFalse($it->isBoundary(9));
        $this->assertSame(12, $it->current());
        $this->assertFalse($it->isBoundary(-1));
        $this->assertSame(0, $it->current());
        $this->assertFalse($it->isBoundary(62));
        $this->assertSame(61, $it->current());

        $this->assertSame(0, $it->first());
        $this->assertSame(7, $it->next(3));
        $this->assertSame(5, $it->next(-2));
        $this->assertSame(5, $it->next(0));
        $this->assertSame(0, $it->previous());
        $this->assertSame(p::DONE, $it->previous());
        $this->assertSame(61, $it->last());
        $this->assertSame(p::DONE, $it->next());
        $this->assertSame(61, $it->current());
        $this->assertSame(57, $it->previous());
        $this->assertSame(p::DONE, $it->next(100));
        $this->assertSame(61, $it->current());

        $this->assertTrue($it->setText('abc'));
        $this->assertSame(0, $it->current());
    }

    public function testPartsIterator()
    {
        $it = p::createWordInstance();
        $it->setText('ab, c');

        $parts = $it->getPartsIterator();
        $this->assertInstanceOf(PartsIterator::class, $parts);
        $this->assertSame($it, $parts->getBreakIterator());
        $this->assertSame(['ab', ',', ' ', 'c'], iterator_to_array($parts));
        $this->assertSame([0 => 'ab', 2 => ',', 3 => ' ', 4 => 'c'], iterator_to_array($it->getPartsIterator(PartsIterator::KEY_LEFT)));
        $this->assertSame([2 => 'ab', 3 => ',', 4 => ' ', 5 => 'c'], iterator_to_array($it->getPartsIterator(PartsIterator::KEY_RIGHT)));

        $statuses = [];
        foreach ($parts as $part) {
            $statuses[] = $parts->getRuleStatus();
        }
        $this->assertSame([200, 0, 0, 200], $statuses);
    }

    public function testCharacterInstance()
    {
        $it = p::createCharacterInstance();
        $it->setText("e\u{301}🇫🇷👨‍👩‍👧\r\n");

        $this->assertSame([0, 3, 11, 29, 31], iterator_to_array($it));
        $this->assertSame(0, $it->getRuleStatus());
    }

    public function testCodePointInstance()
    {
        $it = p::createCodePointInstance();
        $this->assertSame(-1, $it->getLastCodePoint());

        $it->setText('aé😀');
        $this->assertSame([0, 1, 3, 7], iterator_to_array($it));

        $it->first();
        $this->assertSame(-1, $it->getLastCodePoint());
        $this->assertSame(1, $it->next());
        $this->assertSame(0x61, $it->getLastCodePoint());
        $this->assertSame(3, $it->next());
        $this->assertSame(0xE9, $it->getLastCodePoint());
        $this->assertSame(1, $it->previous());
        $this->assertSame(0xE9, $it->getLastCodePoint());
        $this->assertSame(7, $it->following(4));
        $this->assertSame(0x1F600, $it->getLastCodePoint());
        $this->assertSame(7, $it->last());
        $this->assertSame(-1, $it->getLastCodePoint());
        $this->assertFalse($it->isBoundary(5));
        $this->assertSame(3, $it->current());
    }

    public function testIllFormedUtf8()
    {
        $it = p::createWordInstance();
        $it->setText("a\xFFb\xE2\x82c");

        $this->assertSame([0, 1, 2, 3, 5, 6], iterator_to_array($it));

        $it = p::createCodePointInstance();
        $it->setText("a\xFF");
        $it->last();
        $it->previous();
        $this->assertSame(0xFFFD, $it->getLastCodePoint());
    }

    public function testLargeText()
    {
        $text = str_repeat("Lorem ipsum, dolor sit amet. Ünïcödé 😀 text\n", 20000);
        $length = \strlen($text);

        $it = p::createWordInstance();
        $it->setText($text);

        $count = 0;
        foreach ($it as $boundary) {
            ++$count;
        }
        $this->assertSame(1 + 20000 * 18, $count);
        $this->assertSame($length, $boundary);

        // The cache of boundaries stays bounded
        $cached = (function () { return \count($this->boundaries); })->bindTo($it, p::class)();
        $this->assertLessThanOrEqual(8192, $cached);

        // Moves far from the cached boundaries only compute boundaries around the target offset
        $this->assertSame(41, $it->following(40));
        $this->assertSame(46, $it->preceding(47));
        $this->assertSame($length - 5, $it->preceding($length - 1));
        $this->assertSame(11, $it->following(6));
        $this->assertSame(0, $it->previous() - 6);
        $this->assertSame($length - 1, $it->preceding($length));
    }

    /**
     * @dataProvider provideTextsWithFewSafePoints
     */
    public function testNavigationMatchesIteration(string $factory, string $text)
    {
        $it = p::$factory();
        $it->setText($text);
        $boundaries = iterator_to_array($it);

        $this->assertSame(\strlen($text), end($boundaries));

        $reverse = [$it->last()];
        while (p::DONE !== $boundary = $it->previous()) {
            $reverse[] = $boundary;
        }
        $this->assertSame(array_reverse($boundaries), $reverse);

        $isBoundary = array_fill_keys($boundaries, true);
        mt_srand(727);
        for ($k = 0; $k < 200; ++$k) {
            $offset = mt_rand(0, \strlen($text) - 1);
            $start = $offset;
            while (0 < $start && 0x80 === (\ord($text[$start]) & 0xC0)) {
                --$start;
            }
            $i = 0;
            while ($boundaries[$i] <= $start) {
                ++$i;
            }

            $this->assertSame($boundaries[$i], $it->following($offset), "following($offset)");
            $this->assertSame($boundaries[$i], $it->current());
            $this->assertSame(isset($isBoundary[$offset]), $it->isBoundary($offset), "isBoundary($offset)");
            $this->assertSame(0 < $start ? $boundaries[isset($isBoundary[$start]) ? $i - 2 : $i - 1] : p::DONE, $it->preceding($offset), "preceding($offset)");
        }
    }

    public static function provideTextsWithFewSafePoints(): iterable
    {
        $texts = [
            'no space' => str_repeat('abc,def.ghi_', 2000),
            'JSON' => json_encode(array_fill(0, 1000, ['id' => 123, 'name' => "l'été"])),
            'Thai' => str_repeat('ภาษาไทย', 1500),
            'CJK' => str_repeat('漢字テストかな', 1000),
            'mixed' => str_repeat("Lorem ipsum, dolor sit amet. Ünïcödé 😀 text\n", 500),
        ];

        foreach (['createWordInstance', 'createCharacterInstance', 'createCodePointInstance'] as $factory) {
            foreach ($texts as $name => $text) {
                yield $factory.' '.$name => [$factory, $text];
            }
        }
    }

    /**
     * @requires extension intl
     */
    public function testMatchesIntl()
    {
        mt_srand(714);
        $pieces = ['hello', 'world', "can't", '3.14', '1,000', '_id', ' ', '  ', "\n", "\r\n", ',', '.', '!', '"', 'é', 'Ελλάδα', 'мир', 'שָׁלוֹם', 'א"ב', 'مرحبا', '😀', '👍🏽', '👨‍👩‍👧', '🇫🇷', "e\u{301}", '—', 'ⓜ', "\u{3000}"];

        foreach (['createWordInstance', 'createCharacterInstance', 'createCodePointInstance'] as $factory) {
            // Before PCRE 10.44, the grapheme cluster pattern also joins any char that follows a ZWJ
            $factoryPieces = 'createCharacterInstance' !== $factory || (float) \PCRE_VERSION >= 10.44 ? array_merge($pieces, ["a\u{200D}😀"]) : $pieces;

            for ($t = 0; $t < 4; ++$t) {
                $text = '';
                while (\strlen($text) < 3000 * ($t + 1)) {
                    $text .= $factoryPieces[mt_rand(0, \count($factoryPieces) - 1)];
                }

                $p = p::$factory();
                // The default locale can be en_US_POSIX, whose word rules don't join letters around "." or ":"
                $i = 'createCodePointInstance' === $factory ? \IntlBreakIterator::$factory() : \IntlBreakIterator::$factory('en');
                $p->setText($text);
                $i->setText($text);

                $this->assertSame(iterator_to_array($i), iterator_to_array($p));

                for ($k = 0; $k < 500; ++$k) {
                    $o = mt_rand(-2, \strlen($text) + 2);
                    $calls = [['following', $o], ['preceding', $o], ['isBoundary', $o], ['next', null], ['previous', null], ['next', mt_rand(-5, 5)], [mt_rand(0, 1) ? 'first' : 'last', null]];
                    $call = $calls[mt_rand(0, 6)];

                    if ('createCodePointInstance' === $factory && 'preceding' === $call[0]) {
                        continue; // ICU is inconsistent for offsets inside a code point here
                    }

                    $args = null === $call[1] ? [] : [$call[1]];
                    $expected = [$i->{$call[0]}(...$args), $i->current(), 'createCodePointInstance' === $factory ? $i->getLastCodePoint() : $i->getRuleStatus()];
                    $actual = [$p->{$call[0]}(...$args), $p->current(), 'createCodePointInstance' === $factory ? $p->getLastCodePoint() : $p->getRuleStatus()];
                    $this->assertSame($expected, $actual, \sprintf('%s->%s(%s) at step %d', $factory, $call[0], $call[1], $k));

                    if (p::DONE === $expected[0]) {
                        // ICU keeps returning DONE from next() after previous() reached the start
                        $i->first();
                        $p->first();
                    }
                }
            }
        }
    }

    public function testAsciiClassesMatchUnicodeData()
    {
        $ascii = (new \ReflectionClassConstant(p::class, 'ASCII_CLASSES'))->getValue();
        $unicode = require \dirname(__DIR__, 3).'/src/Intl/BreakIterator/Resources/unidata/wordBreak.php';

        foreach ($unicode as $name => $class) {
            for ($c = 0; $c < 0x80; ++$c) {
                $this->assertSame(
                    '' !== $class && preg_match('/['.$class.']/u', \chr($c)),
                    '' !== $ascii[$name] && preg_match('/['.$ascii[$name].']/', \chr($c)),
                    \sprintf('Class "%s" for U+%04X', $name, $c)
                );
            }
        }
    }

    public function testSafePointsAreBoundaries()
    {
        $text = '';
        for ($c = 0; $c < 0x80; ++$c) {
            $text .= \chr($c).'a'.\chr($c).\chr($c).'1 '.\chr($c)."\u{301}\u{200D}é".\chr($c)."\r\n";
        }
        $text .= "a 漢 \u{301}漢\u{200D}漢 あ\u{3099}あい ก\u{E31}กa\u{E40}ก \u{5D0}\"\u{5D0} 🇫🇷🇫 \u{600}a\u{600}\u{301}";
        mt_srand(727);
        $pieces = preg_split('//u', $text, -1, \PREG_SPLIT_NO_EMPTY);
        for ($k = 0; $k < 2000; ++$k) {
            $text .= $pieces[mt_rand(0, \count($pieces) - 1)];
        }

        $safeRx = (new \ReflectionClassConstant(p::class, 'SAFE_RX'))->getValue();

        foreach (['createCodePointInstance', 'createCharacterInstance', 'createWordInstance'] as $type => $factory) {
            // Segment the whole text in one pass, as chunked iteration itself relies on safe points
            $it = p::$factory();
            preg_match_all((function () { return $this->getPattern(true); })->bindTo($it, p::class)(), $text, $m);
            $boundaries = [0 => true];
            $offset = 0;
            foreach ($m[0] as $segment) {
                $boundaries[$offset += \strlen($segment)] = true;
            }
            $this->assertSame(\strlen($text), $offset);

            preg_match_all($safeRx[$type], $text, $m, \PREG_OFFSET_CAPTURE);
            $this->assertNotEmpty($m[0]);

            foreach ($m[0] as [, $offset]) {
                $this->assertArrayHasKey($offset, $boundaries, \sprintf('%s: safe point %d (%s)', $factory, $offset, bin2hex(substr($text, max(0, $offset - 4), 8))));
            }
        }
    }

    public function testGlobalClasses()
    {
        if (\extension_loaded('intl')) {
            $this->markTestSkipped('The polyfill is not used when the "intl" extension is loaded.');
        }

        $it = \IntlBreakIterator::createWordInstance('en');
        $this->assertInstanceOf(\IntlRuleBasedBreakIterator::class, $it);
        $this->assertInstanceOf(\IntlBreakIterator::class, $it);
        $this->assertInstanceOf(\IntlPartsIterator::class, $it->getPartsIterator());
        $this->assertInstanceOf(\IntlRuleBasedBreakIterator::class, \IntlBreakIterator::createCharacterInstance());
        $this->assertInstanceOf(\IntlCodePointBreakIterator::class, \IntlBreakIterator::createCodePointInstance());

        $it->setText('hello world');
        $this->assertSame(['hello', ' ', 'world'], iterator_to_array($it->getPartsIterator()));
        $this->assertSame(\IntlBreakIterator::WORD_LETTER, $it->getRuleStatus());
    }

    public function testCustomRulesAreNotSupported()
    {
        $this->expectException(\LogicException::class);

        new \Symfony\Polyfill\Intl\BreakIterator\RuleBasedBreakIterator('$a = [a]; $a;');
    }

    private static function parseBreakTests(string $file): iterable
    {
        foreach (file($file) as $line) {
            if ('' === $line = trim(explode('#', $line)[0])) {
                continue;
            }

            $text = '';
            $boundaries = [];
            foreach (preg_split('/\s+/', $line) as $token) {
                if ('÷' === $token) {
                    $boundaries[] = \strlen($text);
                } elseif ('×' !== $token) {
                    $text .= self::chr(hexdec($token));
                }
            }

            yield [$text, $boundaries];
        }
    }

    private static function chr(int $code): string
    {
        if (0x80 > $code) {
            return \chr($code);
        }
        if (0x800 > $code) {
            return \chr(0xC0 | $code >> 6).\chr(0x80 | $code & 0x3F);
        }
        if (0x10000 > $code) {
            return \chr(0xE0 | $code >> 12).\chr(0x80 | $code >> 6 & 0x3F).\chr(0x80 | $code & 0x3F);
        }

        return \chr(0xF0 | $code >> 18).\chr(0x80 | $code >> 12 & 0x3F).\chr(0x80 | $code >> 6 & 0x3F).\chr(0x80 | $code & 0x3F);
    }
}
