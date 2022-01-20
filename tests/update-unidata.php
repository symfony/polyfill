#!/usr/bin/env php
<?php

use Symfony\Polyfill\Internal\Compiler;

require __DIR__.'/Compiler.php';

@mkdir(__DIR__.'/unicode/data', 0777, true);

foreach (['UnicodeData.txt', 'CompositionExclusions.txt', 'CaseFolding.txt', 'SpecialCasing.txt'] as $file) {
    $data = file_get_contents('https://unicode.org/Public/UNIDATA/'.$file);
    file_put_contents(__DIR__.'/unicode/data/'.$file, $data);
}

$data = file_get_contents('https://github.com/unicode-org/cldr/raw/master/common/transforms/Latin-ASCII.xml');
file_put_contents(__DIR__.'/unicode/data/Latin-ASCII.xml', $data);

Compiler::translitMap(__DIR__.'/../src/Iconv/Resources/charset/');
Compiler::unicodeMaps(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/');

rename(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/lowerCase.php', __DIR__.'/../src/Mbstring/Resources/unidata/lowerCase.php');
rename(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/upperCase.php', __DIR__.'/../src/Mbstring/Resources/unidata/upperCase.php');
unlink(__DIR__.'/../src/Intl/Normalizer/Resources/unidata/caseFolding_full.php');

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
