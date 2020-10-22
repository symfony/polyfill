<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Uuid;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Uuid\Uuid;

class UuidTest extends TestCase
{
    public function testCreate()
    {
        $this->assertRegExp('{^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', uuid_create());
    }

    public function testCreateTime()
    {
        $this->assertRegExp('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', uuid_create(UUID_TYPE_TIME));
    }

    public function testGenerateMd5()
    {
        $uuidNs = uuid_create();

        $this->assertFalse(@uuid_generate_md5('not a uuid', 'foo'));

        $this->assertRegExp('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $a = uuid_generate_md5($uuidNs, 'foo'));
        $this->assertRegExp('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $b = uuid_generate_md5($uuidNs, 'bar'));
        $this->assertNotSame($a, $b);
        $this->assertSame(UUID_TYPE_MD5, uuid_type($a));
        $this->assertSame(UUID_TYPE_MD5, uuid_type($b));

        $this->assertSame('828658e4-5ae7-39fc-820b-d01a789b1a4d', uuid_generate_md5('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name'));
    }

    public function testGenerateSha1()
    {
        $uuidNs = uuid_create();

        $this->assertFalse(@uuid_generate_sha1('not a uuid', 'foo'));

        $this->assertRegExp('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $a = uuid_generate_sha1($uuidNs, 'foo'));
        $this->assertRegExp('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $b = uuid_generate_sha1($uuidNs, 'bar'));
        $this->assertNotSame($a, $b);
        $this->assertSame(UUID_TYPE_SHA1, uuid_type($a));
        $this->assertSame(UUID_TYPE_SHA1, uuid_type($b));

        if ('851def0c-b9c7-55aa-8991-130e769ec0a9' === uuid_generate_sha1('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name') && '851def0c-b9c7-55aa-8991-130e769ec0a9' === \uuid_generate_sha1('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name')) {
            $this->markTestSkipped('Buggy libuuid.');
        }

        $this->assertSame('851def0c-b9c7-55aa-a991-130e769ec0a9', uuid_generate_sha1('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name'));
    }

    public function provideCreateNoOverlapTests()
    {
        return array(
            array(Uuid::UUID_TYPE_RANDOM),
            array(Uuid::UUID_TYPE_TIME),
        );
    }

    /** @dataProvider provideCreateNoOverlapTests */
    public function testCreateNoOverlap($type)
    {
        $uuids = array();
        $count = 100000;
        for ($i = 0; $i < $count; ++$i) {
            $uuids[] = uuid_create($type);
        }

        $uuids = array_unique($uuids);

        $this->assertCount($count, $uuids);
    }

    public function provideIsValidTest()
    {
        return array(
            array(true, '00000000-0000-0000-0000-000000000000'),
            array(true, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(true, 'FA83B381-328C-46B8-8C90-4E9BA47DFA4B'),
            array(false, 'fa83b381-328c-ZZZZ-8c90-4e9ba47dfa4b'),
            array(false, 'fa83b381328c46b88c904e9ba47dfa4b'),
            array(false, 'foobar'),
        );
    }

    /** @dataProvider provideIsValidTest */
    public function testIsValid($expected, $uuid)
    {
        $this->assertSame($expected, uuid_is_valid($uuid));
    }

    public function testIsNotValid()
    {
        $this->assertFalse(uuid_is_valid('foobar'));
    }

    public function provideCompareTest()
    {
        return array(
            array(false, 'foobar', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(false, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b', 'foobar'),
            array(-1, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4c'),
            array(0, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(1, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4d', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4c'),
        );
    }

    /** @dataProvider provideCompareTest */
    public function testCompareWithUuidNotValid($expected, $uuid1, $uuid2)
    {
        $result = @uuid_compare($uuid1, $uuid2);

        // The result depends of pecl version ...
        if (-1 === $expected) {
            $this->assertTrue($result < 0);
        } elseif (1 === $expected) {
            $this->assertTrue($result > 0);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    public function provideIsNullTest()
    {
        return array(
            array(true, '00000000-0000-0000-0000-000000000000'),
            array(false, '00000000-0000-0000-0000-000000000001'),
            array(false, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(false, 'foobar'),
        );
    }

    /** @dataProvider provideIsNullTest */
    public function testIsNull($expected, $uuid)
    {
        $this->assertSame($expected, @uuid_is_null($uuid));
    }

    public function provideTypeTest()
    {
        return array(
            array(false, 'foobar'),
            array(Uuid::UUID_TYPE_NULL, '00000000-0000-0000-0000-000000000000'),
            array(Uuid::UUID_TYPE_RANDOM, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(Uuid::UUID_TYPE_TIME, 'dbc6260f-e9cc-11e9-8dac-9cb6d0897f07'),
            array(Uuid::UUID_TYPE_TIME, '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'),
        );
    }

    /** @dataProvider provideTypeTest */
    public function testType($expected, $uuid)
    {
        $this->assertSame($expected, @uuid_type($uuid));
    }

    public function provideVariantTest()
    {
        return array(
            array(false, 'foobar'),
            array(Uuid::UUID_TYPE_NULL, '00000000-0000-0000-0000-000000000000'),
            array(Uuid::UUID_VARIANT_DCE, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(Uuid::UUID_VARIANT_DCE, '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'),
        );
    }

    /** @dataProvider provideVariantTest */
    public function testVariant($expected, $uuid)
    {
        $this->assertSame($expected, @uuid_variant($uuid));
    }

    public function provideTimeTest()
    {
        return array(
            array(false, 'foobar'),
            array(false, '00000000-0000-0000-0000-000000000000'),
            array(false, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array(1572444805, '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'),
            array(1572445677, '77ffc38a-fb21-11e9-b46a-3c7de2fa99cb'),
        );
    }

    /** @dataProvider provideTimeTest */
    public function testTime($expected, $uuid)
    {
        $this->assertSame($expected, @uuid_time($uuid));
    }

    public function provideMacTest()
    {
        return array(
            array(false, 'foobar'),
            array(false, '00000000-0000-0000-0000-000000000000'),
            array(false, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array('b52d3e41ad26', '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'),
            array('3c7de2fa99cb', '77ffc38a-fb21-11e9-b46a-3c7de2fa99CB'),
        );
    }

    /** @dataProvider provideMacTest */
    public function testMac($expected, $uuid)
    {
        $this->assertSame($expected, @uuid_mac($uuid));
    }

    public function provideParseTest()
    {
        return array(
            array(false, 'foobar', false),
            array('00000000000000000000000000000000', '00000000-0000-0000-0000-000000000000'),
            array('fa83b381328c46b88c904e9ba47dfa4b', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'),
            array('77ffc38afb2111e9b46a3c7de2fa99cb', '77ffc38a-fb21-11e9-b46a-3c7de2fa99cb'),
        );
    }

    /** @dataProvider provideParseTest */
    public function testParse($expected, $uuid, $bin2hex = true)
    {
        $out = @uuid_parse($uuid);
        if ($bin2hex) {
            $out = bin2hex($out);
        }

        $this->assertSame($expected, $out);
    }

    public function provideUnparseTest()
    {
        return array(
            array(false, 'foobar'),
            array(false, pack('h*', '46b8')),
            array('00000000-0000-0000-0000-000000000000', pack('H*', '00000000000000000000000000000000')),
            array('fa83b381-328c-46b8-8c90-4e9ba47dfa4b', pack('H*', 'fa83b381328c46b88c904e9ba47dfa4b')),
            array('77ffc38a-fb21-11e9-b46a-3c7de2fa99cb', pack('H*', '77ffc38afb2111e9b46a3c7de2fa99cb')),
        );
    }

    /** @dataProvider provideUnparseTest */
    public function testUnparse($expected, $uuid)
    {
        $this->assertSame($expected, @uuid_unparse($uuid));
    }
}
