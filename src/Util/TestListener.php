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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

if (class_exists('PHPUnit_Runner_Version') && version_compare(\PHPUnit_Runner_Version::id(), '6.0.0', '<')) {
    class_alias('Symfony\Polyfill\Util\LegacyTestListener', 'Symfony\Polyfill\Util\TestListener');
// Using an early return instead of a else does not work when using the PHPUnit phar due to some weird PHP behavior (the class
// gets defined without executing the code before it and so the definition is not properly conditional)
} else {
    /**
     * @author Nicolas Grekas <p@tchwork.com>
     */
    class TestListener extends TestSuite implements TestListenerInterface
    {
        private $suite;
        private $trait;

        public function __construct(TestSuite $suite = null)
        {
            if ($suite) {
                $this->suite = $suite;
                $this->setName($suite->getName().' with polyfills enabled');
                $this->addTest($suite);
            }
            $this->trait = new TestListenerTrait();
        }

        public function startTestSuite(TestSuite $suite): void
        {
            $this->trait->startTestSuite($suite);
        }

        protected function setUp(): void
        {
            TestListenerTrait::$enabledPolyfills = $this->suite->getName();
        }

        protected function tearDown(): void
        {
            TestListenerTrait::$enabledPolyfills = false;
        }

        public function addError(Test $test, \Throwable $t, float $time): void
        {
            $this->trait->addError($test, $t, $time);
        }

        public function addWarning(Test $test, Warning $e, float $time): void
        {
        }

        public function addFailure(Test $test, AssertionFailedError $e, float $time): void
        {
            $this->trait->addError($test, $e, $time);
        }

        public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
        {
        }

        public function addRiskyTest(Test $test, \Throwable $t, float $time): void
        {
        }

        public function addSkippedTest(Test $test, \Throwable $t, float $time): void
        {
        }

        public function endTestSuite(TestSuite $suite): void
        {
        }

        public function startTest(Test $test): void
        {
        }

        public function endTest(Test $test, $time): void
        {
        }
    }
}
