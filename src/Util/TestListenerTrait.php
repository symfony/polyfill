<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Util;

use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Util\Test;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class TestListenerTrait
{
    public static $enabledPolyfills;

    public function startTestSuite($mainSuite)
    {
        if (null !== self::$enabledPolyfills) {
            return;
        }
        self::$enabledPolyfills = false;
        $warnings = [];

        foreach ($mainSuite->tests() as $suite) {
            $testClass = $suite->getName();
            if (!$tests = $suite->tests()) {
                continue;
            }
            if (\in_array('class-polyfill', Test::getGroups($testClass), true)) {
                // TODO: check signatures for all polyfilled methods on PHP >= 8
                continue;
            }
            $testedClass = new \ReflectionClass($testClass);
            if (preg_match('{^ \* @requires PHP (.*)}mi', $testedClass->getDocComment(), $m) && version_compare($m[1], \PHP_VERSION, '>')) {
                continue;
            }
            if (!preg_match('/^(.+)\\\\Tests(\\\\.*)Test$/', $testClass, $m)) {
                $mainSuite->addTest(TestListener::warning('Unknown naming convention for '.$testClass));
                continue;
            }
            if (!class_exists($m[1].$m[2])) {
                continue;
            }
            $testedClass = new \ReflectionClass($m[1].$m[2]);
            $bootstrap = \dirname($testedClass->getFileName()).'/bootstrap';
            $bootstrap = new \SplFileObject($bootstrap.(\PHP_VERSION_ID >= 80000 && file_exists($bootstrap.'80.php') ? '80' : '').'.php');
            $newWarnings = 0;
            $defLine = null;

            foreach (new \RegexIterator($bootstrap, '/define\(\'/') as $defLine) {
                preg_match("/define\('(?P<name>[^']++)'/", $defLine, $matches);
                if (\defined($matches['name'])) {
                    continue;
                }

                try {
                    eval($defLine);
                } catch (\PHPUnit\Framework\Exception $ex) {
                    $warnings[] = TestListener::warning($ex->getMessage());
                    ++$newWarnings;
                }
            }

            $bootstrap->rewind();

            foreach (new \RegexIterator($bootstrap, '/return p\\\\'.$testedClass->getShortName().'::/') as $defLine) {
                if (!preg_match('/^\s*function (?P<name>[^\(]++)(?P<signature>\(.*\)(?: ?: [^ ]++)?) \{ (?<return>return p\\\\'.$testedClass->getShortName().'::[^\(]++)(?P<args>\([^\n]*?\)); \}$/', $defLine, $f)) {
                    $warnings[] = TestListener::warning('Invalid line in '.$bootstrap->getPathname().': '.trim($defLine));
                    ++$newWarnings;
                    continue;
                }
                $testNamespace = substr($testClass, 0, strrpos($testClass, '\\'));
                if (\function_exists($testNamespace.'\\'.$f['name'])) {
                    continue;
                }

                try {
                    $r = new \ReflectionFunction($f['name']);
                    if ($r->isUserDefined()) {
                        throw new \ReflectionException();
                    }
                    if ('idn_to_ascii' === $f['name'] || 'idn_to_utf8' === $f['name']) {
                        $defLine = sprintf('return PHP_VERSION_ID < 80000 && INTL_IDNA_VARIANT_2003 === $variant ? \\%s($domain, $options, $variant) : \\%1$s%s', $f['name'], $f['args']);
                    } elseif (false !== strpos($f['signature'], '&') && 'idn_to_ascii' !== $f['name'] && 'idn_to_utf8' !== $f['name']) {
                        $defLine = sprintf('return \\%s%s', $f['name'], $f['args']);
                    } else {
                        $defLine = sprintf("return \\call_user_func_array('%s', \\func_get_args())", $f['name']);
                    }
                } catch (\ReflectionException $e) {
                    $r = null;
                    $defLine = sprintf("throw new \\%s('Internal function not found: %s')", SkippedTestError::class, $f['name']);
                }

                eval(<<<EOPHP
namespace {$testNamespace};

use Symfony\Polyfill\Util\TestListenerTrait;
use {$testedClass->getNamespaceName()} as p;

function {$f['name']}{$f['signature']}
{
    if ('{$testClass}' === TestListenerTrait::\$enabledPolyfills) {
        {$f['return']}{$f['args']};
    }

    {$defLine};
}
EOPHP
                );

                if (\PHP_VERSION_ID >= 80000 && $r && false === strpos($bootstrap->getPath(), 'Php7') && false === strpos($bootstrap->getPath(), 'Php80')) {
                    $originalSignature = ReflectionCaster::getSignature(ReflectionCaster::castFunctionAbstract($r, [], new Stub(), true));
                    $polyfillSignature = ReflectionCaster::castFunctionAbstract(new \ReflectionFunction($testNamespace.'\\'.$f['name']), [], new Stub(), true);
                    $polyfillSignature = ReflectionCaster::getSignature($polyfillSignature);

                    $map = [
                        '?' => '',
                        'IDNA_DEFAULT' => \PHP_VERSION_ID >= 80100 ? 'IDNA_DEFAULT' : '0',
                        'array|string|null $string' => 'array|string $string',
                        'array|string|null $from_encoding = null' => 'array|string|null $from_encoding = null',
                        'array|string|null $from_encoding' => 'array|string $from_encoding',
                    ];

                    if (strtr($polyfillSignature, $map) !== $originalSignature) {
                        $warnings[] = TestListener::warning("Incompatible signature for PHP >= 8:\n- {$f['name']}$originalSignature\n+ {$f['name']}$polyfillSignature");
                    }
                }
            }
            if (!$newWarnings && null === $defLine) {
                $warnings[] = TestListener::warning('No polyfills found in bootstrap.php for '.$testClass);
            } else {
                $mainSuite->addTest(new TestListener($suite));
            }
        }
        foreach ($warnings as $w) {
            $mainSuite->addTest($w);
        }
    }

    public function addError($test, \Exception $e, $time)
    {
        if (false !== self::$enabledPolyfills) {
            $r = new \ReflectionProperty('Exception', 'message');
            $r->setAccessible(true);
            $r->setValue($e, 'Polyfills enabled, '.$r->getValue($e));
        }
    }
}
