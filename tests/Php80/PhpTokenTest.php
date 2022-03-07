<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php80;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Php80\PhpToken as PhpTokenPolyfill;

class PhpTokenTest extends TestCase
{
    public function testTokenize()
    {
        $code = file_get_contents(__FILE__);
        $tokens = \PhpToken::tokenize($code);
        $polyfillTokens = PhpTokenPolyfill::tokenize($code);
        $this->assertEqualsCanonicalizing((array) $tokens[1], (array) $polyfillTokens[1]);
        $this->assertEqualsCanonicalizing((array) $tokens[50], (array) $polyfillTokens[50]);
        $this->assertEqualsCanonicalizing((array) $tokens[100], (array) $polyfillTokens[100]);
        $this->assertEqualsCanonicalizing((array) $tokens[150], (array) $polyfillTokens[150]);
        $this->assertEqualsCanonicalizing((array) $tokens[200], (array) $polyfillTokens[200]);
        $this->assertEqualsCanonicalizing((array) $tokens[250], (array) $polyfillTokens[250]);
    }

    public function testGetTokenName()
    {
        // named token
        $token = new \PhpToken(\T_ECHO, 'echo');
        $polyfillToken = new PhpTokenPolyfill(\T_ECHO, 'echo');
        $this->assertSame($token->getTokenName(), $polyfillToken->getTokenName());
        // single char token
        $token = new \PhpToken(\ord(';'), ';');
        $polyfillToken = new PhpTokenPolyfill(\ord(';'), ';');
        $this->assertSame($token->getTokenName(), $polyfillToken->getTokenName());
        // unknown token
        $token = new \PhpToken(10000, "\0");
        $polyfillToken = new PhpTokenPolyfill(10000, "\0");
        $this->assertSame($token->getTokenName(), $polyfillToken->getTokenName());
    }

    public function testIs()
    {
        // single token
        $token = new \PhpToken(\T_ECHO, 'echo');
        $polyfillToken = new PhpTokenPolyfill(\T_ECHO, 'echo');
        $this->assertSame($token->is(\T_ECHO), $polyfillToken->is(\T_ECHO));
        $this->assertSame($token->is('echo'), $polyfillToken->is('echo'));
        $this->assertSame($token->is('T_ECHO'), $polyfillToken->is('T_ECHO'));
        // token set
        $token = new \PhpToken(\T_TRAIT, 'trait');
        $polyfillToken = new PhpTokenPolyfill(\T_TRAIT, 'trait');
        $this->assertSame(
            $token->is([\T_INTERFACE, \T_CLASS, \T_TRAIT]),
            $polyfillToken->is([\T_INTERFACE, \T_CLASS, \T_TRAIT])
        );
        // mixed set
        $token = new \PhpToken(\T_TRAIT, 'trait');
        $polyfillToken = new PhpTokenPolyfill(\T_TRAIT, 'trait');
        $this->assertSame(
            $token->is([\T_INTERFACE, 'class', 334]),
            $polyfillToken->is([\T_INTERFACE, 'class', 334])
        );
    }

    public function testIsIgnorable()
    {
        // not ignorable token
        $token = new \PhpToken(\T_ECHO, 'echo');
        $polyfillToken = new PhpTokenPolyfill(\T_ECHO, 'echo');
        $this->assertSame($token->isIgnorable(), $polyfillToken->isIgnorable());
        // ignorable token
        $token = new \PhpToken(\T_COMMENT, '// todo');
        $polyfillToken = new PhpTokenPolyfill(\T_COMMENT, '// todo');
        $this->assertSame($token->isIgnorable(), $polyfillToken->isIgnorable());
    }
}
