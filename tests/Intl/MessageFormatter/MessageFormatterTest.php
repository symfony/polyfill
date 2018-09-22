<?php

/*
 * Copyright © 2008 by Yii Software LLC (http://www.yiisoft.com)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  * Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *  * Neither the name of Yii Software LLC nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Originally forked from
 * https://github.com/yiisoft/yii2/blob/2.0.15/tests/framework/i18n/FallbackMessageFormatterTest.php
 */

namespace Symfony\Polyfill\Tests\Intl\MessageFormatter;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\MessageFormatter\MessageFormatter;

/**
 * @author Carsten Brandt <mail@cebe.cc>
 */
class MessageFormatterTest extends TestCase
{
    /**
     * @dataProvider patterns
     */
    public function testNamedArguments($pattern, $expected, array $args)
    {
        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format($args);
        $this->assertEquals($expected, $result, $formatter->getErrorMessage());

        if (\PHP_VERSION_ID < 50500) {
            return;
        }

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format($args);
        $this->assertEquals($expected, $result, $formatter->getErrorMessage());
    }

    public function patterns()
    {
        $subject = 'Answer to the Ultimate Question of Life, the Universe, and Everything';

        return array(
            array(
                '{сабж} is {n}', // pattern
                $subject.' is 42', // expected
                array( // params
                    'n' => 42,
                    'сабж' => $subject,
                ),
            ),

            array(
                '{сабж} is {n, number}', // pattern
                $subject.' is 42', // expected
                array( // params
                    'n' => 42,
                    'сабж' => $subject,
                ),
            ),

            array(
                '{сабж} is {n, number, integer}', // pattern
                $subject.' is 42', // expected
                array( // params
                    'n' => 42,
                    'сабж' => $subject,
                ),
            ),

            array(
                'Here is a big number: {f, number}', // pattern
                'Here is a big number: 200,000,000', // expected
                array( // params
                    'f' => 2e+8,
                ),
            ),

            array(
                'Here is a big number: {f, number, integer}', // pattern
                'Here is a big number: 200,000,000', // expected
                array( // params
                    'f' => 2e+8,
                ),
            ),

            array(
                'Here is a big number: {d, number}', // pattern
                'Here is a big number: 200,000,000.101', // expected
                array( // params
                    'd' => 200000000.101,
                ),
            ),

            array(
                'Here is a big number: {d, number, integer}', // pattern
                'Here is a big number: 200,000,000', // expected
                array( // params
                    'd' => 200000000.101,
                ),
            ),

            // This one was provided by Aura.Intl. Thanks!
            array(<<<'_MSG_'
{gender_of_host, select,
  female {{num_guests, plural, offset:1
      =0 {{host} does not give a party.}
      =1 {{host} invites {guest} to her party.}
      =2 {{host} invites {guest} and one other person to her party.}
     other {{host} invites {guest} and # other people to her party.}}}
  male {{num_guests, plural, offset:1
      =0 {{host} does not give a party.}
      =1 {{host} invites {guest} to his party.}
      =2 {{host} invites {guest} and one other person to his party.}
     other {{host} invites {guest} and # other people to his party.}}}
  other {{num_guests, plural, offset:1
      =0 {{host} does not give a party.}
      =1 {{host} invites {guest} to their party.}
      =2 {{host} invites {guest} and one other person to their party.}
      other {{host} invites {guest} and # other people to their party.}}}}
_MSG_
                ,
                'ralph invites beep and 3 other people to his party.',
                array(
                    'gender_of_host' => 'male',
                    'num_guests' => 4,
                    'host' => 'ralph',
                    'guest' => 'beep',
                ),
            ),

            array(
                '{name} is {gender} and {gender, select, female{she} male{he} other{it}} loves Yii!',
                'Alexander is male and he loves Yii!',
                array(
                    'name' => 'Alexander',
                    'gender' => 'male',
                ),
            ),

            // verify pattern in select does not get replaced
            array(
                '{name} is {gender} and {gender, select, female{she} male{he} other{it}} loves Yii!',
                'Alexander is male and he loves Yii!',
                array(
                    'name' => 'Alexander',
                    'gender' => 'male',
                    // following should not be replaced
                    'he' => 'wtf',
                    'she' => 'wtf',
                    'it' => 'wtf',
                ),
            ),

            // verify pattern in select message gets replaced
            array(
                '{name} is {gender} and {gender, select, female{she} male{{he}} other{it}} loves Yii!',
                'Alexander is male and wtf loves Yii!',
                array(
                    'name' => 'Alexander',
                    'gender' => 'male',
                    'he' => 'wtf',
                    'she' => 'wtf',
                ),
            ),

            // formatting a message that contains params but they are not provided.
            array(
                'Incorrect password (length must be from { min, number } to { max, number } symbols).',
                'Incorrect password (length must be from {min} to {max} symbols).',
                array('attribute' => 'password'),
            ),

            // some parser specific verifications
            array(
                '{gender} and {gender, select, female{she} male{{he}} other{it}} loves {nr} is {gender}!',
                'male and wtf loves 42 is male!',
                array(
                    'nr' => 42,
                    'gender' => 'male',
                    'he' => 'wtf',
                    'she' => 'wtf',
                ),
            ),
        );
    }

    public function testInsufficientArguments()
    {
        $pattern = '{сабж} is {n}';

        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format(array('n' => 42));
        $this->assertEquals('{сабж} is 42', $result);

        if (\PHP_VERSION_ID < 50500) {
            return;
        }

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format(array('n' => 42));
        $this->assertEquals('{сабж} is 42', $result);
    }

    public function testNoParams()
    {
        $pattern = '{сабж} is {n}';

        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format(array());
        $this->assertEquals($pattern, $result, $formatter->getErrorMessage());

        if (\PHP_VERSION_ID < 50500) {
            return;
        }

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format(array());
        $this->assertEquals($pattern, $result, $formatter->getErrorMessage());
    }

    public function testGridViewMessage()
    {
        $pattern = 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.';

        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format(array('begin' => 1, 'end' => 5, 'totalCount' => 10));
        $this->assertEquals('Showing <b>1-5</b> of <b>10</b> items.', $result);

        if (\PHP_VERSION_ID < 50500) {
            return;
        }

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format(array('begin' => 1, 'end' => 5, 'totalCount' => 10));
        $this->assertEquals('Showing <b>1-5</b> of <b>10</b> items.', $result);
    }

    public function testUnsupportedPercentException()
    {
        $pattern = 'Number {n, number, percent}';
        $formatter = new MessageFormatter('en-US', $pattern);
        $this->assertFalse($formatter->format(array('n' => 42)));
    }

    public function testUnsupportedCurrencyException()
    {
        $pattern = 'Number {n, number, currency}';
        $formatter = new MessageFormatter('en-US', $pattern);
        $this->assertFalse($formatter->format(array('n' => 42)));
    }
}
