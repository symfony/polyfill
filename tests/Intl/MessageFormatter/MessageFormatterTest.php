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
 *
 * @group class-polyfill
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

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format($args);
        $this->assertEquals($expected, $result, $formatter->getErrorMessage());
    }

    public function patterns()
    {
        $subject = 'Answer to the Ultimate Question of Life, the Universe, and Everything';

        return [
            [
                '{сабж} is {n}', // pattern
                $subject.' is 42', // expected
                [ // params
                    'n' => 42,
                    'сабж' => $subject,
                ],
            ],

            [
                '{сабж} is {n, number}', // pattern
                $subject.' is 42', // expected
                [ // params
                    'n' => 42,
                    'сабж' => $subject,
                ],
            ],

            [
                '{сабж} is {n, number, integer}', // pattern
                $subject.' is 42', // expected
                [ // params
                    'n' => 42,
                    'сабж' => $subject,
                ],
            ],

            [
                'Here is a big number: {f, number}', // pattern
                'Here is a big number: 200,000,000', // expected
                [ // params
                    'f' => 2e+8,
                ],
            ],

            [
                'Here is a big number: {f, number, integer}', // pattern
                'Here is a big number: 200,000,000', // expected
                [ // params
                    'f' => 2e+8,
                ],
            ],

            [
                'Here is a big number: {d, number}', // pattern
                'Here is a big number: 200,000,000.101', // expected
                [ // params
                    'd' => 200000000.101,
                ],
            ],

            [
                'Here is a big number: {d, number, integer}', // pattern
                'Here is a big number: 200,000,000', // expected
                [ // params
                    'd' => 200000000.101,
                ],
            ],

            [<<<'_MSG_'
{eye_color_of_host, select,
  brown {{num_guests, plural, offset:1
      =0 {{host} has brown eyes and does not give a party.}
      =1 {{host} has brown eyes and invites {guest} to their party.}
      =2 {{host} has brown eyes and invites {guest} and one other person to their party.}
     other {{host} has brown eyes and invites {guest} and # other people to their party.}}}
  green {{num_guests, plural, offset:1
      =0 {{host} has green eyes and does not give a party.}
      =1 {{host} has green eyes and invites {guest} to their party.}
      =2 {{host} has green eyes and invites {guest} and one other person to their party.}
     other {{host} has green eyes and invites {guest} and # other people to their party.}}}
  other {{num_guests, plural, offset:1
      =0 {{host} has pretty eyes and does not give a party.}
      =1 {{host} has pretty eyes and invites {guest} to their party.}
      =2 {{host} has pretty eyes and invites {guest} and one other person to their party.}
      other {{host} has pretty eyes and invites {guest} and # other people to their party.}}}}
_MSG_
                ,
                'Alex has brown eyes and invites Riley and 3 other people to their party.',
                [
                    'eye_color_of_host' => 'brown',
                    'num_guests' => 4,
                    'host' => 'Alex',
                    'guest' => 'Riley',
                ],
            ],

            [
                '{name} has {eye_color} eyes like {eye_color, select, brown{wood} green{grass} other{a bird}}!',
                'Alex has brown eyes like wood!',
                [
                    'name' => 'Alex',
                    'eye_color' => 'brown',
                ],
            ],

            // verify pattern in select does not get replaced
            [
                '{name} has {eye_color} eyes like {eye_color, select, brown{wood} green{grass} other{a bird}}!',
                'Alex has blue eyes like a bird!',
                [
                    'name' => 'Alex',
                    'eye_color' => 'blue',
                    // following should not be replaced
                    'wood' => 'nothing',
                    'grass' => 'nothing',
                    'a bird' => 'nothing',
                ],
            ],

            // verify pattern in select message gets replaced
            [
                '{name} has {eye_color} eyes like {eye_color, select, brown{{wood}} green{grass} other{a bird}}!',
                'Alex has brown eyes like bears!',
                [
                    'name' => 'Alex',
                    'eye_color' => 'brown',
                    'wood' => 'bears',
                    'grass' => 'plants',
                    'a bird' => 'the sea',
                ],
            ],

            // formatting a message that contains params but they are not provided.
            [
                'Incorrect password (length must be from { min, number } to { max, number } symbols).',
                'Incorrect password (length must be from {min} to {max} symbols).',
                ['attribute' => 'password'],
            ],

            // some parser specific verifications
            [
                'Alex has {eye_color} eyes like {eye_color, select, brown{{wood}} other{a bird}} and loves {number}!',
                'Alex has brown eyes like bears and loves 42!',
                [
                    'number' => 42,
                    'eye_color' => 'brown',
                    'wood' => 'bears',
                    'grass' => 'plants',
                ],
            ],
        ];
    }

    public function testInsufficientArguments()
    {
        $pattern = '{сабж} is {n}';

        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format(['n' => 42]);
        $this->assertEquals('{сабж} is 42', $result);

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format(['n' => 42]);
        $this->assertEquals('{сабж} is 42', $result);
    }

    public function testNoParams()
    {
        $pattern = '{сабж} is {n}';

        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format([]);
        $this->assertEquals($pattern, $result, $formatter->getErrorMessage());

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format([]);
        $this->assertEquals($pattern, $result, $formatter->getErrorMessage());
    }

    public function testGridViewMessage()
    {
        $pattern = 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.';

        $formatter = new MessageFormatter('en_US', $pattern);
        $result = $formatter->format(['begin' => 1, 'end' => 5, 'totalCount' => 10]);
        $this->assertEquals('Showing <b>1-5</b> of <b>10</b> items.', $result);

        $formatter = new \MessageFormatter('en_US', $pattern);
        $result = $formatter->format(['begin' => 1, 'end' => 5, 'totalCount' => 10]);
        $this->assertEquals('Showing <b>1-5</b> of <b>10</b> items.', $result);
    }

    public function testUnsupportedPercentException()
    {
        $pattern = 'Number {n, number, percent}';
        $formatter = new MessageFormatter('en-US', $pattern);
        $this->assertFalse($formatter->format(['n' => 42]));
    }

    public function testUnsupportedCurrencyException()
    {
        $pattern = 'Number {n, number, currency}';
        $formatter = new MessageFormatter('en-US', $pattern);
        $this->assertFalse($formatter->format(['n' => 42]));
    }
}
