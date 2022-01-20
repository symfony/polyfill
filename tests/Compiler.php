<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Internal;

/**
 * Compiler is a use once class that implements the compilation of unicode
 * and charset data to a format suitable for other Utf8 classes.
 *
 * See https://unicode.org/Public/UNIDATA/ for unicode data
 * See https://github.com/unicode-org/cldr/blob/master/common/transforms/ for Latin-ASCII.xml
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
class Compiler
{
    public static function translitMap($out_dir)
    {
        $map = [];

        $h = fopen(self::getFile('UnicodeData.txt'), 'r');
        while (false !== $line = fgets($h)) {
            $m = [];

            if (preg_match('/^([^;]*);[^;]*;[^;]*;[^;]*;[^;]*;<(circle|compat|font|fraction|narrow|small|square|wide)> ([^;]*);/', $line, $m)) {
                $m[1] = self::chr(hexdec($m[1]));

                $m[3] = explode(' ', $m[3]);
                $m[3] = array_map('hexdec', $m[3]);
                $m[3] = array_map([__CLASS__, 'chr'], $m[3]);
                $m[3] = implode('', $m[3]);

                switch ($m[2]) {
                    case 'compat':
                        if (' ' === $m[3][0]) {
                            continue 2;
                        }
                        break;
                    case 'circle':   $m[3] = '('.$m[3].')'; break;
                    case 'fraction': $m[3] = ' '.$m[3].' '; break;
                }

                $m = [$m[1], $m[3]];
            } elseif (preg_match('/^([^;]*);CJK COMPATIBILITY IDEOGRAPH-[^;]*;[^;]*;[^;]*;[^;]*;([^;]*);/', $line, $m)) {
                $m = [
                    self::chr(hexdec($m[1])),
                    self::chr(hexdec($m[2])),
                ];
            }

            if (!$m) {
                continue;
            }

            $map[$m[0]] = $m[1];
        }
        fclose($h);

        foreach (file(self::getFile('Latin-ASCII.xml')) as $line) {
            if (preg_match('/^(.|\\\\u.*?) → (.*?) ;/u', $line, $m)) {
                if ('\u' === substr($m[1], 0, 2)) {
                    $m[1] = self::chr(hexdec(substr($m[1], 2)));
                }

                switch ($m[2][0]) {
                    case '\\': $m[2] = substr($m[2], 1); break;
                    case "'":  $m[2] = substr($m[2], 1, -1); break;
                }

                isset($map[$m[1]]) || $map[$m[1]] = $m[2];
            }
        }

        file_put_contents($out_dir.'translit.php', "<?php\n\nreturn ".var_export($map, true).";\n");
    }

    public static function unicodeMaps($out_dir)
    {
        $upperCase = [];
        $lowerCase = [];
        $caseFolding = [];
        $combiningClass = [];
        $canonicalComposition = [];
        $canonicalDecomposition = [];
        $compatibilityDecomposition = [];

        $exclusion = [];

        $h = fopen(self::getFile('CompositionExclusions.txt'), 'r');
        while (false !== $m = fgets($h)) {
            if (preg_match('/^(?:# )?([0-9A-F]+) /', $m, $m)) {
                $exclusion[self::chr(hexdec($m[1]))] = 1;
            }
        }
        fclose($h);

        $h = fopen(self::getFile('UnicodeData.txt'), 'r');
        while (false !== $m = fgets($h)) {
            $m = explode(';', $m);

            $k = self::chr(hexdec($m[0]));
            $combClass = (int) $m[3];
            $decomp = $m[5];

            $m[12] && $m[12] != $m[0] && $upperCase[$k] = self::chr(hexdec($m[12]));
            $m[13] && $m[13] != $m[0] && $lowerCase[$k] = self::chr(hexdec($m[13]));

            $combClass && $combiningClass[$k] = $combClass;

            if ($decomp) {
                $canonic = '<' != $decomp[0];
                $canonic || $decomp = preg_replace("'^<.*> '", '', $decomp);

                $decomp = explode(' ', $decomp);

                $exclude = 1 == \count($decomp) || isset($exclusion[$k]);

                $decomp = array_map('hexdec', $decomp);
                $decomp = array_map([__CLASS__, 'chr'], $decomp);
                $decomp = implode('', $decomp);

                if ($canonic) {
                    $canonicalDecomposition[$k] = $decomp;
                    $exclude || $canonicalComposition[$decomp] = $k;
                }

                $compatibilityDecomposition[$k] = $decomp;
            }
        }
        fclose($h);

        $h = fopen(self::getFile('SpecialCasing.txt'), 'r');
        while (false !== $m = fgets($h)) {
            if ('#' === $m[0] || 5 !== \count($m = explode('; ', $m))) {
                continue;
            }

            $k = self::chr(hexdec($m[0]));
            $lower = implode('', array_map([__CLASS__, 'chr'], array_map('hexdec', explode(' ', $m[1]))));
            $upper = implode('', array_map([__CLASS__, 'chr'], array_map('hexdec', explode(' ', $m[3]))));

            if ($lower !== $k) {
                $lowerCase[$k] = $lower;
            }
            if ($upper !== $k) {
                $upperCase[$k] = $upper;
            }
        }
        fclose($h);

        do {
            $m = 0;

            foreach ($canonicalDecomposition as $k => $decomp) {
                $h = strtr($decomp, $canonicalDecomposition);
                if ($h != $decomp) {
                    $canonicalDecomposition[$k] = $h;
                    $m = 1;
                }
            }
        } while ($m);

        do {
            $m = 0;

            foreach ($compatibilityDecomposition as $k => $decomp) {
                $h = strtr($decomp, $compatibilityDecomposition);
                if ($h != $decomp) {
                    $compatibilityDecomposition[$k] = $h;
                    $m = 1;
                }
            }
        } while ($m);

        foreach ($compatibilityDecomposition as $k => $decomp) {
            if (isset($canonicalDecomposition[$k]) && $canonicalDecomposition[$k] == $decomp) {
                unset($compatibilityDecomposition[$k]);
            }
        }

        $h = fopen(self::getFile('CaseFolding.txt'), 'r');
        while (false !== $m = fgets($h)) {
            if (preg_match('/^([0-9A-F]+); ([CFST]); ([0-9A-F]+(?: [0-9A-F]+)*)/', $m, $m)) {
                $k = self::chr(hexdec($m[1]));

                $decomp = explode(' ', $m[3]);
                $decomp = array_map('hexdec', $decomp);
                $decomp = array_map([__CLASS__, 'chr'], $decomp);
                $decomp = implode('', $decomp);

                @($lowerCase[$k] != $decomp && $caseFolding[$m[2]][$k] = $decomp);
            }
        }
        fclose($h);

        // Only full case folding is worth serializing
        $caseFolding = [
            array_keys($caseFolding['F']),
            array_values($caseFolding['F']),
        ];

        $upperCase = "<?php\n\nreturn ".var_export($upperCase, true).";\n";
        $lowerCase = "<?php\n\nreturn ".var_export($lowerCase, true).";\n";
        $caseFolding = "<?php\n\nreturn ".var_export($caseFolding, true).";\n";
        $combiningClass = "<?php\n\nreturn ".var_export($combiningClass, true).";\n";
        $canonicalComposition = "<?php\n\nreturn ".var_export($canonicalComposition, true).";\n";
        $canonicalDecomposition = "<?php\n\nreturn ".var_export($canonicalDecomposition, true).";\n";
        $compatibilityDecomposition = "<?php\n\nreturn ".var_export($compatibilityDecomposition, true).";\n";

        file_put_contents($out_dir.'upperCase.php', $upperCase);
        file_put_contents($out_dir.'lowerCase.php', $lowerCase);
        file_put_contents($out_dir.'caseFolding_full.php', $caseFolding);
        file_put_contents($out_dir.'combiningClass.php', $combiningClass);
        file_put_contents($out_dir.'canonicalComposition.php', $canonicalComposition);
        file_put_contents($out_dir.'canonicalDecomposition.php', $canonicalDecomposition);
        file_put_contents($out_dir.'compatibilityDecomposition.php', $compatibilityDecomposition);
    }

    public static function idnMaps($out_dir)
    {
        $handle = fopen(self::getFile('IdnaMappingTable.txt'), 'r');
        $statuses = [
            'mapped' => [],
            'ignored' => [],
            'deviation' => [],
            'disallowed' => [],
            'disallowed_STD3_mapped' => [],
            'disallowed_STD3_valid' => [],
        ];
        $rangeFallback = '';

        while (false !== ($line = fgets($handle))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            [$data] = explode('#', $line);
            $data = array_map('trim', explode(';', $data));
            [$codePoints, $status] = $data;
            $range = explode('..', $codePoints);
            $start = \intval($range[0], 16);
            $codePoints = [$start, isset($range[1]) ? \intval($range[1], 16) : $start];
            $diff = $codePoints[1] - $codePoints[0] + 1;

            switch ($status) {
                case 'valid':
                    // skip valid as we treat it as the default case.
                    break;

                case 'mapped':
                case 'deviation':
                case 'disallowed_STD3_mapped':
                    preg_match_all('/[[:xdigit:]]+/', $data[2], $matches);
                    $mapped = '';

                    foreach ($matches[0] as $codePoint) {
                        $mapped .= self::chr(\intval($codePoint, 16));
                    }

                    for ($i = 0; $i < $diff; ++$i) {
                        $statuses[$status][$codePoints[0] + $i] = $mapped;
                    }

                    break;

                case 'disallowed':
                    if ($diff > 30) {
                        if ('' !== $rangeFallback) {
                            $rangeFallback .= "\n\n";
                        }

                        $rangeFallback .= <<<RANGE_FALLBACK
        if (\$codePoint >= {$codePoints[0]} && \$codePoint <= {$codePoints[1]}) {
            return true;
        }
RANGE_FALLBACK;

                        continue 2;
                    }

                    for ($i = 0; $i < $diff; ++$i) {
                        $statuses[$status][$codePoints[0] + $i] = true;
                    }

                    break;

                case 'ignored':
                case 'disallowed_STD3_valid':
                    for ($i = 0; $i < $diff; ++$i) {
                        $statuses[$status][$codePoints[0] + $i] = true;
                    }

                    break;
            }
        }

        fclose($handle);
        file_put_contents($out_dir.'mapped.php', "<?php\n\nreturn ".var_export($statuses['mapped'], true).";\n");
        file_put_contents($out_dir.'ignored.php', "<?php\n\nreturn ".var_export($statuses['ignored'], true).";\n");
        file_put_contents($out_dir.'deviation.php', "<?php\n\nreturn ".var_export($statuses['deviation'], true).";\n");
        file_put_contents($out_dir.'disallowed.php', "<?php\n\nreturn ".var_export($statuses['disallowed'], true).";\n");
        file_put_contents($out_dir.'disallowed_STD3_mapped.php', "<?php\n\nreturn ".var_export($statuses['disallowed_STD3_mapped'], true).";\n");
        file_put_contents($out_dir.'disallowed_STD3_valid.php', "<?php\n\nreturn ".var_export($statuses['disallowed_STD3_valid'], true).";\n");
        $s = <<<CP_STATUS
<?php

namespace Symfony\Polyfill\Intl\Idn\Resources\unidata;

/**
 * @internal
 */
