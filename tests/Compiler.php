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
        $map = array();

        $h = fopen(self::getFile('UnicodeData.txt'), 'r');
        while (false !== $line = fgets($h)) {
            $m = array();

            if (preg_match('/^([^;]*);[^;]*;[^;]*;[^;]*;[^;]*;<(circle|compat|font|fraction|narrow|small|square|wide)> ([^;]*);/', $line, $m)) {
                $m[1] = self::chr(hexdec($m[1]));

                $m[3] = explode(' ', $m[3]);
                $m[3] = array_map('hexdec', $m[3]);
                $m[3] = array_map(array(__CLASS__, 'chr'), $m[3]);
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

                $m = array($m[1], $m[3]);
            } elseif (preg_match('/^([^;]*);CJK COMPATIBILITY IDEOGRAPH-[^;]*;[^;]*;[^;]*;[^;]*;([^;]*);/', $line, $m)) {
                $m = array(
                    self::chr(hexdec($m[1])),
                    self::chr(hexdec($m[2])),
                );
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
        $upperCase = array();
        $lowerCase = array();
        $caseFolding = array();
        $combiningClass = array();
        $canonicalComposition = array();
        $canonicalDecomposition = array();
        $compatibilityDecomposition = array();

        $exclusion = array();

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
                $decomp = array_map(array(__CLASS__, 'chr'), $decomp);
                $decomp = implode('', $decomp);

                if ($canonic) {
                    $canonicalDecomposition[$k] = $decomp;
                    $exclude || $canonicalComposition[$decomp] = $k;
                }

                $compatibilityDecomposition[$k] = $decomp;
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
                $decomp = array_map(array(__CLASS__, 'chr'), $decomp);
                $decomp = implode('', $decomp);

                @($lowerCase[$k] != $decomp && $caseFolding[$m[2]][$k] = $decomp);
            }
        }
        fclose($h);

        // Only full case folding is worth serializing
        $caseFolding = array(
            array_keys($caseFolding['F']),
            array_values($caseFolding['F']),
        );

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

    protected static function chr($c)
    {
        $c %= 0x200000;

        return $c <    0x80 ? \chr($c) : (
               $c <   0x800 ? \chr(0xC0 | $c >> 6).\chr(0x80 | $c & 0x3F) : (
               $c < 0x10000 ? \chr(0xE0 | $c >> 12).\chr(0x80 | $c >> 6 & 0x3F).\chr(0x80 | $c & 0x3F) : (
                              \chr(0xF0 | $c >> 18).\chr(0x80 | $c >> 12 & 0x3F).\chr(0x80 | $c >> 6 & 0x3F).\chr(0x80 | $c & 0x3F)
        )));
    }

    protected static function getFile($file)
    {
        return __DIR__.'/unicode/data/'.$file;
    }
}
