#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Internal\Compiler;

require __DIR__.'/Compiler.php';

@mkdir(__DIR__.'/unicode/data', 0777, true);

foreach (['UnicodeData.txt', 'CompositionExclusions.txt', 'CaseFolding.txt', 'SpecialCasing.txt'] as $file) {
    $data = file_get_contents('https://unicode.org/Public/UNIDATA/'.$file);
    file_put_contents(__DIR__.'/unicode/data/'.$file, $data);
}

$data = file_get_contents('https://github.com/unicode-org/cldr/raw/main/common/transforms/Latin-ASCII.xml');
file_put_contents(__DIR__.'/unicode/data/Latin-ASCII.xml', $data);

Compiler::translitMap(__DIR__.'/../src/Iconv/Resources/charset/');
Compiler::unicodeMaps(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/');

rename(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/lowerCase.php', __DIR__.'/../src/Mbstring/Resources/unidata/lowerCase.php');
rename(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/upperCase.php', __DIR__.'/../src/Mbstring/Resources/unidata/upperCase.php');
rename(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/titleCase.php', __DIR__.'/../src/Mbstring/Resources/unidata/titleCase.php');
unlink(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/caseFolding_full.php');

$data = file_get_contents('https://unicode.org/Public/UNIDATA/EastAsianWidth.txt');
file_put_contents(__DIR__.'/unicode/data/EastAsianWidth.txt', $data);

Compiler::eastAsianWidthRegexp(__DIR__.'/../src/Mbstring/Resources/unidata/');

$data = file_get_contents('http://www.unicode.org/Public/UNIDATA/NormalizationTest.txt');
file_put_contents(__DIR__.'/Intl/Normalizer/NormalizationTest.txt', $data);

$data = file_get_contents('http://www.unicode.org/Public/idna/latest/IdnaMappingTable.txt');
file_put_contents(__DIR__.'/unicode/data/IdnaMappingTable.txt', $data);

foreach (['DerivedBidiClass.txt', 'DerivedCombiningClass.txt', 'DerivedGeneralCategory.txt', 'DerivedJoiningType.txt'] as $file) {
    $data = file_get_contents('http://www.unicode.org/Public/UNIDATA/extracted/'.$file);
    file_put_contents(__DIR__.'/unicode/data/'.$file, $data);
}

Compiler::idnMaps(__DIR__.'/../src/Intl/Idn/Resources/unidata/');
Compiler::idnRegexClass(__DIR__.'/../src/Intl/Idn/Resources/unidata/');
Compiler::idnViramaMap(__DIR__.'/../src/Intl/Idn/Resources/unidata/');

$data = file_get_contents('http://www.unicode.org/Public/idna/latest/IdnaTestV2.txt');
file_put_contents(__DIR__.'/Intl/Idn/IdnaTestV2.txt', $data);

// IntlChar gives the data of the version of Unicode of the ICU it is tested with, not the latest one
$ucd = __DIR__.'/unicode/data/ucd-17.0.0/';
@mkdir($ucd, 0777, true);
foreach (['UnicodeData.txt', 'CaseFolding.txt', 'SpecialCasing.txt', 'BidiMirroring.txt', 'BidiBrackets.txt', 'PropList.txt', 'PropertyValueAliases.txt', 'DerivedAge.txt', 'DerivedCoreProperties.txt', 'DerivedNormalizationProps.txt', 'EastAsianWidth.txt', 'LineBreak.txt', 'HangulSyllableType.txt', 'IndicPositionalCategory.txt', 'IndicSyllabicCategory.txt', 'VerticalOrientation.txt', 'emoji/emoji-data.txt', 'auxiliary/GraphemeBreakProperty.txt', 'auxiliary/SentenceBreakProperty.txt', 'auxiliary/WordBreakProperty.txt', 'extracted/DerivedBidiClass.txt', 'extracted/DerivedBinaryProperties.txt', 'extracted/DerivedCombiningClass.txt', 'extracted/DerivedDecompositionType.txt', 'extracted/DerivedGeneralCategory.txt', 'extracted/DerivedJoiningGroup.txt', 'extracted/DerivedJoiningType.txt', 'extracted/DerivedNumericType.txt', 'extracted/DerivedNumericValues.txt'] as $file) {
    file_put_contents($ucd.basename($file), file_get_contents('https://unicode.org/Public/17.0.0/ucd/'.$file));
}
foreach (['emoji/emoji-sequences.txt', 'security/IdentifierStatus.txt'] as $file) {
    file_put_contents($ucd.basename($file), file_get_contents('https://unicode.org/Public/17.0.0/'.$file));
}

Compiler::intlCharMaps(__DIR__.'/../src/Intl/Char/Resources/unidata/', $ucd);