final class DisallowedRanges
{
    /**
     * @param int \$codePoint
     *
     * @return bool
     */
    public static function inRange(\$codePoint)
    {
{$rangeFallback}

        return false;
    }
}

CP_STATUS;

        file_put_contents($out_dir.'DisallowedRanges.php', $s);
    }

    public static function idnViramaMap($out_dir)
    {
        $handle = fopen(self::getFile('DerivedCombiningClass.txt'), 'r');
        $virama = [];

        while (false !== ($line = fgets($handle))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            [$data] = explode('#', $line);
            $data = array_map('trim', explode(';', $data));
            [$codePoints, $combiningClass] = $data;

            if ('9' !== $combiningClass) {
                continue;
            }

            $range = explode('..', $codePoints);
            $start = \intval($range[0], 16);
            $codePoints = [$start, isset($range[1]) ? \intval($range[1], 16) : $start];
            $diff = $codePoints[1] - $codePoints[0] + 1;

            for ($i = 0; $i < $diff; ++$i) {
                $virama[$codePoints[0] + $i] = (int) $combiningClass;
            }
        }

        fclose($handle);
        file_put_contents($out_dir.'virama.php', "<?php\n\nreturn ".var_export($virama, true).";\n");
    }

    public static function idnRegexClass($out_dir)
    {
        $handle = fopen(self::getFile('DerivedBidiClass.txt'), 'r');
        $bidiData = [];

        while (false !== ($line = fgets($handle))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            [$data] = explode('#', $line);
            $data = array_map('trim', explode(';', $data));
            $range = explode('..', $data[0]);
            $start = \intval($range[0], 16);
            $data[0] = [$start, isset($range[1]) ? \intval($range[1], 16) : $start];
            $bidiData[] = $data;
        }

        fclose($handle);
        $cpSort = static function (array $a, array $b) {
            if ($a[0][0] === $b[0][0]) {
                return 0;
            }

            return $a[0][0] < $b[0][0] ? -1 : 1;
        };
        $buildCharClass = static function (array $data) {
            $out = '';

            foreach ($data as $codePoints) {
                if ($codePoints[0][0] !== $codePoints[0][1]) {
                    $out .= sprintf('\x{%04X}-\x{%04X}', $codePoints[0][0], $codePoints[0][1]);

                    continue;
                }

                $out .= sprintf('\x{%04X}', $codePoints[0][0]);
            }

            return $out;
        };
        usort($bidiData, $cpSort);
        $rtlLabel = sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return \in_array($data[1], ['R', 'AL', 'AN'], true);
        })));

        // Step 1. The first character must be a character with Bidi property L, R, or AL.  If it has the R
        // or AL property, it is an RTL label; if it has the L property, it is an LTR label.
        //
        // Because any code point not explicitly listed in DerivedBidiClass.txt is considered to have the
        // 'L' property, we negate a character class matching all code points explicitly listed in
        // DerivedBidiClass.txt minus the ones explicitly marked as 'L'.
        $bidiStep1Ltr = sprintf('/^[^%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return 'L' !== $data[1];
        })));
        $bidiStep1Rtl = sprintf('/^[%s]/u',
        $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return \in_array($data[1], ['R', 'AL'], true);
        })));

        // Step 2. In an RTL label, only characters with the Bidi properties R, AL, AN, EN, ES, CS, ET, ON,
        // BN, or NSM are allowed.
        $bidiStep2 = sprintf('/[^%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return \in_array($data[1], ['R', 'AL', 'AN', 'EN', 'ES', 'CS', 'ET', 'ON', 'BN', 'NSM'], true);
        })));

        // Step 3. In an RTL label, the end of the label must be a character with Bidi property R, AL, EN,
        // or AN, followed by zero or more characters with Bidi property NSM.
        $bidiStep3 = sprintf(
            '/[%s][%s]*$/u',
            $buildCharClass(array_filter($bidiData, static function (array $data): bool {
                return \in_array($data[1], ['R', 'AL', 'EN', 'AN'], true);
            })),
            $buildCharClass(array_filter($bidiData, static function (array $data): bool {
                return 'NSM' === $data[1];
            }))
        );

        // Step 4. In an RTL label, if an EN is present, no AN may be present, and vice versa.
        $bidiStep4EN = sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return 'EN' === $data[1];
        })));
        $bidiStep4AN = sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return 'AN' === $data[1];
        })));

        // Step 5. In an LTR label, only characters with the Bidi properties L, EN, ES, CS, ET, ON, BN, or
        // NSM are allowed.
        //
        // Because any code point not explicitly listed in DerivedBidiClass.txt is considered to have the
        // 'L' property, we create a character class matching all code points explicitly listed in
        // DerivedBidiClass.txt minus the ones explicitly marked as 'L', 'EN', 'ES', 'CS', 'ET', 'ON',
        // 'BN', or 'NSM'.
        $bidiStep5 = sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return !\in_array($data[1], ['L', 'EN', 'ES', 'CS', 'ET', 'ON', 'BN', 'NSM'], true);
        })));

        // Step 6. In an LTR label, the end of the label must be a character with Bidi property L or EN,
        // followed by zero or more characters with Bidi property NSM.
        //
        // Again, because any code point not explicitly listed in DerivedBidiClass.txt is considered to
        // have the 'L' property, we negate a character class matching all code points explicitly listed in
        // DerivedBidiClass.txt to match characters with the 'L' and 'EN' property.
        $bidiStep6 = sprintf(
            '/[^%s][%s]*$/u',
            $buildCharClass(array_filter($bidiData, static function (array $data): bool {
                return !\in_array($data[1], ['L', 'EN'], true);
            })),
            $buildCharClass(array_filter($bidiData, static function (array $data): bool {
                return 'NSM' === $data[1];
            }))
        );

        unset($bidiData);
        $handle = fopen(self::getFile('DerivedGeneralCategory.txt'), 'r');
        $generalCategories = [];

        while (false !== ($line = fgets($handle))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            [$data] = explode('#', $line);
            $data = array_map('trim', explode(';', $data));
            $range = explode('..', $data[0]);
            $start = \intval($range[0], 16);
            $data[0] = [$start, isset($range[1]) ? \intval($range[1], 16) : $start];
            $generalCategories[] = $data;
        }

        fclose($handle);
        usort($generalCategories, $cpSort);
        $combiningMarks = sprintf('/^[%s]/u', $buildCharClass(array_filter($generalCategories, static function (array $data) {
            return \in_array($data[1], ['Mc', 'Me', 'Mn'], true);
        })));
        unset($generalCategories);

        $handle = fopen(self::getFile('DerivedJoiningType.txt'), 'r');
        $joiningTypes = [];

        while (false !== ($line = fgets($handle))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            [$data] = explode('#', $line);
            $data = array_map('trim', explode(';', $data));
            $range = explode('..', $data[0]);
            $start = \intval($range[0], 16);
            $data[0] = [$start, isset($range[1]) ? \intval($range[1], 16) : $start];
            $joiningTypes[] = $data;
        }

        fclose($handle);
        usort($joiningTypes, $cpSort);

        // ((Joining_Type:{L,D})(Joining_Type:T)*\u200C(Joining_Type:T)*(Joining_Type:{R,D}))
        // We use a capturing group around the first portion of the regex so we can count the byte length
        // of the match and increment preg_match's offset accordingly.
        $zwnj = sprintf(
            '/([%1$s%2$s][%3$s]*\x{200C}[%3$s]*)[%4$s%2$s]/u',
            $buildCharClass(array_filter($joiningTypes, static function (array $data): bool {
                return 'L' === $data[1];
            })),
            $buildCharClass(array_filter($joiningTypes, static function (array $data): bool {
                return 'D' === $data[1];
            })),
            $buildCharClass(array_filter($joiningTypes, static function (array $data): bool {
                return 'T' === $data[1];
            })),
            $buildCharClass(array_filter($joiningTypes, static function (array $data): bool {
                return 'R' === $data[1];
            }))
        );
        $s = <<<RegexClass
