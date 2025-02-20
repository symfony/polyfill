<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests;

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    /**
     * Test including the PHP polyfills does not try to redefine any symbols.
     */
    public function testIncludingTwice()
    {
        $this->expectNotToPerformAssertions();

        // File should already be loaded once by Composer.
        require __DIR__.'/../src/bootstrap.php';
    }
}
