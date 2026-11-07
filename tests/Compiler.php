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
 * See https://github.com/unicode-org/cldr/blob/main/common/transforms/ for Latin-ASCII.xml
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
        $canonical = [];

        $h = fopen(self::getFile('UnicodeData.txt'), 'r');
        while (false !== $line = fgets($h)) {
            $m = [];

            if (preg_match('/^([^;]*);[^;]*;[^;]*;[^;]*;[^;]*;([0-9A-F]++)[ ;]/', $line, $m)) {
                $canonical[hexdec($m[1])] = hexdec($m[2]);
                $m = [];
            }

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

        // Characters whose canonical decomposition starts with an ASCII character, e.g. "é" => "e"
        foreach ($canonical as $c => $base) {
            while (isset($canonical[$base])) {
                $base = $canonical[$base];
            }

            if (0x80 > $base) {
                $c = self::chr($c);
                isset($map[$c]) || $map[$c] = \chr($base);
            }
        }

        file_put_contents($out_dir.'translit.php', "<?php\n\nreturn ".var_export($map, true).";\n");
    }

    public static function unicodeMaps($out_dir)
    {
        $upperCase = [];
        $lowerCase = [];
        $titleCase = [];
        $caseFolding = [];
        $combiningClass = [];
        $canonicalComposition = [];
        $canonicalDecomposition = [];
        $compatibilityDecomposition = [];
        $rawCanonicalDecomposition = [];
        $rawCompatibilityDecomposition = [];

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
            $m[14] = trim($m[14]);
            $m[14] && $m[14] != ($m[12] ?: $m[0]) && $titleCase[$k] = self::chr(hexdec($m[14]));

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
                    $rawCanonicalDecomposition[$k] = $decomp;
                    $exclude || $canonicalComposition[$decomp] = $k;
                } else {
                    $rawCompatibilityDecomposition[$k] = $decomp;
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
            $title = implode('', array_map([__CLASS__, 'chr'], array_map('hexdec', explode(' ', $m[2]))));
            $upper = implode('', array_map([__CLASS__, 'chr'], array_map('hexdec', explode(' ', $m[3]))));

            if ($lower !== $k) {
                $lowerCase[$k] = $lower;
            }
            if ($upper !== $k) {
                $upperCase[$k] = $upper;
            }
            if ($title !== $upper) {
                $titleCase[$k] = $title;
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
        $titleCase = "<?php\n\nreturn ".var_export($titleCase, true).";\n";
        $caseFolding = "<?php\n\nreturn ".var_export($caseFolding, true).";\n";
        $combiningClass = "<?php\n\nreturn ".var_export($combiningClass, true).";\n";
        $canonicalComposition = "<?php\n\nreturn ".var_export($canonicalComposition, true).";\n";
        $canonicalDecomposition = "<?php\n\nreturn ".var_export($canonicalDecomposition, true).";\n";
        $compatibilityDecomposition = "<?php\n\nreturn ".var_export($compatibilityDecomposition, true).";\n";
        $rawCanonicalDecompositionData = "<?php\n\nreturn ".var_export($rawCanonicalDecomposition, true).";\n";
        $rawCompatibilityDecompositionData = "<?php\n\nreturn ".var_export($rawCompatibilityDecomposition, true).";\n";

        file_put_contents($out_dir.'upperCase.php', $upperCase);
        file_put_contents($out_dir.'lowerCase.php', $lowerCase);
        file_put_contents($out_dir.'titleCase.php', $titleCase);
        file_put_contents($out_dir.'caseFolding_full.php', $caseFolding);
        file_put_contents($out_dir.'combiningClass.php', $combiningClass);
        file_put_contents($out_dir.'canonicalComposition.php', $canonicalComposition);
        file_put_contents($out_dir.'canonicalDecomposition.php', $canonicalDecomposition);
        file_put_contents($out_dir.'compatibilityDecomposition.php', $compatibilityDecomposition);
        file_put_contents($out_dir.'rawCanonicalDecomposition.php', $rawCanonicalDecompositionData);
        file_put_contents($out_dir.'rawCompatibilityDecomposition.php', $rawCompatibilityDecompositionData);
    }

    public static function eastAsianWidthRegexp($out_dir)
    {
        $ranges = [];

        $h = fopen(self::getFile('EastAsianWidth.txt'), 'r');
        while (false !== $m = fgets($h)) {
            if (preg_match('/^([0-9A-F]+)(?:\.\.([0-9A-F]+))?\s*;\s*[WF]\b/', $m, $m)) {
                $start = hexdec($m[1]);
                $end = hexdec($m[2] ?? $m[1]);

                if ($ranges && $ranges[\count($ranges) - 1][1] + 1 === $start) {
                    $ranges[\count($ranges) - 1][1] = $end;
                } else {
                    $ranges[] = [$start, $end];
                }
            }
        }
        fclose($h);

        $regexp = '';
        foreach ($ranges as [$start, $end]) {
            $regexp .= $start === $end ? \sprintf('\x{%04X}', $start) : \sprintf('\x{%04X}-\x{%04X}', $start, $end);
        }

        file_put_contents($out_dir.'eastAsianWidthRegexp.php', "<?php\n\n// from W and F in https://unicode.org/Public/UNIDATA/EastAsianWidth.txt\n\nreturn '/[".$regexp."]/u';\n");
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
        $missingData = [];
        $bidiAliases = [
            'Left_To_Right' => 'L',
            'Right_To_Left' => 'R',
            'Arabic_Letter' => 'AL',
            'European_Terminator' => 'ET',
            'Boundary_Neutral' => 'BN',
        ];

        while (false !== ($line = fgets($handle))) {
            // Unassigned code points whose default is not L are only listed in @missing lines
            if (preg_match('/^# @missing: ([[:xdigit:]]+)\.\.([[:xdigit:]]+); (\w+)/', $line, $matches)) {
                if (!isset($bidiAliases[$matches[3]])) {
                    throw new \RuntimeException(\sprintf('Unknown bidi class "%s".', $matches[3]));
                }

                if ('L' !== $bidiAliases[$matches[3]]) {
                    $missingData[] = [[\intval($matches[1], 16), \intval($matches[2], 16)], $bidiAliases[$matches[3]]];
                }

                continue;
            }

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
        usort($bidiData, $cpSort);
        $defaultData = [];

        foreach ($missingData as [[$start, $end], $bidiClass]) {
            foreach ($bidiData as [[$from, $to]]) {
                if ($to < $start || $from > $end) {
                    continue;
                }

                if ($from > $start) {
                    $defaultData[] = [[$start, $from - 1], $bidiClass];
                }

                $start = $to + 1;
            }

            if ($start <= $end) {
                $defaultData[] = [[$start, $end], $bidiClass];
            }
        }

        $bidiData = array_merge($bidiData, $defaultData);
        $buildCharClass = static function (array $data) {
            $out = '';

            foreach ($data as $codePoints) {
                if ($codePoints[0][0] !== $codePoints[0][1]) {
                    $out .= \sprintf('\x{%04X}-\x{%04X}', $codePoints[0][0], $codePoints[0][1]);

                    continue;
                }

                $out .= \sprintf('\x{%04X}', $codePoints[0][0]);
            }

            return $out;
        };
        usort($bidiData, $cpSort);
        $rtlLabel = \sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return \in_array($data[1], ['R', 'AL', 'AN'], true);
        })));

        // Step 1. The first character must be a character with Bidi property L, R, or AL.  If it has the R
        // or AL property, it is an RTL label; if it has the L property, it is an LTR label.
        //
        // Because any code point not explicitly listed in DerivedBidiClass.txt is considered to have the
        // 'L' property, we negate a character class matching all code points explicitly listed in
        // DerivedBidiClass.txt minus the ones explicitly marked as 'L'.
        $bidiStep1Ltr = \sprintf('/^[^%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return 'L' !== $data[1];
        })));
        $bidiStep1Rtl = \sprintf('/^[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return \in_array($data[1], ['R', 'AL'], true);
        })));

        // Step 2. In an RTL label, only characters with the Bidi properties R, AL, AN, EN, ES, CS, ET, ON,
        // BN, or NSM are allowed.
        $bidiStep2 = \sprintf('/[^%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return \in_array($data[1], ['R', 'AL', 'AN', 'EN', 'ES', 'CS', 'ET', 'ON', 'BN', 'NSM'], true);
        })));

        // Step 3. In an RTL label, the end of the label must be a character with Bidi property R, AL, EN,
        // or AN, followed by zero or more characters with Bidi property NSM.
        $bidiStep3 = \sprintf(
            '/[%s][%s]*$/u',
            $buildCharClass(array_filter($bidiData, static function (array $data): bool {
                return \in_array($data[1], ['R', 'AL', 'EN', 'AN'], true);
            })),
            $buildCharClass(array_filter($bidiData, static function (array $data): bool {
                return 'NSM' === $data[1];
            }))
        );

        // Step 4. In an RTL label, if an EN is present, no AN may be present, and vice versa.
        $bidiStep4EN = \sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return 'EN' === $data[1];
        })));
        $bidiStep4AN = \sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return 'AN' === $data[1];
        })));

        // Step 5. In an LTR label, only characters with the Bidi properties L, EN, ES, CS, ET, ON, BN, or
        // NSM are allowed.
        //
        // Because any code point not explicitly listed in DerivedBidiClass.txt is considered to have the
        // 'L' property, we create a character class matching all code points explicitly listed in
        // DerivedBidiClass.txt minus the ones explicitly marked as 'L', 'EN', 'ES', 'CS', 'ET', 'ON',
        // 'BN', or 'NSM'.
        $bidiStep5 = \sprintf('/[%s]/u', $buildCharClass(array_filter($bidiData, static function (array $data): bool {
            return !\in_array($data[1], ['L', 'EN', 'ES', 'CS', 'ET', 'ON', 'BN', 'NSM'], true);
        })));

        // Step 6. In an LTR label, the end of the label must be a character with Bidi property L or EN,
        // followed by zero or more characters with Bidi property NSM.
        //
        // Again, because any code point not explicitly listed in DerivedBidiClass.txt is considered to
        // have the 'L' property, we negate a character class matching all code points explicitly listed in
        // DerivedBidiClass.txt to match characters with the 'L' and 'EN' property.
        $bidiStep6 = \sprintf(
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
        $combiningMarks = \sprintf('/^[%s]/u', $buildCharClass(array_filter($generalCategories, static function (array $data) {
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
        $zwnj = \sprintf(
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

    public static function intlCharMaps($out_dir)
    {
        require_once __DIR__.'/../src/Intl/Char/Char.php';

        $range = static function ($codePoints) {
            $range = explode('..', trim($codePoints));

            return [\intval($range[0], 16), \intval(isset($range[1]) ? $range[1] : $range[0], 16)];
        };
        // The values of an enumerated property for every code point: the defaults of @missing, then the values given
        $enumerated = static function ($file, array $values) use ($range) {
            $table = array_fill(0, 0x110000, 0);
            foreach (file(self::getFile($file)) as $line) {
                if (preg_match('/^# @missing: ([0-9A-F.]+); (\w+)/', $line, $m) || preg_match('/^([0-9A-F.]+)\s*;\s*(\w+)/', $line, $m)) {
                    list($start, $end) = $range($m[1]);
                    $value = isset($values[$m[2]]) ? $values[$m[2]] : (int) $m[2];
                    for ($i = $start; $i <= $end; ++$i) {
                        $table[$i] = $value;
                    }
                }
            }

            return $table;
        };

        $bidi = ['L' => 0, 'R' => 1, 'EN' => 2, 'ES' => 3, 'ET' => 4, 'AN' => 5, 'CS' => 6, 'B' => 7, 'S' => 8, 'WS' => 9, 'ON' => 10, 'LRE' => 11, 'LRO' => 12, 'AL' => 13, 'RLE' => 14, 'RLO' => 15, 'PDF' => 16, 'NSM' => 17, 'BN' => 18, 'FSI' => 19, 'LRI' => 20, 'RLI' => 21, 'PDI' => 22];
        $bidi += ['Left_To_Right' => 0, 'Right_To_Left' => 1, 'European_Number' => 2, 'European_Separator' => 3, 'European_Terminator' => 4, 'Arabic_Number' => 5, 'Common_Separator' => 6, 'Paragraph_Separator' => 7, 'Segment_Separator' => 8, 'White_Space' => 9, 'Other_Neutral' => 10, 'Left_To_Right_Embedding' => 11, 'Left_To_Right_Override' => 12, 'Arabic_Letter' => 13, 'Right_To_Left_Embedding' => 14, 'Right_To_Left_Override' => 15, 'Pop_Directional_Format' => 16, 'Nonspacing_Mark' => 17, 'Boundary_Neutral' => 18, 'First_Strong_Isolate' => 19, 'Left_To_Right_Isolate' => 20, 'Right_To_Left_Isolate' => 21, 'Pop_Directional_Isolate' => 22];
        // The general categories, in the order of IntlChar::CHAR_CATEGORY_*
        $categories = array_flip(['Cn', 'Lu', 'Ll', 'Lt', 'Lm', 'Lo', 'Mn', 'Me', 'Mc', 'Nd', 'Nl', 'No', 'Zs', 'Zl', 'Zp', 'Cc', 'Cf', 'Co', 'Cs', 'Pd', 'Ps', 'Pe', 'Pc', 'Po', 'Sm', 'Sc', 'Sk', 'So', 'Pi', 'Pf']);
        $categories += ['Unassigned' => 0];

        $tables = [
            'bidi' => $enumerated('DerivedBidiClass.txt', $bidi),
            'category' => $enumerated('DerivedGeneralCategory.txt', $categories),
            'combining' => $enumerated('DerivedCombiningClass.txt', []),
        ];
        // The ages: the starts of their runs, and the index of the version of each run
        $versions = [''];
        $ages = array_fill(0, 0x110000, 0);
        foreach (file(self::getFile('DerivedAge.txt')) as $line) {
            if (preg_match('/^([0-9A-F.]+)\s*;\s*([0-9.]+)/', $line, $m)) {
                list($start, $end) = $range($m[1]);
                if (false === $version = array_search($m[2], $versions, true)) {
                    $version = \count($versions);
                    $versions[] = $m[2];
                }
                for ($i = $start; $i <= $end; ++$i) {
                    $ages[$i] = $version;
                }
            }
        }
        $ageStarts = [];
        $ageVersions = '';
        $last = null;
        foreach ($ages as $codePoint => $age) {
            if ($age !== $last) {
                $ageStarts[] = $codePoint;
                $ageVersions .= \chr($age);
                $last = $age;
            }
        }

        $export = "<?php\n\n// from https://unicode.org/Public/UNIDATA/extracted/DerivedBidiClass.txt, DerivedGeneralCategory.txt, DerivedCombiningClass.txt and DerivedAge.txt:\n// the value of each code point, a byte, in three-stage tables (see Char::value()), and the runs of the ages and their versions\n\nreturn [\n";
        foreach ($tables as $name => $table) {
            $export .= "    '$name' => ['".self::threeStageTable($table)."],\n";
        }
        file_put_contents($out_dir.'properties.php', $export."    'age' => ['".self::deltas($ageStarts)."', '".base64_encode($ageVersions)."', ['".implode("', '", $versions)."']],\n];\n");

        // The simple case mappings, and the simple case foldings (with the Turkic ones)
        $maps = ['lower' => [], 'upper' => [], 'title' => [], 'fold' => [], 'turkic' => []];
        foreach (file(self::getFile('UnicodeData.txt')) as $line) {
            $fields = explode(';', $line);
            $codePoint = \intval($fields[0], 16);
            $upper = '' !== trim($fields[12]) ? \intval($fields[12], 16) : $codePoint;
            $lower = '' !== trim($fields[13]) ? \intval($fields[13], 16) : $codePoint;
            $title = '' !== trim($fields[14]) ? \intval($fields[14], 16) : $upper;
            if ($upper !== $codePoint) {
                $maps['upper'][$codePoint] = $upper;
            }
            if ($lower !== $codePoint) {
                $maps['lower'][$codePoint] = $lower;
            }
            // Kept where it is not the uppercase mapping, like ǅ to itself
            if ($title !== $upper) {
                $maps['title'][$codePoint] = $title;
            }
        }
        foreach (file(self::getFile('CaseFolding.txt')) as $line) {
            if (preg_match('/^([0-9A-F]+); ([CST]); ([0-9A-F]+);/', $line, $m)) {
                $maps['T' === $m[2] ? 'turkic' : 'fold'][\intval($m[1], 16)] = \intval($m[3], 16);
            }
        }

        $numeric = [];
        foreach (file(self::getFile('DerivedNumericValues.txt')) as $line) {
            if (preg_match('/^([0-9A-F.]+)\s*;\s*([-0-9.]+)\s*;\s*;\s*([-0-9\/]+)/', $line, $m)) {
                list($start, $end) = $range($m[1]);
                $parts = explode('/', $m[3]);
                $value = 2 === \count($parts) ? $parts[0] / $parts[1] : (float) $parts[0];
                for ($i = $start; $i <= $end; ++$i) {
                    $numeric[$i] = $value;
                }
            }
        }
        ksort($numeric);

        // The binary properties IntlChar has a constant of, as ranges
        $aliases = ['Sentence_Terminal' => 'S_TERM', 'Prepended_Concatenation_Mark' => 'PREPENDED_CONCATENATION_MARK', 'Regional_Indicator' => 'REGIONAL_INDICATOR', 'Emoji_Component' => 'EMOJI_COMPONENT', 'Extended_Pictographic' => 'EXTENDED_PICTOGRAPHIC'];
        $binary = [];
        foreach (['PropList.txt', 'DerivedCoreProperties.txt', 'emoji-data.txt', 'DerivedBinaryProperties.txt'] as $file) {
            foreach (file(self::getFile($file)) as $line) {
                if (!preg_match('/^([0-9A-F.]+)\s*;\s*(\w+)\s*(?:#|$)/', $line, $m)) {
                    continue;
                }
                $constant = 'PROPERTY_'.(isset($aliases[$m[2]]) ? $aliases[$m[2]] : strtoupper($m[2]));
                if (!\defined('Symfony\\Polyfill\\Intl\\Char\\Char::'.$constant)) {
                    continue;
                }
                list($start, $end) = $range($m[1]);
                $binary[\constant('Symfony\\Polyfill\\Intl\\Char\\Char::'.$constant)][] = [$start, $end];
            }
        }
        ksort($binary);
        foreach ($binary as $property => $ranges) {
            sort($ranges);
            $merged = [];
            foreach ($ranges as list($start, $end)) {
                if ($merged && $merged[\count($merged) - 1] + 1 >= $start) {
                    $merged[\count($merged) - 1] = max($end, $merged[\count($merged) - 1]);
                } else {
                    $merged[] = $start;
                    $merged[] = $end;
                }
            }
            $binary[$property] = $merged;
        }

        // The titlecase mappings where they are not the uppercase ones, and the foldings where they are not the lowercase ones
        $fold = [];
        foreach ($maps['fold'] + $maps['lower'] as $codePoint => $_) {
            $folded = isset($maps['fold'][$codePoint]) ? $maps['fold'][$codePoint] : $codePoint;
            if ($folded !== (isset($maps['lower'][$codePoint]) ? $maps['lower'][$codePoint] : $codePoint)) {
                $fold[$codePoint] = $folded;
            }
        }
        ksort($fold);
        $maps['fold'] = $fold;

        $map = static function (array $map) {
            $entries = [];
            foreach ($map as $key => $value) {
                $entries[] = $key.'=>'.(\is_array($value) ? '['.implode(',', $value).']' : (\is_float($value) ? var_export($value, true) : $value));
            }

            return '['.implode(',', $entries).']';
        };
        $out = "<?php\n\n// from https://unicode.org/Public/UNIDATA/UnicodeData.txt and CaseFolding.txt: the simple case mappings that change a code point, the titlecase ones\n// that are not the uppercase ones, and the foldings that are not the lowercase ones (the Turkic ones apart)\n\nreturn [\n";
        foreach ($maps as $name => $entries) {
            $out .= "    '$name' => ".$map($entries).",\n";
        }
        file_put_contents($out_dir.'caseMapping.php', $out."];\n");
        file_put_contents($out_dir.'numericValue.php', "<?php\n\n// from https://unicode.org/Public/UNIDATA/extracted/DerivedNumericValues.txt\n\nreturn ".$map($numeric).";\n");
        $out = "<?php\n\n// from https://unicode.org/Public/UNIDATA/PropList.txt, DerivedCoreProperties.txt, emoji/emoji-data.txt and extracted/DerivedBinaryProperties.txt:\n// the ranges of code points of each binary property, by its IntlChar::PROPERTY_* constant (see Char::ranges())\n\nreturn [\n";
        foreach ($binary as $property => $bounds) {
            $out .= "    $property => '".self::deltas($bounds)."',\n";
        }
        file_put_contents($out_dir.'binaryProperties.php', $out."];\n");

        // The mirrors and the paired brackets
        $mirrors = [];
        foreach (file(self::getFile('BidiMirroring.txt')) as $line) {
            if (preg_match('/^([0-9A-F]+); ([0-9A-F]+)/', $line, $m)) {
                $mirrors[\intval($m[1], 16)] = \intval($m[2], 16);
            }
        }
        $brackets = [];
        foreach (file(self::getFile('BidiBrackets.txt')) as $line) {
            if (preg_match('/^([0-9A-F]+); ([0-9A-F]+); ([oc])/', $line, $m)) {
                $brackets[\intval($m[1], 16)] = [\intval($m[2], 16), 'o' === $m[3] ? 1 : 2];
            }
        }
        file_put_contents($out_dir.'bidiMirroring.php', "<?php\n\n// from https://unicode.org/Public/UNIDATA/BidiMirroring.txt and BidiBrackets.txt\n\nreturn [\n    'mirrors' => ".$map($mirrors).",\n    'brackets' => ".$map($brackets).",\n];\n");
    }

    /**
     * Splits the values of the code points in a three-stage table, a byte per value: the code points by 1024, each to
     * a block of 64 indexes of blocks of 16 values, the same blocks stored once; the indexes are 16-bit.
     */
    private static function threeStageTable(array $values)
    {
        $data = '';
        $dataBlocks = [];
        $indexes = '';
        $indexBlocks = [];
        $top = '';
        foreach (array_chunk($values, 1024) as $chunk) {
            $index = '';
            foreach (array_chunk($chunk, 16) as $block) {
                $bytes = implode('', array_map('chr', $block));
                if (!isset($dataBlocks[$bytes])) {
                    $dataBlocks[$bytes] = \strlen($data) >> 4;
                    $data .= $bytes;
                }
                $index .= pack('n', $dataBlocks[$bytes]);
            }
            if (!isset($indexBlocks[$index])) {
                $indexBlocks[$index] = \strlen($indexes) >> 7;
                $indexes .= $index;
            }
            $top .= pack('n', $indexBlocks[$index]);
        }

        return base64_encode($top.$indexes.$data).'\', '.(\strlen($top) + \strlen($indexes));
    }

    /**
     * Ranges of code points, their starts and ends one after the other, as the base64 of the variable-length deltas
     * between them, 7 bits a byte.
     */
    private static function deltas(array $bounds)
    {
        $bytes = '';
        $previous = -1;
        foreach ($bounds as $bound) {
            $delta = $bound - $previous;
            $previous = $bound;
            while ($delta >= 0x80) {
                $bytes .= \chr(0x80 | $delta & 0x7F);
                $delta >>= 7;
            }
            $bytes .= \chr($delta);
        }

        return base64_encode($bytes);
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
