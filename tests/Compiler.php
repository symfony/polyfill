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

    /**
     * Builds the data of IntlChar from the files of a version of the UCD, the one of the ICU that ext-intl is tested with.
     */
    public static function intlCharMaps($out_dir, $ucd_dir): void
    {
        $file = static function ($name) use ($ucd_dir) {
            return file($ucd_dir.$name);
        };
        $range = static function ($codePoints) {
            $range = explode('..', trim($codePoints));

            return [\intval($range[0], 16), \intval($range[1] ?? $range[0], 16)];
        };
        // The fields of the data lines of a file, and of its @missing lines: code points first
        $records = static function ($name) use ($file) {
            $records = [];
            foreach ($file($name) as $line) {
                if (0 === strpos($line, '# @missing:')) {
                    $line = substr($line, 11);
                } elseif ('' === trim($line) || '#' === $line[0]) {
                    continue;
                }
                $records[] = array_map('trim', explode(';', explode('#', $line, 2)[0]));
            }

            return $records;
        };

        // The short name of every name of the values of each property
        $aliases = [];
        foreach ($records('PropertyValueAliases.txt') as $fields) {
            $short = $fields[1];
            foreach (\array_slice($fields, 1) as $name) {
                $aliases[$fields[0]][$name] = $short;
            }
        }
        // The values of an enumerated property for every code point, numbered like ICU: the defaults of @missing,
        // then the values given, from the field of the value or from the lines of the property in a shared file
        $enumerated = static function ($name, $property, array $values, $default) use ($records, $range, $aliases) {
            $table = array_fill(0, 0x110000, $default);
            $values = array_flip($values);
            foreach ($records($name) as $fields) {
                if (preg_match('/_QC$|^InCB$/', $property)) {
                    if (\count($fields) < 3 || $fields[1] !== $property) {
                        continue;
                    }
                    $fields = [$fields[0], $fields[2]];
                }
                $value = $aliases[$property][$fields[1]] ?? $fields[1];
                if (!isset($values[$value])) {
                    throw new \RuntimeException(\sprintf('Unknown value "%s" of %s in %s.', $fields[1], $property, $name));
                }
                [$start, $end] = $range($fields[0]);
                for ($i = $start; $i <= $end; ++$i) {
                    $table[$i] = $values[$value];
                }
            }

            return $table;
        };

        // The values of the enumerated properties, in the order of ICU's enums (of IntlChar's constants)
        $bidi = ['L', 'R', 'EN', 'ES', 'ET', 'AN', 'CS', 'B', 'S', 'WS', 'ON', 'LRE', 'LRO', 'AL', 'RLE', 'RLO', 'PDF', 'NSM', 'BN', 'FSI', 'LRI', 'RLI', 'PDI'];
        $categories = ['Cn', 'Lu', 'Ll', 'Lt', 'Lm', 'Lo', 'Mn', 'Me', 'Mc', 'Nd', 'Nl', 'No', 'Zs', 'Zl', 'Zp', 'Cc', 'Cf', 'Co', 'Cs', 'Pd', 'Ps', 'Pe', 'Pc', 'Po', 'Sm', 'Sc', 'Sk', 'So', 'Pi', 'Pf'];
        $tables = [
            'bidiClass' => $enumerated('DerivedBidiClass.txt', 'bc', $bidi, 0),
            'generalCategory' => $enumerated('DerivedGeneralCategory.txt', 'gc', $categories, 0),
            'combiningClass' => $enumerated('DerivedCombiningClass.txt', 'ccc', range(0, 255), 0),
            'decompositionType' => $enumerated('DerivedDecompositionType.txt', 'dt', ['None', 'Can', 'Com', 'Enc', 'Fin', 'Font', 'Fra', 'Init', 'Iso', 'Med', 'Nar', 'Nb', 'Sml', 'Sqr', 'Sub', 'Sup', 'Vert', 'Wide'], 0),
            'eastAsianWidth' => $enumerated('EastAsianWidth.txt', 'ea', ['N', 'A', 'H', 'F', 'Na', 'W'], 0),
            'joiningGroup' => $enumerated('DerivedJoiningGroup.txt', 'jg', ['No_Joining_Group', 'Ain', 'Alaph', 'Alef', 'Beh', 'Beth', 'Dal', 'Dalath_Rish', 'E', 'Feh', 'Final_Semkath', 'Gaf', 'Gamal', 'Hah', 'Teh_Marbuta_Goal', 'He', 'Heh', 'Heh_Goal', 'Heth', 'Kaf', 'Kaph', 'Knotted_Heh', 'Lam', 'Lamadh', 'Meem', 'Mim', 'Noon', 'Nun', 'Pe', 'Qaf', 'Qaph', 'Reh', 'Reversed_Pe', 'Sad', 'Sadhe', 'Seen', 'Semkath', 'Shin', 'Swash_Kaf', 'Syriac_Waw', 'Tah', 'Taw', 'Teh_Marbuta', 'Teth', 'Waw', 'Yeh', 'Yeh_Barree', 'Yeh_With_Tail', 'Yudh', 'Yudh_He', 'Zain', 'Fe', 'Khaph', 'Zhain', 'Burushaski_Yeh_Barree', 'Farsi_Yeh', 'Nya', 'Rohingya_Yeh', 'Manichaean_Aleph', 'Manichaean_Ayin', 'Manichaean_Beth', 'Manichaean_Daleth', 'Manichaean_Dhamedh', 'Manichaean_Five', 'Manichaean_Gimel', 'Manichaean_Heth', 'Manichaean_Hundred', 'Manichaean_Kaph', 'Manichaean_Lamedh', 'Manichaean_Mem', 'Manichaean_Nun', 'Manichaean_One', 'Manichaean_Pe', 'Manichaean_Qoph', 'Manichaean_Resh', 'Manichaean_Sadhe', 'Manichaean_Samekh', 'Manichaean_Taw', 'Manichaean_Ten', 'Manichaean_Teth', 'Manichaean_Thamedh', 'Manichaean_Twenty', 'Manichaean_Waw', 'Manichaean_Yodh', 'Manichaean_Zayin', 'Straight_Waw', 'African_Feh', 'African_Noon', 'African_Qaf', 'Malayalam_Bha', 'Malayalam_Ja', 'Malayalam_Lla', 'Malayalam_Llla', 'Malayalam_Nga', 'Malayalam_Nna', 'Malayalam_Nnna', 'Malayalam_Nya', 'Malayalam_Ra', 'Malayalam_Ssa', 'Malayalam_Tta', 'Hanifi_Rohingya_Kinna_Ya', 'Hanifi_Rohingya_Pa', 'Thin_Yeh', 'Vertical_Tail', 'Kashmiri_Yeh', 'Thin_Noon'], 0),
            'joiningType' => $enumerated('DerivedJoiningType.txt', 'jt', ['U', 'C', 'D', 'L', 'R', 'T'], 0),
            'lineBreak' => $enumerated('LineBreak.txt', 'lb', ['XX', 'AI', 'AL', 'B2', 'BA', 'BB', 'BK', 'CB', 'CL', 'CM', 'CR', 'EX', 'GL', 'HY', 'ID', 'IN', 'IS', 'LF', 'NS', 'NU', 'OP', 'PO', 'PR', 'QU', 'SA', 'SG', 'SP', 'SY', 'ZW', 'NL', 'WJ', 'H2', 'H3', 'JL', 'JT', 'JV', 'CP', 'CJ', 'HL', 'RI', 'EB', 'EM', 'ZWJ', 'AK', 'AP', 'AS', 'VF', 'VI', 'HH'], 0),
            'numericType' => $enumerated('DerivedNumericType.txt', 'nt', ['None', 'De', 'Di', 'Nu'], 0),
            'hangulSyllableType' => $enumerated('HangulSyllableType.txt', 'hst', ['NA', 'L', 'V', 'T', 'LV', 'LVT'], 0),
            'nfdQuickCheck' => $enumerated('DerivedNormalizationProps.txt', 'NFD_QC', ['N', 'Y'], 1),
            'nfkdQuickCheck' => $enumerated('DerivedNormalizationProps.txt', 'NFKD_QC', ['N', 'Y'], 1),
            'nfcQuickCheck' => $enumerated('DerivedNormalizationProps.txt', 'NFC_QC', ['N', 'Y', 'M'], 1),
            'nfkcQuickCheck' => $enumerated('DerivedNormalizationProps.txt', 'NFKC_QC', ['N', 'Y', 'M'], 1),
            'graphemeClusterBreak' => $enumerated('GraphemeBreakProperty.txt', 'GCB', ['XX', 'CN', 'CR', 'EX', 'L', 'LF', 'LV', 'LVT', 'T', 'V', 'SM', 'PP', 'RI', 'EB', 'EBG', 'EM', 'GAZ', 'ZWJ'], 0),
            'sentenceBreak' => $enumerated('SentenceBreakProperty.txt', 'SB', ['XX', 'AT', 'CL', 'FO', 'LO', 'NU', 'LE', 'SE', 'SP', 'ST', 'UP', 'CR', 'EX', 'LF', 'SC'], 0),
            'wordBreak' => $enumerated('WordBreakProperty.txt', 'WB', ['XX', 'LE', 'FO', 'KA', 'ML', 'MN', 'NU', 'EX', 'CR', 'Extend', 'LF', 'MB', 'NL', 'RI', 'HL', 'SQ', 'DQ', 'EB', 'EBG', 'EM', 'GAZ', 'ZWJ', 'WSegSpace'], 0),
            'indicPositionalCategory' => $enumerated('IndicPositionalCategory.txt', 'InPC', ['NA', 'Bottom', 'Bottom_And_Left', 'Bottom_And_Right', 'Left', 'Left_And_Right', 'Overstruck', 'Right', 'Top', 'Top_And_Bottom', 'Top_And_Bottom_And_Right', 'Top_And_Left', 'Top_And_Left_And_Right', 'Top_And_Right', 'Visual_Order_Left', 'Top_And_Bottom_And_Left'], 0),
            'indicSyllabicCategory' => $enumerated('IndicSyllabicCategory.txt', 'InSC', ['Other', 'Avagraha', 'Bindu', 'Brahmi_Joining_Number', 'Cantillation_Mark', 'Consonant', 'Consonant_Dead', 'Consonant_Final', 'Consonant_Head_Letter', 'Consonant_Initial_Postfixed', 'Consonant_Killer', 'Consonant_Medial', 'Consonant_Placeholder', 'Consonant_Preceding_Repha', 'Consonant_Prefixed', 'Consonant_Subjoined', 'Consonant_Succeeding_Repha', 'Consonant_With_Stacker', 'Gemination_Mark', 'Invisible_Stacker', 'Joiner', 'Modifying_Letter', 'Non_Joiner', 'Nukta', 'Number', 'Number_Joiner', 'Pure_Killer', 'Register_Shifter', 'Syllable_Modifier', 'Tone_Letter', 'Tone_Mark', 'Virama', 'Visarga', 'Vowel', 'Vowel_Dependent', 'Vowel_Independent', 'Reordering_Killer'], 0),
            'verticalOrientation' => $enumerated('VerticalOrientation.txt', 'vo', ['R', 'Tr', 'Tu', 'U'], 0),
            'identifierStatus' => $enumerated('IdentifierStatus.txt', 'Identifier_Status', ['Restricted', 'Allowed'], 0),
            'indicConjunctBreak' => $enumerated('DerivedCoreProperties.txt', 'InCB', ['None', 'Consonant', 'Extend', 'Linker'], 0),
        ];

        // The canonical decompositions, the case mappings and the ages, from UnicodeData.txt and DerivedAge.txt
        $decompositions = $compatibility = [];
        $maps = ['lower' => [], 'upper' => [], 'title' => [], 'fold' => [], 'turkic' => []];
        foreach ($file('UnicodeData.txt') as $line) {
            $fields = explode(';', $line);
            $codePoint = \intval($fields[0], 16);
            if ('' !== $fields[5]) {
                $compatibility[$codePoint] = array_map('hexdec', explode(' ', preg_replace('/^<\w+> /', '', $fields[5])));
                if ('<' !== $fields[5][0]) {
                    $decompositions[$codePoint] = $compatibility[$codePoint];
                }
            }
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
        // The combining classes of the first and of the last code points of the full canonical decompositions
        $decompose = static function ($codePoint) use (&$decompose, $decompositions) {
            if (!isset($decompositions[$codePoint])) {
                return [$codePoint];
            }

            return array_merge(...array_map($decompose, $decompositions[$codePoint]));
        };
        $tables['leadCombiningClass'] = $tables['trailCombiningClass'] = $tables['combiningClass'];
        foreach ($decompositions as $codePoint => $_) {
            $decomposition = $decompose($codePoint);
            $tables['leadCombiningClass'][$codePoint] = $tables['combiningClass'][$decomposition[0]];
            $tables['trailCombiningClass'][$codePoint] = $tables['combiningClass'][$decomposition[\count($decomposition) - 1]];
        }

        $versions = [''];
        $tables['age'] = array_fill(0, 0x110000, 0);
        foreach ($records('DerivedAge.txt') as $fields) {
            if ('Unassigned' === $fields[1]) {
                continue;
            }
            if (false === $version = array_search($fields[1], $versions, true)) {
                $version = \count($versions);
                $versions[] = $fields[1];
            }
            [$start, $end] = $range($fields[0]);
            for ($i = $start; $i <= $end; ++$i) {
                $tables['age'][$i] = $version;
            }
        }

        @mkdir($out_dir.'tables', 0777, true);
        foreach ($tables as $name => $table) {
            file_put_contents($out_dir.'tables/'.$name.'.php', "<?php\n\n// the value of each code point, numbered like ICU, in a three-stage table (see Char::table())\n\nreturn ['".self::threeStageTable($table).('age' === $name ? ", ['".implode("', '", $versions)."']" : '')."];\n");
        }

        // The simple case foldings (with the Turkic ones), and the full case mappings for Case_Sensitive
        foreach ($file('CaseFolding.txt') as $line) {
            if (preg_match('/^([0-9A-F]+); ([CST]); ([0-9A-F]+);/', $line, $m)) {
                $maps['T' === $m[2] ? 'turkic' : 'fold'][\intval($m[1], 16)] = \intval($m[3], 16);
            }
        }
        $caseSensitive = [];
        foreach ($maps as $map) {
            foreach ($map as $from => $to) {
                if ($from !== $to) {
                    $caseSensitive[$from] = $caseSensitive[$to] = true;
                }
            }
        }
        // The full mappings of SpecialCasing.txt (the unconditional ones) and the full foldings of CaseFolding.txt
        $full = [];
        foreach ($records('SpecialCasing.txt') as $fields) {
            if ('' === $fields[4]) {
                $full[] = [$fields[0], $fields[1], $fields[2], $fields[3]];
            }
        }
        foreach ($records('CaseFolding.txt') as $fields) {
            if ('F' === $fields[1]) {
                $full[] = [$fields[0], $fields[2]];
            }
        }
        foreach ($full as $fields) {
            $from = \intval($fields[0], 16);
            foreach (\array_slice($fields, 1) as $target) {
                $target = array_map('hexdec', preg_split('/\s+/', trim($target)));
                if ([$from] !== $target) {
                    $caseSensitive[$from] = true;
                    foreach ($target as $codePoint) {
                        $caseSensitive[$codePoint] = true;
                    }
                }
            }
        }

        $numeric = [];
        foreach ($records('DerivedNumericValues.txt') as $fields) {
            if ('NaN' === $fields[1]) {
                continue;
            }
            $parts = explode('/', $fields[3]);
            $value = 2 === \count($parts) ? $parts[0] / $parts[1] : (float) $parts[0];
            [$start, $end] = $range($fields[0]);
            for ($i = $start; $i <= $end; ++$i) {
                $numeric[$i] = $value;
            }
        }
        ksort($numeric);

        // The binary properties, by the numbers of ICU's UProperty
        $properties = array_flip(['Alphabetic', 'ASCII_Hex_Digit', 'Bidi_Control', 'Bidi_Mirrored', 'Dash', 'Default_Ignorable_Code_Point', 'Deprecated', 'Diacritic', 'Extender', 'Full_Composition_Exclusion', 'Grapheme_Base', 'Grapheme_Extend', 'Grapheme_Link', 'Hex_Digit', 'Hyphen', 'ID_Continue', 'ID_Start', 'Ideographic', 'IDS_Binary_Operator', 'IDS_Trinary_Operator', 'Join_Control', 'Logical_Order_Exception', 'Lowercase', 'Math', 'Noncharacter_Code_Point', 'Quotation_Mark', 'Radical', 'Soft_Dotted', 'Terminal_Punctuation', 'Unified_Ideograph', 'Uppercase', 'White_Space', 'XID_Continue', 'XID_Start', 'Case_Sensitive', 'Sentence_Terminal', 'Variation_Selector', 'NFD_Inert', 'NFKD_Inert', 'NFC_Inert', 'NFKC_Inert', 'Segment_Starter', 'Pattern_Syntax', 'Pattern_White_Space', 'alnum', 'blank', 'graph', 'print', 'xdigit', 'Cased', 'Case_Ignorable', 'Changes_When_Lowercased', 'Changes_When_Uppercased', 'Changes_When_Titlecased', 'Changes_When_Casefolded', 'Changes_When_Casemapped', 'Changes_When_NFKC_Casefolded', 'Emoji', 'Emoji_Presentation', 'Emoji_Modifier', 'Emoji_Modifier_Base', 'Emoji_Component', 'Regional_Indicator', 'Prepended_Concatenation_Mark', 'Extended_Pictographic', 'Basic_Emoji', 'Emoji_Keycap_Sequence', 'RGI_Emoji_Modifier_Sequence', 'RGI_Emoji_Flag_Sequence', 'RGI_Emoji_Tag_Sequence', 'RGI_Emoji_ZWJ_Sequence', 'RGI_Emoji', 'IDS_Unary_Operator', 'ID_Compat_Math_Start', 'ID_Compat_Math_Continue', 'Modifier_Combining_Mark']);
        $sets = array_fill(0, \count($properties), []);
        foreach (['PropList.txt', 'DerivedCoreProperties.txt', 'emoji-data.txt', 'DerivedBinaryProperties.txt', 'DerivedNormalizationProps.txt', 'emoji-sequences.txt'] as $name) {
            foreach ($records($name) as $fields) {
                // The code points of Basic_Emoji are those of RGI_Emoji too, its sequences are not code points
                if ('emoji-sequences.txt' === $name && ('Basic_Emoji' !== $fields[1] || false !== strpos($fields[0], ' '))) {
                    continue;
                }
                if ((2 === \count($fields) || 'emoji-sequences.txt' === $name) && isset($properties[$fields[1]])) {
                    [$start, $end] = $range($fields[0]);
                    for ($i = $start; $i <= $end; ++$i) {
                        $sets[$properties[$fields[1]]][$i] = true;
                        if ('emoji-sequences.txt' === $name) {
                            $sets[$properties['RGI_Emoji']][$i] = true;
                        }
                    }
                }
            }
        }
        $sets[$properties['Case_Sensitive']] = $caseSensitive;

        // The properties ICU derives from its normalization data: NF*_Inert and Segment_Starter
        $ccc = $tables['combiningClass'];
        $pairs = [];
        foreach ($decompositions as $codePoint => $decomposition) {
            if (2 === \count($decomposition) && !isset($sets[$properties['Full_Composition_Exclusion']][$codePoint])) {
                $pairs[$decomposition[0]][$decomposition[1]] = $codePoint;
            }
        }
        $compose = static function ($starter, $codePoint) use ($pairs) {
            if (0x1100 <= $starter && $starter <= 0x1112 && 0x1161 <= $codePoint && $codePoint <= 0x1175) {
                return 0xAC00 + (($starter - 0x1100) * 21 + $codePoint - 0x1161) * 28;
            }
            if (0xAC00 <= $starter && $starter <= 0xD7A3 && 0 === ($starter - 0xAC00) % 28 && 0x11A8 <= $codePoint && $codePoint <= 0x11C2) {
                return $starter + $codePoint - 0x11A7;
            }

            return $pairs[$starter][$codePoint] ?? null;
        };
        $fullDecomposition = static function ($codePoint, array $mappings) use (&$fullDecomposition) {
            if (0xAC00 <= $codePoint && $codePoint <= 0xD7A3) {
                $s = $codePoint - 0xAC00;

                return $s % 28 ? [0x1100 + intdiv($s, 588), 0x1161 + intdiv($s % 588, 28), 0x11A7 + $s % 28] : [0x1100 + intdiv($s, 588), 0x1161 + intdiv($s % 588, 28)];
            }
            if (!isset($mappings[$codePoint])) {
                return [$codePoint];
            }
            $decomposition = [];
            foreach ($mappings[$codePoint] as $c) {
                array_push($decomposition, ...$fullDecomposition($c, $mappings));
            }

            return $decomposition;
        };
        // The composition of a string, its decomposition reordered, then composed
        $normalize = static function (array $codePoints, array $mappings) use ($fullDecomposition, $compose, $ccc) {
            $decomposed = [];
            foreach ($codePoints as $c) {
                foreach ($fullDecomposition($c, $mappings) as $c) {
                    for ($j = \count($decomposed); $j > 0 && 0 !== $ccc[$c] && $ccc[$decomposed[$j - 1]] > $ccc[$c]; --$j) {
                    }
                    array_splice($decomposed, $j, 0, [$c]);
                }
            }
            $composed = [];
            $starter = -1;
            $last = 256;
            foreach ($decomposed as $c) {
                if (0 <= $starter && ($last < $ccc[$c] || (0 === $last && \count($composed) - 1 === $starter)) && null !== $r = $compose($composed[$starter], $c)) {
                    $composed[$starter] = $r;
                    continue;
                }
                if (0 === $last = $ccc[$c]) {
                    $starter = \count($composed);
                }
                $composed[] = $c;
            }

            return $composed;
        };
        // The characters that can follow and change a composition: those that compose, and a mark of each combining class
        $following = array_fill(0x1161, 0x1175 - 0x1161 + 1, true) + array_fill(0x11A8, 0x11C2 - 0x11A8 + 1, true);
        foreach ($pairs as $second) {
            $following += array_fill_keys(array_keys($second), true);
        }
        $classes = [];
        foreach ($ccc as $codePoint => $class) {
            if ($class && !isset($classes[$class])) {
                $following[$codePoint] = $classes[$class] = true;
            }
        }
        foreach ([['NFD_Inert', 'nfdQuickCheck', false, $decompositions], ['NFKD_Inert', 'nfkdQuickCheck', false, $compatibility], ['NFC_Inert', 'nfcQuickCheck', true, $decompositions], ['NFKC_Inert', 'nfkcQuickCheck', true, $compatibility]] as [$property, $quickCheck, $composing, $mappings]) {
            $normalized = [];
            foreach ($following as $c => $_) {
                $normalized[$c] = $normalize([$c], $mappings);
            }
            for ($c = 0; $c <= 0x10FFFF; ++$c) {
                if (1 !== $tables[$quickCheck][$c] || 0 !== $ccc[$c]) {
                    continue;
                }
                // A character that does not decompose and does not compose with the following ones, or whose composition
                // is not changed by any following character
                if ($composing && (isset($mappings[$c]) || isset($pairs[$c]) || (0x1100 <= $c && $c <= 0x1112) || (0xAC00 <= $c && $c <= 0xD7A3 && 0 === ($c - 0xAC00) % 28))) {
                    $self = $normalize([$c], $mappings);
                    foreach ($normalized as $next => $nextNormalized) {
                        if ($normalize([$c, $next], $mappings) !== array_merge($self, $nextNormalized)) {
                            continue 2;
                        }
                    }
                }
                $sets[$properties[$property]][$c] = true;
            }
        }
        // Not a segment starter: a non-starter, the non-first characters of the canonical decompositions, the characters
        // that combine backward and do not decompose
        $notStarters = [];
        foreach ($decompositions as $codePoint => $_) {
            foreach (\array_slice($fullDecomposition($codePoint, $decompositions), 1) as $c) {
                $notStarters[$c] = true;
            }
        }
        for ($c = 0; $c <= 0x10FFFF; ++$c) {
            if (0 === $ccc[$c] && (2 !== $tables['nfcQuickCheck'][$c] || isset($decompositions[$c])) && !isset($notStarters[$c])) {
                $sets[$properties['Segment_Starter']][$c] = true;
            }
        }
        // The POSIX properties, from the general category like ICU's u_is*POSIX()
        $category = $tables['generalCategory'];
        for ($i = 0; $i <= 0x10FFFF; ++$i) {
            $gc = $categories[$category[$i]];
            if (isset($sets[$properties['Alphabetic']][$i]) || 'Nd' === $gc) {
                $sets[$properties['alnum']][$i] = true;
            }
            if ($i <= 0x9F ? 0x09 === $i || 0x20 === $i : 'Zs' === $gc) {
                $sets[$properties['blank']][$i] = true;
            }
            if (!\in_array($gc, ['Cc', 'Cs', 'Cn', 'Zs', 'Zl', 'Zp'], true)) {
                $sets[$properties['graph']][$i] = $sets[$properties['print']][$i] = true;
            } elseif ('Zs' === $gc) {
                $sets[$properties['print']][$i] = true;
            }
            if ('Nd' === $gc || (0x41 <= $i && $i <= 0x46) || (0x61 <= $i && $i <= 0x66) || (0xFF21 <= $i && $i <= 0xFF26) || (0xFF41 <= $i && $i <= 0xFF46)) {
                $sets[$properties['xdigit']][$i] = true;
            }
        }
        $binary = [];
        foreach ($sets as $property => $set) {
            ksort($set);
            $bounds = [];
            foreach ($set as $codePoint => $_) {
                if ($bounds && $bounds[\count($bounds) - 1] + 1 === $codePoint) {
                    $bounds[\count($bounds) - 1] = $codePoint;
                } else {
                    $bounds[] = $codePoint;
                    $bounds[] = $codePoint;
                }
            }
            $binary[$property] = $bounds;
        }

        // The titlecase mappings where they are not the uppercase ones, and the foldings where they are not the lowercase ones
        $fold = [];
        foreach ($maps['fold'] + $maps['lower'] as $codePoint => $_) {
            $folded = $maps['fold'][$codePoint] ?? $codePoint;
            if ($folded !== ($maps['lower'][$codePoint] ?? $codePoint)) {
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
        $out = "<?php\n\n// from UnicodeData.txt and CaseFolding.txt: the simple case mappings that change a code point, the titlecase ones\n// that are not the uppercase ones, and the foldings that are not the lowercase ones (the Turkic ones apart)\n\nreturn [\n";
        foreach ($maps as $name => $entries) {
            $out .= "    '$name' => ".$map($entries).",\n";
        }
        file_put_contents($out_dir.'caseMapping.php', $out."];\n");
        file_put_contents($out_dir.'numericValue.php', "<?php\n\n// from extracted/DerivedNumericValues.txt\n\nreturn ".$map($numeric).";\n");
        $out = "<?php\n\n// from PropList.txt, DerivedCoreProperties.txt, emoji/emoji-data.txt, extracted/DerivedBinaryProperties.txt, DerivedNormalizationProps.txt,\n// emoji/emoji-sequences.txt, the case mappings and the general categories: the ranges of code points of each binary\n// property, by its number in ICU (see Char::pages())\n\nreturn [\n";
        foreach ($binary as $property => $bounds) {
            if ($bounds) {
                $out .= "    $property => '".self::deltas($bounds)."',\n";
            }
        }
        file_put_contents($out_dir.'binaryProperties.php', $out."];\n");

        // The mirrors and the paired brackets
        $mirrors = [];
        foreach ($file('BidiMirroring.txt') as $line) {
            if (preg_match('/^([0-9A-F]+); ([0-9A-F]+)/', $line, $m)) {
                $mirrors[\intval($m[1], 16)] = \intval($m[2], 16);
            }
        }
        $brackets = [];
        foreach ($file('BidiBrackets.txt') as $line) {
            if (preg_match('/^([0-9A-F]+); ([0-9A-F]+); ([oc])/', $line, $m)) {
                $brackets[\intval($m[1], 16)] = [\intval($m[2], 16), 'o' === $m[3] ? 1 : 2];
            }
        }
        file_put_contents($out_dir.'bidiMirroring.php', "<?php\n\n// from BidiMirroring.txt and BidiBrackets.txt\n\nreturn [\n    'mirrors' => ".$map($mirrors).",\n    'brackets' => ".$map($brackets).",\n];\n");
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
