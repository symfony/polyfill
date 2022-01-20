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
        $this->assertMatchesRegularExpression('{^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', uuid_create());
    }

    public function testCreateTime()
    {
        $this->assertMatchesRegularExpression('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', uuid_create(\UUID_TYPE_TIME));
    }

    public function testGenerateMd5()
    {
        $uuidNs = uuid_create();

        $this->assertMatchesRegularExpression('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $a = uuid_generate_md5($uuidNs, 'foo'));
        $this->assertMatchesRegularExpression('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $b = uuid_generate_md5($uuidNs, 'bar'));
        $this->assertNotSame($a, $b);
        $this->assertSame(\UUID_TYPE_MD5, uuid_type($a));
        $this->assertSame(\UUID_TYPE_MD5, uuid_type($b));

        $this->assertSame('828658e4-5ae7-39fc-820b-d01a789b1a4d', uuid_generate_md5('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name'));
    }

    public function testGenerateMd5WithInvalidUuid()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_generate_md5(): Argument #1 ($uuid_ns) UUID expected');
        }

        $this->assertFalse(@uuid_generate_md5('not a uuid', 'foo'));
    }

    public function testGenerateSha1()
    {
        $uuidNs = uuid_create();

        $this->assertMatchesRegularExpression('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $a = uuid_generate_sha1($uuidNs, 'foo'));
        $this->assertMatchesRegularExpression('{^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$}', $b = uuid_generate_sha1($uuidNs, 'bar'));
        $this->assertNotSame($a, $b);
        $this->assertSame(\UUID_TYPE_SHA1, uuid_type($a));
        $this->assertSame(\UUID_TYPE_SHA1, uuid_type($b));

        if ('851def0c-b9c7-55aa-8991-130e769ec0a9' === uuid_generate_sha1('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name') && '851def0c-b9c7-55aa-8991-130e769ec0a9' === uuid_generate_sha1('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name')) {
            $this->markTestSkipped('Buggy libuuid.');
        }

        $this->assertSame('851def0c-b9c7-55aa-a991-130e769ec0a9', uuid_generate_sha1('ec07aa88-f84e-47b9-a581-1c6b30a2f484', 'the name'));
    }

    public function testGenerateSha1WithInvalidUuid()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_generate_sha1(): Argument #1 ($uuid_ns) UUID expected');
        }

        $this->assertFalse(@uuid_generate_sha1('not a uuid', 'foo'));
    }

    public function provideCreateNoOverlapTests(): array
    {
        return [
            [Uuid::UUID_TYPE_RANDOM],
            [Uuid::UUID_TYPE_TIME],
        ];
    }

    /** @dataProvider provideCreateNoOverlapTests */
    public function testCreateNoOverlap(int $type)
    {
        $uuids = [];
        $count = 100000;
        for ($i = 0; $i < $count; ++$i) {
            $uuids[] = uuid_create($type);
        }

        $uuids = array_unique($uuids);

        $this->assertCount($count, $uuids);
    }

    public function provideIsValidTest(): array
    {
        return [
            [true, '00000000-0000-0000-0000-000000000000'],
            [true, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
            [true, 'FA83B381-328C-46B8-8C90-4E9BA47DFA4B'],
            [false, 'fa83b381-328c-ZZZZ-8c90-4e9ba47dfa4b'],
            [false, 'fa83b381328c46b88c904e9ba47dfa4b'],
            [false, 'foobar'],
        ];
    }

    /** @dataProvider provideIsValidTest */
    public function testIsValid(bool $expected, string $uuid)
    {
        $this->assertSame($expected, uuid_is_valid($uuid));
    }

    public function provideCompareTest(): array
    {
        return [
            [-1, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4c'],
            [0, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
            [1, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4d', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4c'],
        ];
    }

    /** @dataProvider provideCompareTest */
    public function testCompare(int $expected, string $uuid1, string $uuid2)
    {
        $result = @uuid_compare($uuid1, $uuid2);

        // Normalize the result because it depends of pecl version.
        if (0 !== $result) {
            $result /= abs($result);
        }

        $this->assertSame($expected, $result);
    }

    public function testCompareWithInvalidUuidLeft()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_compare(): Argument #1 ($uuid1) UUID expected');
        }

        $this->assertFalse(@uuid_compare('foobar', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'));
    }

    public function testCompareWithInvalidUuidRight()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_compare(): Argument #2 ($uuid2) UUID expected');
        }

        $this->assertFalse(@uuid_compare('fa83b381-328c-46b8-8c90-4e9ba47dfa4b', 'foobar'));
    }

    public function provideIsNullTest(): array
    {
        return [
            [true, '00000000-0000-0000-0000-000000000000'],
            [false, '00000000-0000-0000-0000-000000000001'],
            [false, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
        ];
    }

    /** @dataProvider provideIsNullTest */
    public function testIsNull(bool $expected, string $uuid)
    {
        $this->assertSame($expected, @uuid_is_null($uuid));
    }

    public function testIsNullWithInvalidUuid()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_is_null(): Argument #1 ($uuid) UUID expected');
        }

        $this->assertFalse(@uuid_is_null('foobar'));
    }

    public function provideTypeTest(): array
    {
        return [
            [Uuid::UUID_TYPE_NULL, '00000000-0000-0000-0000-000000000000'],
            [Uuid::UUID_TYPE_RANDOM, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
            [Uuid::UUID_TYPE_TIME, 'dbc6260f-e9cc-11e9-8dac-9cb6d0897f07'],
            [Uuid::UUID_TYPE_TIME, '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'],
        ];
    }

    /** @dataProvider provideTypeTest */
    public function testType(int $expected, string $uuid)
    {
        $this->assertSame($expected, @uuid_type($uuid));
    }

    public function testTypeWithInvalidUuid()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_type(): Argument #1 ($uuid) UUID expected');
        }

        $this->assertFalse(@uuid_type('foobar'));
    }

    public function provideVariantTest(): array
    {
        return [
            [Uuid::UUID_TYPE_NULL, '00000000-0000-0000-0000-000000000000'],
            [Uuid::UUID_VARIANT_DCE, 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
            [Uuid::UUID_VARIANT_DCE, '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'],
        ];
    }

    /** @dataProvider provideVariantTest */
    public function testVariant(int $expected, string $uuid)
    {
        $this->assertSame($expected, @uuid_variant($uuid));
    }

    public function testVariantWithInvalidUuid()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_variant(): Argument #1 ($uuid) UUID expected');
        }

        $this->assertFalse(@uuid_variant('foobar'));
    }

    public function provideTimeTest(): array
    {
        return [
            [1572444805, '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'],
            [1572445677, '77ffc38a-fb21-11e9-b46a-3c7de2fa99cb'],
        ];
    }

    /** @dataProvider provideTimeTest */
    public function testTime(int $expected, string $uuid)
    {
        $this->assertSame($expected, @uuid_time($uuid));
    }

    public function provideInvalidTimeTest(): array
    {
        return [
            ['foobar'],
            ['00000000-0000-0000-0000-000000000000'],
            ['fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
        ];
    }

    /**
     * @dataProvider provideInvalidTimeTest
     */
    public function testTimeWithInvalidUuid(string $uuid)
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_time(): Argument #1 ($uuid) UUID DCE TIME expected');
        }

        $this->assertFalse(@uuid_time($uuid));
    }

    public function provideMacTest(): array
    {
        return [
            ['b52d3e41ad26', '6fec1e70-fb1f-11e9-81dc-b52d3e41ad26'],
            ['3c7de2fa99cb', '77ffc38a-fb21-11e9-b46a-3c7de2fa99CB'],
        ];
    }

    /** @dataProvider provideMacTest */
    public function testMac(string $expected, string $uuid)
    {
        $this->assertSame($expected, @uuid_mac($uuid));
    }

    public function provideInvalidMacTest(): array
    {
        return [
            ['foobar'],
            ['00000000-0000-0000-0000-000000000000'],
            ['fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
        ];
    }

    /**
     * @dataProvider provideInvalidMacTest
     */
    public function testMacWithInvalidUuid(string $uuid)
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_mac(): Argument #1 ($uuid) UUID DCE TIME expected');
        }

        $this->assertFalse(@uuid_mac($uuid));
    }

    public function provideParseTest(): array
    {
        return [
            ['00000000000000000000000000000000', '00000000-0000-0000-0000-000000000000'],
            ['fa83b381328c46b88c904e9ba47dfa4b', 'fa83b381-328c-46b8-8c90-4e9ba47dfa4b'],
            ['77ffc38afb2111e9b46a3c7de2fa99cb', '77ffc38a-fb21-11e9-b46a-3c7de2fa99cb'],
        ];
    }

    /** @dataProvider provideParseTest */
    public function testParse(string $expected, string $uuid)
    {
        $out = bin2hex(@uuid_parse($uuid));

        $this->assertSame($expected, $out);
    }

    public function testParseWithInvalidUuid()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_parse(): Argument #1 ($uuid) UUID expected');
        }

        $this->assertFalse(@uuid_parse('foobar'));
    }

    public function provideUnparseTest(): array
    {
        return [
            ['00000000-0000-0000-0000-000000000000', pack('H*', '00000000000000000000000000000000')],
            ['fa83b381-328c-46b8-8c90-4e9ba47dfa4b', pack('H*', 'fa83b381328c46b88c904e9ba47dfa4b')],
            ['77ffc38a-fb21-11e9-b46a-3c7de2fa99cb', pack('H*', '77ffc38afb2111e9b46a3c7de2fa99cb')],
        ];
    }

    /** @dataProvider provideUnparseTest */
    public function testUnparse(string $expected, string $uuid)
    {
        $this->assertSame($expected, @uuid_unparse($uuid));
    }

    public function provideInvalidUnparseTest(): array
    {
        return [
            ['foobar'],
            [pack('h*', '46b8')],
        ];
    }

    /**
     * @dataProvider provideInvalidUnparseTest
     */
    public function testUnparseWithInvalidUuid(string $uuid)
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('uuid_unparse(): Argument #1 ($uuid) UUID expected');
        }

        $this->assertFalse(@uuid_unparse($uuid));
    }
}