<?php

namespace Symfony\Polyfill\Intl\Idn\Resources\unidata;

/**
 * @internal
 */
final class Regex
{
    const COMBINING_MARK = '{$combiningMarks}';

    const RTL_LABEL = '{$rtlLabel}';

    const BIDI_STEP_1_LTR = '{$bidiStep1Ltr}';
    const BIDI_STEP_1_RTL = '{$bidiStep1Rtl}';
    const BIDI_STEP_2 = '{$bidiStep2}';
    const BIDI_STEP_3 = '{$bidiStep3}';
    const BIDI_STEP_4_AN = '{$bidiStep4AN}';
    const BIDI_STEP_4_EN = '{$bidiStep4EN}';
    const BIDI_STEP_5 = '{$bidiStep5}';
    const BIDI_STEP_6 = '{$bidiStep6}';

    const ZWNJ = '{$zwnj}';
}

RegexClass;

        file_put_contents($out_dir.'Regex.php', $s);
    }

    protected static function chr($c)
    {
        $c %= 0x200000;

        return $c < 0x80 ? \chr($c) : (
               $c < 0x800 ? \chr(0xC0 | $c >> 6).\chr(0x80 | $c & 0x3F) : (
               $c < 0x10000 ? \chr(0xE0 | $c >> 12).\chr(0x80 | $c >> 6 & 0x3F).\chr(0x80 | $c & 0x3F) : (
                              \chr(0xF0 | $c >> 18).\chr(0x80 | $c >> 12 & 0x3F).\chr(0x80 | $c >> 6 & 0x3F).\chr(0x80 | $c & 0x3F)
        )));
    }

    protected static function getFile($file)
    {
        return __DIR__.'/unicode/data/'.$file;
    }
}
