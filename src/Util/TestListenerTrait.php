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

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

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
        $SkippedTestError = class_exists('PHPUnit\Framework\SkippedTestError') ? 'PHPUnit\Framework\SkippedTestError' : 'PHPUnit_Framework_SkippedTestError';
        $warnings = array();
        foreach ($mainSuite->tests() as $suite) {
            $testClass = $suite->getName();
            if (!$tests = $suite->tests()) {
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
            $filename = \dirname($testedClass->getFileName()).'/bootstrap.php';
            $bootstrap = new \SplFileObject($filename);
            if (PHP_VERSION_ID >= 80000) {
                $warnings = array_merge($warnings, static::scanFileForInvalidSignatures($filename));
            }
            $defLine = null;

            foreach (new \RegexIterator($bootstrap, '/define\(\'/') as $defLine) {
                preg_match('/define\(\'(?P<name>.+)\'/', $defLine, $matches);
                if (\defined($matches['name'])) {
                    continue;
                }

                try {
                    eval($defLine);
                } catch (\PHPUnit_Framework_Exception $ex){
                    $warnings[] = TestListener::warning($ex->getMessage());
                } catch (\PHPUnit\Framework\Exception $ex) {
                    $warnings[] = TestListener::warning($ex->getMessage());
                }
            }

            $bootstrap->rewind();

            foreach (new \RegexIterator($bootstrap, '/return p\\\\'.$testedClass->getShortName().'::/') as $defLine) {
                if (!preg_match('/^\s*function (?P<name>[^\(]++)(?P<signature>\(.*\)(?: ?: [^ ]++)?) \{ (?<return>return p\\\\'.$testedClass->getShortName().'::[^\(]++)(?P<args>\([^\)]*+\)); \}$/', $defLine, $f)) {
                    $warnings[] = TestListener::warning('Invalid line in bootstrap.php: '.trim($defLine));
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
                        $defLine = sprintf('return INTL_IDNA_VARIANT_2003 === $variant ? \\%s($domain, $options, $variant) : \\%1$s%s', $f['name'], $f['args']);
                    } elseif (false !== strpos($f['signature'], '&') && 'idn_to_ascii' !== $f['name'] && 'idn_to_utf8' !== $f['name']) {
                        $defLine = sprintf('return \\%s%s', $f['name'], $f['args']);
                    } else {
                        $defLine = sprintf("return \\call_user_func_array('%s', \\func_get_args())", $f['name']);
                    }
                } catch (\ReflectionException $e) {
                    $defLine = sprintf("throw new \\{$SkippedTestError}('Internal function not found: %s')", $f['name']);
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
            }
            if (!$warnings && null === $defLine) {
                $warnings[] = new $SkippedTestError('No Polyfills found in bootstrap.php for '.$testClass);
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

    /**
     * @param string|string[] $files
     */
    private static function scanFileForInvalidSignatures($file)
    {
        $betterReflection = new BetterReflection();
        $locator = new SingleFileSourceLocator($file, $betterReflection->astLocator());
        $reflector = new FunctionReflector($locator, $betterReflection->classReflector());
        $polyfills = $reflector->getAllFunctions();
        $warnings = array();
        foreach ($polyfills as $polyfill) {
            $function = $polyfill->getName();
            if (!\function_exists($function)) {
                continue;
            }

            $originalFunction = new \ReflectionFunction($function);

            $warnings[] = static::compareParameters($function, $originalFunction->getParameters(), $polyfill->getParameters());
        }

        return array_filter($warnings);
    }

    private static function compareParameters($methodOrFunction, $original, $polyfill)
    {
        $callable = function($parameter) { return $parameter->getName(); };
        $polyfillParameters = array_map($callable, $polyfill);
        $originalParameters = array_map($callable, $original);
        if ($polyfillParameters !== $originalParameters) {
            $toString = function($parameters) {
                return implode(', ', array_map(function($parameter) {
                    return '$'.$parameter;
                }, $parameters));
            };

            $polyfillParametersString = $toString($polyfillParameters);
            $originalParametersString = $toString($originalParameters);

            return TestListener::warning(<<<FMT
The polyfill for "$methodOrFunction" has an incorrect signature.

Expected : $originalParametersString
Actual   : $polyfillParametersString
FMT
);
        }

        return null;
    }
}
