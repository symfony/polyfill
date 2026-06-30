<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\DeepClone;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Util\TestListenerTrait;

if (\PHP_VERSION_ID >= 80100) {
    require __DIR__.'/fixtures.php';
}
if (\PHP_VERSION_ID >= 80500) {
    require __DIR__.'/fixtures85.php';
}

/**
 * @requires PHP 8.1
 */
class DeepCloneTest extends TestCase
{
    public function testToArrayIntIsStatic()
    {
        $this->assertSame(['value' => 42], deepclone_to_array(42));
    }

    public function testToArrayStringIsStatic()
    {
        $this->assertSame(['value' => 'hello'], deepclone_to_array('hello'));
    }

    public function testToArrayTrueIsStatic()
    {
        $this->assertSame(['value' => true], deepclone_to_array(true));
    }

    public function testToArrayFalseIsStatic()
    {
        $this->assertSame(['value' => false], deepclone_to_array(false));
    }

    public function testToArrayNullIsStatic()
    {
        $this->assertSame(['value' => null], deepclone_to_array(null));
    }

    public function testToArrayFloatIsStatic()
    {
        $this->assertSame(['value' => 3.14], deepclone_to_array(3.14));
    }

    public function testToArrayEmptyArrayIsStatic()
    {
        $this->assertSame(['value' => []], deepclone_to_array([]));
    }

    public function testToArraySimpleStdClassWireFormat()
    {
        $o = new \stdClass();
        $o->foo = 'bar';
        $o->num = 42;
        $d = deepclone_to_array($o);

        $this->assertSame('stdClass', $d['classes']);
        $this->assertSame(1, $d['objectMeta']);
        $this->assertSame(0, $d['prepared']);
        $this->assertSame([0 => 'bar'], $d['properties']['stdClass']['foo']);
        $this->assertSame([0 => 42], $d['properties']['stdClass']['num']);
    }

    public function testToArrayNestedObjectsWireFormat()
    {
        $o = new \stdClass();
        $o->child = new \stdClass();
        $o->child->val = 'inner';
        $d = deepclone_to_array($o);

        $this->assertSame(2, $d['objectMeta']);
        $this->assertSame([1 => 'inner'], $d['properties']['stdClass']['val']);
        $this->assertSame([0 => true], $d['resolve']['stdClass']['child']);
    }

    public function testToArraySharedObjectReferenceWireFormat()
    {
        $child = new \stdClass();
        $child->x = 1;
        $o = new \stdClass();
        $o->a = $child;
        $o->b = $child;
        $d = deepclone_to_array($o);

        $this->assertSame($d['properties']['stdClass']['a'][0], $d['properties']['stdClass']['b'][0]);
    }

    public function testToArrayCircularReferenceWireFormat()
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $a->ref = $b;
        $b->ref = $a;
        $d = deepclone_to_array($a);

        $this->assertSame(2, $d['objectMeta']);
        $this->assertTrue($d['resolve']['stdClass']['ref'][0]);
        $this->assertTrue($d['resolve']['stdClass']['ref'][1]);
    }

    public function testToArrayScalarHardRefsWireFormat()
    {
        $v = [1];
        $v[] = &$v[0];
        $d = deepclone_to_array($v);

        $this->assertSame([-1, -1], $d['prepared']);
        $this->assertSame([false, false], $d['mask']);
        $this->assertArrayHasKey(1, $d['refs']);
    }

    public function testToArrayRecursiveHardRefWireFormat()
    {
        $v = [];
        $v[0] = &$v;
        $d = deepclone_to_array($v);

        $this->assertSame([-1], $d['prepared']);
        $this->assertSame([false], $d['mask']);
        $this->assertArrayHasKey(1, $d['refs']);
        $this->assertArrayHasKey(1, $d['refMasks']);
    }

    public function testToArrayUnsharedReferenceBecomesStatic()
    {
        $x = [123];
        $d = deepclone_to_array([&$x]);

        $this->assertArrayHasKey('value', $d);
        $this->assertSame([[123]], $d['value']);
    }

    public function testToArrayNamedClosureGlobalFunctionWireFormat()
    {
        $d = deepclone_to_array(\Closure::fromCallable('strlen'), allow_named_closures: true);

        $this->assertSame([null, 'strlen'], $d['prepared']);
        $this->assertSame(0, $d['mask']);
    }

    public function testToArrayLargeStringCowPreservation()
    {
        $big = str_repeat('x', 10000);
        $o = new \stdClass();
        $o->data = $big;
        $d = deepclone_to_array($o);

        $this->assertSame($big, $d['properties']['stdClass']['data'][0]);
    }

    public function testFromArrayScalarInt()
    {
        $this->assertSame(42, deepclone_from_array(['value' => 42]));
    }

    public function testFromArrayScalarString()
    {
        $this->assertSame('hello', deepclone_from_array(['value' => 'hello']));
    }

    public function testFromArrayScalarNull()
    {
        $this->assertNull(deepclone_from_array(['value' => null]));
    }

    public function testFromArrayScalarBool()
    {
        $this->assertTrue(deepclone_from_array(['value' => true]));
    }

    public function testFromArrayScalarArray()
    {
        $this->assertSame([1, 2], deepclone_from_array(['value' => [1, 2]]));
    }

    public function testRoundTripSimpleStdClass()
    {
        $o = new \stdClass();
        $o->foo = 'bar';
        $o->num = 42;
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertInstanceOf(\stdClass::class, $clone);
        $this->assertSame('bar', $clone->foo);
        $this->assertSame(42, $clone->num);
        $this->assertNotSame($o, $clone);
    }

    public function testRoundTripNestedObjects()
    {
        $o = new \stdClass();
        $o->child = new \stdClass();
        $o->child->val = 'inner';
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertInstanceOf(\stdClass::class, $clone->child);
        $this->assertSame('inner', $clone->child->val);
        $this->assertNotSame($o->child, $clone->child);
    }

    public function testRoundTripSharedObjectReference()
    {
        $child = new \stdClass();
        $child->x = 1;
        $o = new \stdClass();
        $o->a = $child;
        $o->b = $child;
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame($clone->a, $clone->b);
        $this->assertNotSame($child, $clone->a);
        $this->assertSame(1, $clone->a->x);
    }

    public function testRoundTripCircularReference()
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $a->ref = $b;
        $b->ref = $a;
        $clone = deepclone_from_array(deepclone_to_array($a));

        $this->assertSame($clone, $clone->ref->ref);
    }

    public function testRoundTripScalarHardReference()
    {
        $v = [1];
        $v[] = &$v[0];
        $clone = deepclone_from_array(deepclone_to_array($v));

        $this->assertSame(1, $clone[0]);
        $clone[0] = 999;
        $this->assertSame(999, $clone[1]);
    }

    public function testRoundTripRecursiveHardReference()
    {
        $v = [];
        $v[0] = &$v;
        $clone = deepclone_from_array(deepclone_to_array($v));

        $this->assertSame($clone, $clone[0]);
    }

    public function testRoundTripNamedClosureGlobalFunction()
    {
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable('strlen'), allow_named_closures: true), allow_named_closures: true);

        $this->assertSame(5, $clone('hello'));
    }

    public function testRoundTripWideFixture()
    {
        $r = new \stdClass();
        $r->items = [];
        for ($i = 0; $i < 50; ++$i) {
            $it = new \stdClass();
            $it->id = $i;
            $it->label = "item-$i";
            $r->items[] = $it;
        }
        $r->meta = new \stdClass();
        $r->meta->count = 50;
        $clone = deepclone_from_array(deepclone_to_array($r));

        $this->assertCount(50, $clone->items);
        $this->assertSame(0, $clone->items[0]->id);
        $this->assertSame('item-49', $clone->items[49]->label);
        $this->assertSame(50, $clone->meta->count);
        $this->assertNotSame($r, $clone);
    }

    public function testRoundTripCowStringPreservation()
    {
        $big = str_repeat('x', 10000);
        $o = new \stdClass();
        $o->data = $big;
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame($big, $clone->data);
    }

    public function testRepeatedClonesAreIndependent()
    {
        $v = [1];
        $v[] = &$v[0];
        $data = deepclone_to_array($v);

        $c1 = deepclone_from_array($data);
        $c2 = deepclone_from_array($data);
        $c1[0] = 999;

        $this->assertSame(999, $c1[1]);
        $this->assertSame(1, $c2[0]);
        $this->assertSame(1, $c2[1]);
    }

    public function testFromArrayRejectsMissingClasses()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) is missing required "classes" key');
        deepclone_from_array(['objectMeta' => 0, 'prepared' => 0]);
    }

    public function testFromArrayRejectsMissingObjectMeta()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) is missing required "objectMeta" key');
        deepclone_from_array(['classes' => 'stdClass', 'prepared' => 0]);
    }

    public function testFromArrayRejectsMissingPrepared()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) is missing required "prepared" key');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 0]);
    }

    public function testFromArrayRejectsClassesWrongType()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "classes" must be of type string|array, null given');
        deepclone_from_array(['classes' => null, 'objectMeta' => 0, 'prepared' => 0]);
    }

    public function testFromArrayRejectsClassesIntEntry()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "classes" entries must be of type string, int given');
        deepclone_from_array(['classes' => [42], 'objectMeta' => 0, 'prepared' => 0]);
    }

    public function testFromArrayRejectsNonObjectUnserializeResult()
    {
        // A class-name string whose second byte is ':' is replayed through
        // unserialize(); a scalar/array serialize form must be rejected rather
        // than stored and later treated as an object.
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) object 0 did not unserialize to an object, int given');
        deepclone_from_array(['classes' => 'i:1234;', 'objectMeta' => 1, 'prepared' => 0]);
    }

    public function testFromArrayRejectsObjectMetaWrongType()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" must be of type int|array, string given');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 'foo', 'prepared' => 0]);
    }

    public function testFromArrayRejectsNegativeObjectMetaCount()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" count must be non-negative, -1 given');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => -1, 'prepared' => 0]);
    }

    public function testFromArrayRejectsOversizedObjectMetaCount()
    {
        // Matches the ext cap (1 << 20). Guards against a DoS where a tiny
        // payload with a huge IS_LONG objectMeta count triggers a massive
        // allocation.
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" count out of range: 1048577');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 0x100001, 'prepared' => 0]);
    }

    public function testFromArrayRejectsObjectMetaReferencingEmptyClasses()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" references class index 0 but "classes" is empty');
        deepclone_from_array(['classes' => '', 'objectMeta' => 1, 'prepared' => 0]);
    }

    public function testFromArrayRejectsOutOfRangeClassIndex()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry 0 has out-of-range class index 5');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => [[5, 0]], 'prepared' => 0]);
    }

    public function testFromArrayRejectsObjectMetaWrongShape()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry 0 must be [int, int]');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => [['x', 'y']], 'prepared' => 0]);
    }

    public function testFromArrayRejectsObjectMetaWrongScalar()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "objectMeta" entry 0 must be of type int|array, string given');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => ['foo'], 'prepared' => 0]);
    }

    public function testFromArrayRejectsPropertiesWrongType()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "properties" must be of type array, string given');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 0, 'prepared' => 0, 'properties' => 'foo']);
    }

    public function testFromArrayRejectsStatesWrongType()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('deepclone_from_array(): Argument #1 ($data) "states" must be of type array, string given');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 0, 'prepared' => 0, 'states' => 'foo']);
    }

    public function testFromArrayRejectsStatesEntryWrongType()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"states" entry must be of type int|array, string given');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 1, 'prepared' => 0, 'states' => [1 => 'foo']]);
    }

    public function testFromArrayRejectsStatesUnknownObjectId()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"states" entry references unknown object id 99');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 1, 'prepared' => 0, 'states' => [1 => 99]]);
    }

    public function testFromArrayRejectsPreparedObjectIdOutOfRange()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"prepared" references unknown object id 99');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 1, 'prepared' => 99]);
    }

    public function testFromArrayRejectsWakeupAdvertisedButNoStates()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"objectMeta" entry 0 flags object for state replay but no matching "states" entry was found');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => [[0, 1]], 'prepared' => 0]);
    }

    public function testFromArrayRejectsUnserializeAdvertisedButNoStates()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"objectMeta" entry 0 flags object for state replay but no matching "states" entry was found');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => [[0, -1]], 'prepared' => 0]);
    }

    public function testFromArrayRejectsStatesWakeupWithoutPositiveMeta()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"states" has a __wakeup entry for object id 0 but "objectMeta" does not flag it for __wakeup');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => [[0, -1]], 'prepared' => 0, 'states' => [1 => 0]]);
    }

    public function testFromArrayRejectsStatesUnserializeWithoutNegativeMeta()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"states" has an __unserialize entry for object id 0 but "objectMeta" does not flag it for __unserialize');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => [[0, 1]], 'prepared' => 0, 'states' => [1 => [0, []]]]);
    }

    public function testFromArrayRejectsStatesWakeupEntryButMetaZero()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"states" has a __wakeup entry for object id 0 but "objectMeta" does not flag it for __wakeup');
        deepclone_from_array(['classes' => 'stdClass', 'objectMeta' => 1, 'prepared' => 0, 'states' => [1 => 0]]);
    }

    public function testFromArrayRejectsPreparedRefIdUnknown()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"prepared" references unknown ref id 99');
        deepclone_from_array(['classes' => '', 'objectMeta' => 0, 'prepared' => -99]);
    }

    public function testFromArrayRejectsIntMinRefIdsWithoutWarning()
    {
        // Negating PHP_INT_MIN overflows to a float and emits a runtime warning
        // before the value can be used as a ref-id array key. Every resolution
        // path must reject it cleanly. A strict error handler turns any emitted
        // diagnostic into a failure so the warning cannot regress unnoticed.
        $cases = [
            [
                ['classes' => 'stdClass', 'objectMeta' => 0, 'prepared' => \PHP_INT_MIN],
                '"prepared" references unknown ref id out of range',
            ],
            [
                ['classes' => 'stdClass', 'objectMeta' => 0, 'prepared' => [0 => \PHP_INT_MIN], 'mask' => [0 => true]],
                'malformed payload, ref id out of range',
            ],
            [
                ['classes' => 'stdClass', 'objectMeta' => 0, 'prepared' => [\PHP_INT_MIN, 'strlen'], 'mask' => 0],
                'malformed payload, named-closure references unknown id -9223372036854775808',
            ],
        ];

        set_error_handler(static function ($type, $message) {
            throw new \RuntimeException('unexpected diagnostic: '.$message);
        });
        try {
            foreach ($cases as [$payload, $expected]) {
                try {
                    // allow_named_closures lets the named-closure case reach the
                    // ref-id check; the other cases carry no named-closure marker
                    // so the flag does not affect them.
                    deepclone_from_array($payload, null, true);
                    $this->fail('Expected ValueError was not thrown');
                } catch (\ValueError $e) {
                    $this->assertStringContainsString($expected, $e->getMessage());
                }
            }
        } finally {
            restore_error_handler();
        }
    }

    public function testClosureGlobalFunctionWireFormat()
    {
        $d = deepclone_to_array(\Closure::fromCallable('strlen'), allow_named_closures: true);

        $this->assertSame(0, $d['mask']);
        $this->assertNull($d['prepared'][0]);
        $this->assertSame('strlen', $d['prepared'][1]);
    }

    public function testClosureGlobalFunctionRoundTrip()
    {
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable('strlen'), allow_named_closures: true), allow_named_closures: true);
        $this->assertSame(5, $clone('hello'));
    }

    public function testClosureStaticMethodWireFormat()
    {
        $d = deepclone_to_array(\Closure::fromCallable([ClosureFixture::class, 'staticMethod']), allow_named_closures: true);

        $this->assertSame(ClosureFixture::class, $d['prepared'][0]);
        $this->assertSame('staticMethod', $d['prepared'][1]);
    }

    public function testClosureStaticMethodRoundTrip()
    {
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable([ClosureFixture::class, 'staticMethod']), allow_named_closures: true), allow_named_closures: true);
        $this->assertSame('static', $clone());
    }

    public function testClosureInstanceMethodWireFormat()
    {
        $obj = new ClosureFixture();
        $d = deepclone_to_array(\Closure::fromCallable([$obj, 'instanceMethod']), allow_named_closures: true);

        $this->assertSame(ClosureFixture::class, $d['classes']);
    }

    public function testClosureInstanceMethodRoundTrip()
    {
        $obj = new ClosureFixture();
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable([$obj, 'instanceMethod']), allow_named_closures: true), allow_named_closures: true);
        $this->assertSame('instance', $clone());
    }

    public function testClosurePrivateMethodWireFormatAndRoundTrip()
    {
        $obj = new ClosureFixture();
        $fn = $obj->getPrivateClosure();
        $d = deepclone_to_array($fn, allow_named_closures: true);

        $this->assertSame(0, $d['mask']);

        $clone = deepclone_from_array($d, allow_named_closures: true);
        $this->assertSame('private', $clone());
    }

    public function testToArrayNamedClosureRequiresOptIn()
    {
        $this->expectException(\ValueError::class);
        // Substring common to the polyfill and extension messages (the polyfill
        // quotes the option name and appends an extension hint).
        $this->expectExceptionMessage('serializing a closure over the named callable "strlen" requires enabling the');
        deepclone_to_array(\Closure::fromCallable('strlen'));
    }

    public function testFromArrayNamedClosureRequiresOptIn()
    {
        $d = deepclone_to_array(\Closure::fromCallable('strlen'), allow_named_closures: true);
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('resolving a closure over a named callable requires enabling the "allow_named_closures" option');
        deepclone_from_array($d);
    }

    public function testFromArrayNamedClosureNestedRejectedBeforeInstantiation()
    {
        $h = new \stdClass();
        $h->cb = \Closure::fromCallable('strlen');
        $d = deepclone_to_array($h, allow_named_closures: true);

        try {
            deepclone_from_array($d);
            $this->fail('Expected ValueError was not thrown');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('resolving a closure over a named callable requires enabling the "allow_named_closures" option', $e->getMessage());
        }

        $clone = deepclone_from_array($d, allow_named_closures: true);
        $this->assertSame(4, ($clone->cb)('abcd'));
    }

    public function testDateTimeRoundTrip()
    {
        $dt = \DateTime::createFromFormat('U', '0');
        $d = deepclone_to_array($dt);

        // __unserialize (negative wakeup) on 8.2+, __wakeup (positive) on 8.1
        $this->assertNotSame(0, $d['objectMeta'][0][1]);

        $clone = deepclone_from_array($d);
        $this->assertInstanceOf(\DateTime::class, $clone);
        $this->assertSame('0', $clone->format('U'));
        $this->assertNotSame($dt, $clone);
    }

    public function testDateTimeImmutableRoundTrip()
    {
        $dti = \DateTimeImmutable::createFromFormat('U', '0');
        $clone = deepclone_from_array(deepclone_to_array($dti));

        $this->assertInstanceOf(\DateTimeImmutable::class, $clone);
        $this->assertSame('0', $clone->format('U'));
    }

    public function testDateTimeZoneRoundTrip()
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $clone = deepclone_from_array(deepclone_to_array($tz));

        $this->assertInstanceOf(\DateTimeZone::class, $clone);
        $this->assertSame('Europe/Paris', $clone->getName());
    }

    public function testDateIntervalRoundTrip()
    {
        $start = new \DateTimeImmutable('2009-10-11');
        $end = new \DateTimeImmutable('2009-10-18');
        $interval = $start->diff($end);
        $clone = deepclone_from_array(deepclone_to_array($interval));

        $this->assertInstanceOf(\DateInterval::class, $clone);
        $this->assertSame(7, $clone->d);
    }

    public function testEnumIsStaticValue()
    {
        $d = deepclone_to_array(DeepCloneColor::Red);

        $this->assertArrayHasKey('value', $d);
        $this->assertSame(DeepCloneColor::Red, $d['value']);
    }

    public function testBackedEnumIsStaticValue()
    {
        $d = deepclone_to_array(DeepCloneSuit::Hearts);

        $this->assertSame(DeepCloneSuit::Hearts, $d['value']);
    }

    public function testEnumInArrayIsStatic()
    {
        $d = deepclone_to_array([DeepCloneColor::Red, DeepCloneColor::Blue]);

        $this->assertSame([DeepCloneColor::Red, DeepCloneColor::Blue], $d['value']);
    }

    public function testEnumAsObjectProperty()
    {
        $o = new \stdClass();
        $o->color = DeepCloneColor::Red;
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame(DeepCloneColor::Red, $clone->color);
    }

    public function testEnumHardReference()
    {
        $x = DeepCloneColor::Red;
        $v = [&$x, &$x];
        $d = deepclone_to_array($v);

        $this->assertArrayHasKey('refs', $d);
        $this->assertArrayHasKey('refMasks', $d);

        $clone = deepclone_from_array($d);
        $this->assertSame(DeepCloneColor::Red, $clone[0]);

        $clone[0] = DeepCloneColor::Blue;
        $this->assertSame(DeepCloneColor::Blue, $clone[1]);
    }

    public function testErrorPropertiesStayUnderErrorScope()
    {
        $e = new \Error('test');
        (new \ReflectionProperty(\Error::class, 'trace'))->setValue($e, ['file' => 't.php']);
        (new \ReflectionProperty(\Error::class, 'line'))->setValue($e, 234);

        $d = deepclone_to_array($e);

        $this->assertArrayHasKey('message', $d['properties']['Error']);
        $this->assertArrayHasKey('line', $d['properties']['Error']);
        $this->assertArrayHasKey('trace', $d['properties']['Error']);
        $this->assertArrayNotHasKey('TypeError', $d['properties']);
    }

    public function testErrorSubclassKeepsErrorScope()
    {
        $e = new DeepCloneFinalError('subclass');
        (new \ReflectionProperty(\Error::class, 'trace'))->setValue($e, []);
        (new \ReflectionProperty(\Error::class, 'line'))->setValue($e, 123);

        $d = deepclone_to_array($e);

        $this->assertArrayHasKey('trace', $d['properties']['Error']);
        $this->assertArrayHasKey('line', $d['properties']['Error']);
    }

    public function testErrorRoundTrip()
    {
        $e = new \Error('round-trip');
        (new \ReflectionProperty(\Error::class, 'trace'))->setValue($e, []);

        $clone = deepclone_from_array(deepclone_to_array($e));

        $this->assertInstanceOf(\Error::class, $clone);
        $this->assertSame('round-trip', $clone->getMessage());
    }

    public function testSerializeMethodWireFormatAndRoundTrip()
    {
        $o = new DeepCloneSerializeFixture('test', 42);
        $d = deepclone_to_array($o);

        // Negative wakeup = __unserialize will be called during reconstruction.
        $this->assertLessThan(0, $d['objectMeta'][0][1]);

        $clone = deepclone_from_array($d);
        $this->assertInstanceOf(DeepCloneSerializeFixture::class, $clone);
        $this->assertSame('test', $clone->name);
        $this->assertSame(42, $clone->val);
    }

    public function testWakeupIsCalledWithPositiveWakeup()
    {
        $o = new DeepCloneWakeupFixture();
        $o->status = 'custom';
        $d = deepclone_to_array($o);

        $this->assertGreaterThan(0, $d['objectMeta'][0][1]);

        $clone = deepclone_from_array($d);
        $this->assertSame('awake', $clone->status);
    }

    public function testSleepFiltersProperties()
    {
        $o = new DeepCloneSleepFixture();
        $o->keep = 'yes';
        $o->skip = 'no';
        $d = deepclone_to_array($o);

        $this->assertArrayHasKey('keep', $d['properties']['stdClass']);
        $this->assertArrayNotHasKey('skip', $d['properties']['stdClass']);
    }

    public function testSerializeWithoutUnserializeScopedProperties()
    {
        $o = new DeepCloneChildNoUnser();
        $d = deepclone_to_array($o);

        // foo is private on ParentNoUnser → scoped to ParentNoUnser
        $this->assertArrayHasKey('foo', $d['properties']['Symfony\\Polyfill\\Tests\\DeepClone\\DeepCloneParentNoUnser']);
        // baz is public → scoped to stdClass
        $this->assertArrayHasKey('baz', $d['properties']['stdClass']);
        // bar is private on ChildNoUnser → scoped to ChildNoUnser
        $this->assertArrayHasKey('bar', $d['properties']['Symfony\\Polyfill\\Tests\\DeepClone\\DeepCloneChildNoUnser']);
        // No __unserialize → no states key
        $this->assertArrayNotHasKey('states', $d);
    }

    public function testUnserializeWithoutSerializeUsesRawArrayState()
    {
        $o = new DeepCloneUnserOnly();
        $o->foo = 'hello';
        $d = deepclone_to_array($o);

        // Should have states (negative wakeup = __unserialize)
        $this->assertArrayHasKey('states', $d);

        // State props are the raw (array) cast, NOT scoped
        $state = reset($d['states']);
        $this->assertArrayHasKey('foo', $state[1]);
        $this->assertSame('hello', $state[1]['foo']);

        // Properties should be empty (all data in state)
        $this->assertTrue(empty($d['properties']));
    }

    public function testUnserializeWithoutSerializeRoundTrip()
    {
        $o = new DeepCloneUnserOnly();
        $o->foo = 'hello';
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame('hello', $clone->foo);
    }

    public function testSleepWithMangledPrivateKeys()
    {
        $o = new DeepCloneSleepPrivate();
        $o->setAll('night', 'afternoon', 'morning');
        $d = deepclone_to_array($o);

        $this->assertArrayHasKey('good', $d['properties']['stdClass']);
        $this->assertArrayHasKey('foo', $d['properties']['Symfony\\Polyfill\\Tests\\DeepClone\\DeepCloneSleepPrivate']);
        $this->assertArrayHasKey('bar', $d['properties']['Symfony\\Polyfill\\Tests\\DeepClone\\DeepCloneSleepPrivate']);
    }

    public function testSleepInheritedPrivateExclusion()
    {
        $o = new DeepCloneChildSleep();
        $o->pub = 'visible';
        // `__sleep` returning the unmangled name "secret" triggers the same
        // E_NOTICE that serialize() itself would ("returned as member variable
        // … but does not exist"), because unmangled names are not allowed to
        // reach a parent's private property. The PHPT just ignores that; we
        // have to silence it so phpunit-bridge doesn't turn it into an error.
        $d = @deepclone_to_array($o);

        $this->assertArrayHasKey('pub', $d['properties']['stdClass']);
        // 'secret' is a private property of ParentSleep. Unmangled "secret"
        // in __sleep must NOT match it (inherited-private exclusion).
        $this->assertArrayNotHasKey('Symfony\\Polyfill\\Tests\\DeepClone\\DeepCloneParentSleep', $d['properties'] ?? []);
    }

    public function testTypedObjectParentClass()
    {
        $o = new DeepCloneParentClass();
        $o->pub = 'changed';
        $o->setProt(42);
        $o->setPriv('secret');
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertInstanceOf(DeepCloneParentClass::class, $clone);
        $this->assertSame('changed', $clone->pub);
        $this->assertEquals($o, $clone);
        $this->assertNotSame($o, $clone);
    }

    public function testTypedObjectChildClassInheritedProperties()
    {
        $o = new DeepCloneChildClass();
        $o->pub = 'child_pub';
        $o->setProt(99);
        $o->setPriv('parent_secret');
        $o->setChildPriv('child_secret');
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertInstanceOf(DeepCloneChildClass::class, $clone);
        $this->assertSame('child_pub', $clone->pub);
        $this->assertEquals($o, $clone);
    }

    public function testTypedObjectDefaultValuesAreSkipped()
    {
        $o = new DeepCloneParentClass();
        $d = deepclone_to_array($o);

        // All properties at defaults → properties empty or minimal.
        $this->assertTrue(empty($d['properties']) || [] === $d['properties']);
    }

    public function testReadonlyProperties()
    {
        $o = new DeepCloneReadonlyFixture('test', 42);
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame('test', $clone->name);
        $this->assertSame(42, $clone->value);
    }

    public function testDocBehaviorsMissingClassThrowsClassNotFound()
    {
        try {
            deepclone_from_array([
                'classes' => 'NonExistentClassXyz',
                'objectMeta' => 1,
                'prepared' => 0,
                'properties' => ['NonExistentClassXyz' => ['foo' => ['bar']]],
            ]);
            $this->fail('Expected DeepClone\\ClassNotFoundException');
        } catch (\InvalidArgumentException $e) {
            $this->assertInstanceOf(\DeepClone\ClassNotFoundException::class, $e);
            $this->assertSame('Class "NonExistentClassXyz" not found.', $e->getMessage());
        }
    }

    public function testDocBehaviorsSplObjectStorageSharedKey()
    {
        $key = new \stdClass();
        $key->id = 'shared-key';
        $storage = new \SplObjectStorage();
        $storage[$key] = 'value';

        $graph = new \stdClass();
        $graph->storage = $storage;
        $graph->keyOutside = $key;

        $clone = deepclone_from_array(deepclone_to_array($graph));
        $clonedKey = null;
        foreach ($clone->storage as $k) {
            $clonedKey = $k;
        }
        $this->assertSame($clone->keyOutside, $clonedKey);
    }

    public function testDocBehaviorsSplObjectStorageSharedValue()
    {
        $value = new \stdClass();
        $value->id = 'shared-value';
        $key = new \stdClass();
        $storage = new \SplObjectStorage();
        $storage[$key] = $value;

        $graph = new \stdClass();
        $graph->storage = $storage;
        $graph->valueOutside = $value;

        $clone = deepclone_from_array(deepclone_to_array($graph));
        $clonedK = null;
        foreach ($clone->storage as $k) {
            $clonedK = $k;
        }
        $this->assertSame($clone->valueOutside, $clone->storage[$clonedK]);
    }

    public function testDocBehaviorsArrayObjectSharedItem()
    {
        $shared = new \stdClass();
        $shared->id = 'shared';
        $ao = new \ArrayObject(['inside' => $shared]);

        $graph = new \stdClass();
        $graph->ao = $ao;
        $graph->outside = $shared;

        $clone = deepclone_from_array(deepclone_to_array($graph));
        $this->assertSame($clone->outside, $clone->ao['inside']);
    }

    public function testDocBehaviorsArrayIteratorSharedItem()
    {
        $shared = new \stdClass();
        $shared->id = 'shared';
        $ai = new \ArrayIterator(['inside' => $shared]);

        $graph = new \stdClass();
        $graph->ai = $ai;
        $graph->outside = $shared;

        $clone = deepclone_from_array(deepclone_to_array($graph));
        $this->assertSame($clone->outside, $clone->ai['inside']);
    }

    public function testDocBehaviorsReflectionClassIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array(new \ReflectionClass(\stdClass::class));
    }

    public function testDocBehaviorsReflectionMethodIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array(new \ReflectionMethod(\ArrayObject::class, '__construct'));
    }

    public function testDocBehaviorsReflectionPropertyIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array(new \ReflectionProperty(\Error::class, 'message'));
    }

    public function testDocBehaviorsIteratorIteratorIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array(new \IteratorIterator(new \ArrayIterator([1, 2])));
    }

    public function testDocBehaviorsRecursiveIteratorIteratorIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator([[1]])));
    }

    public function testDocBehaviorsAnonymousClassIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array(eval('return new class { public int $x = 1; };'));
    }

    public function testDocBehaviorsSplFileInfoIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('SplFileInfo');
        deepclone_to_array(new \SplFileInfo('/etc/hostname'));
    }

    public function testDocBehaviorsSensitiveParameterValueIsRejected()
    {
        if (\PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('SensitiveParameterValue requires PHP 8.2+.');
        }
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('SensitiveParameterValue');
        deepclone_to_array(new \SensitiveParameterValue('super-secret-api-key'));
    }

    public function testDocBehaviorsArrayObjectRoundTrips()
    {
        $ao = new \ArrayObject([1, 2, 3]);
        $clone = deepclone_from_array(deepclone_to_array($ao));
        $this->assertInstanceOf(\ArrayObject::class, $clone);
    }

    public function testDocBehaviorsSplFixedArrayRoundTrips()
    {
        if (\PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('SplFixedArray::__serialize requires PHP 8.2+.');
        }
        $fa = \SplFixedArray::fromArray([10, 20, 30]);
        $clone = deepclone_from_array(deepclone_to_array($fa));
        $this->assertInstanceOf(\SplFixedArray::class, $clone);
    }

    public function testDocBehaviorsSplObjectStorageRoundTrips()
    {
        $s = new \SplObjectStorage();
        $s[new \stdClass()] = 'v';
        $clone = deepclone_from_array(deepclone_to_array($s));
        $this->assertInstanceOf(\SplObjectStorage::class, $clone);
    }

    public function testDocBehaviorsTopLevelResourceIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('stream resource');
        deepclone_to_array(\STDIN);
    }

    public function testExceptionClassesExtendInvalidArgumentException()
    {
        $this->assertTrue(is_subclass_of(\DeepClone\NotInstantiableException::class, \InvalidArgumentException::class));
        $this->assertTrue(is_subclass_of(\DeepClone\ClassNotFoundException::class, \InvalidArgumentException::class));
    }

    public function testToArrayAllowedClassesRejectsDisallowed()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"stdClass" is not allowed');
        deepclone_to_array(new \stdClass(), []);
    }

    public function testToArrayAllowedClassesPermitsListed()
    {
        $d = deepclone_to_array(new \stdClass(), ['stdClass']);
        $this->assertSame('stdClass', $d['classes']);
    }

    public function testToArrayAllowedClassesCaseInsensitive()
    {
        $d = deepclone_to_array(new \stdClass(), ['STDCLASS']);
        $this->assertSame('stdClass', $d['classes']);
    }

    public function testToArrayAllowedClassesRejectsClosure()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"Closure" is not allowed');
        deepclone_to_array(\Closure::fromCallable('strlen'), [], true);
    }

    public function testToArrayAllowedClassesStaticValueBypassesCheck()
    {
        $d = deepclone_to_array(42, []);
        $this->assertSame(['value' => 42], $d);
    }

    public function testFromArrayAllowedClassesRejectsDisallowed()
    {
        $d = deepclone_to_array(new \stdClass());
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"stdClass" is not allowed');
        deepclone_from_array($d, []);
    }

    public function testFromArrayAllowedClassesCaseInsensitive()
    {
        $d = deepclone_to_array(new \stdClass());
        $c = deepclone_from_array($d, ['STDCLASS']);
        $this->assertInstanceOf(\stdClass::class, $c);
    }

    public function testFromArrayAllowedClassesRejectsClosureInMask()
    {
        $d = deepclone_to_array(\Closure::fromCallable('strlen'), allow_named_closures: true);
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"Closure" is not allowed');
        deepclone_from_array($d, ['stdClass']);
    }

    public function testFromArrayAllowedClassesStaticValueBypassesCheck()
    {
        $this->assertSame(42, deepclone_from_array(['value' => 42], []));
    }

    public function testRoundTripWithAllowedClasses()
    {
        $o = new \stdClass();
        $o->x = 1;
        $d = deepclone_to_array($o, ['stdClass']);
        $c = deepclone_from_array($d, ['stdClass']);
        $this->assertSame(1, $c->x);
    }

    public function testAllowedChildCarriesParentDeclaredPrivateState()
    {
        $c = new AllowedChild();
        (function () { $this->secret = 'inherited'; })->bindTo($c, AllowedParent::class)();
        $c->pub = 'visible';

        $d = deepclone_to_array($c, ['Symfony\Polyfill\Tests\DeepClone\AllowedChild']);
        $r = deepclone_from_array($d, ['Symfony\Polyfill\Tests\DeepClone\AllowedChild']);
        $this->assertInstanceOf(AllowedChild::class, $r);
        $this->assertSame('inherited', $r->getSecret());
        $this->assertSame('visible', $r->pub);
    }

    /**
     * @requires extension mongodb
     */
    public function testMongoDbBsonRoundTrip()
    {
        $roundtrip = static function (mixed $value): bool {
            $clone = deepclone_from_array(deepclone_to_array($value));

            return serialize($clone) === serialize($value);
        };

        // Stateless types
        $this->assertTrue($roundtrip(new \MongoDB\BSON\MinKey()));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\MaxKey()));

        // Value types carrying state via __serialize / __unserialize
        $this->assertTrue($roundtrip(new \MongoDB\BSON\ObjectId('507f1f77bcf86cd799439011')));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Binary("\x00\x01\x02\x03", \MongoDB\BSON\Binary::TYPE_GENERIC)));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Binary(random_bytes(16), \MongoDB\BSON\Binary::TYPE_UUID)));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\UTCDateTime(1000)));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Regex('^foo', 'i')));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Decimal128('3.14159265358979323846')));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Int64(\PHP_INT_MAX)));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Timestamp(1, 1234567890)));
        $this->assertTrue($roundtrip(new \MongoDB\BSON\Javascript('function(x) { return x; }')));
        $this->assertTrue($roundtrip(\MongoDB\BSON\Document::fromPHP(['_id' => new \MongoDB\BSON\ObjectId('507f1f77bcf86cd799439011'), 'n' => 1])));
        $this->assertTrue($roundtrip(\MongoDB\BSON\PackedArray::fromPHP([new \MongoDB\BSON\ObjectId('507f1f77bcf86cd799439011'), 42])));

        // Shared references: two properties pointing to the same BSON object
        $oid = new \MongoDB\BSON\ObjectId('507f1f77bcf86cd799439011');
        $obj = new \stdClass();
        $obj->a = $oid;
        $obj->b = $oid;
        $clone = deepclone_from_array(deepclone_to_array($obj));
        $this->assertEquals($clone->a, $clone->b);   // same value
        $this->assertSame($clone->a, $clone->b);     // object identity preserved in the graph
        $this->assertNotSame($clone->a, $oid);       // but distinct from the original
    }

    /**
     * @requires extension tidy
     */
    public function testTidyNodeRoundTrip()
    {
        $tidy = new \tidy();
        $tidy->parseString('<p><b>hello</b></p>', [], 'utf8');
        $b = $tidy->body()->child[0]->child[0]; // <b> node

        $clone = deepclone_from_array(deepclone_to_array($b));

        $this->assertInstanceOf(\tidyNode::class, $clone);
        $this->assertNotSame($b, $clone);
        $this->assertSame($b->name, $clone->name);
        $this->assertSame($b->value, $clone->value);
    }

    public function testHydrateScopedInstantiate()
    {
        $obj = deepclone_hydrate(HydrateChild::class, [
            "\0".HydrateBase::class."\0secret" => 'hidden',
            'num' => 42,
            'pub' => 'visible',
        ]);

        $this->assertInstanceOf(HydrateChild::class, $obj);
        $this->assertSame('hidden', $obj->getSecret());
        $this->assertSame(42, $obj->getNum());
        $this->assertSame('visible', $obj->pub);
    }

    public function testHydrateScopedExistingObject()
    {
        $existing = new HydrateChild();
        $result = deepclone_hydrate($existing, [
            "\0".HydrateBase::class."\0secret" => 'updated',
            'pub' => 'changed',
        ]);

        $this->assertSame($existing, $result);
        $this->assertSame('updated', $result->getSecret());
        $this->assertSame('changed', $result->pub);
        $this->assertSame(0, $result->getNum());
    }

    public function testHydrateScopedStdClass()
    {
        $o = deepclone_hydrate('stdClass', ['x' => 1, 'y' => 'hi']);

        $this->assertSame(1, $o->x);
        $this->assertSame('hi', $o->y);
    }

    public function testHydrateSplObjectStorage()
    {
        // SplObjectStorage ships __serialize/__unserialize since PHP 7.4 —
        // deepclone_hydrate() instantiates, the caller wires up state.
        $o1 = new \stdClass();
        $o2 = new \stdClass();

        $s = deepclone_hydrate('SplObjectStorage');
        $s->__unserialize([[$o1, 'info1', $o2, 'info2'], []]);

        $this->assertSame(2, $s->count());
        $s->rewind();
        $this->assertSame($o1, $s->current());
        $this->assertSame('info1', $s->getInfo());
    }

    public function testHydrateArrayObject()
    {
        $ao = deepclone_hydrate('ArrayObject');
        $ao->__unserialize([\ArrayObject::ARRAY_AS_PROPS, ['x' => 1, 'y' => 2], []]);

        $this->assertInstanceOf(\ArrayObject::class, $ao);
        $this->assertSame(1, $ao['x']);
        $this->assertTrue(\ArrayObject::ARRAY_AS_PROPS === $ao->getFlags());
    }

    public function testHydrateArrayIterator()
    {
        $ai = deepclone_hydrate('ArrayIterator');
        $ai->__unserialize([0, ['a', 'b', 'c'], []]);

        $this->assertInstanceOf(\ArrayIterator::class, $ai);
        $this->assertCount(3, $ai);
    }

    public function testHydrateFlatStdClass()
    {
        $this->assertEquals((object) ['p' => 123], deepclone_hydrate('stdClass', ['p' => 123]));
    }

    public function testHydrateFlatCaseInsensitiveClass()
    {
        $this->assertEquals((object) ['p' => 123], deepclone_hydrate('STDcLASS', ['p' => 123]));
    }

    public function testHydrateFlatMangledKeys()
    {
        $expected = [
            "\0*\0prot" => 345,
            "\0".HydrateBar::class."\0priv" => 123,
            "\0".HydrateFoo::class."\0priv" => 234,
            'dyn' => 456,
            'ro' => 567,
        ];

        $actual = (array) deepclone_hydrate(HydrateBar::class, $expected);
        ksort($actual);

        $this->assertSame($expected, $actual);
    }

    public function testHydrateFlatExceptionTrace()
    {
        $e = deepclone_hydrate('Exception', ['trace' => [234]]);

        $this->assertSame([234], $e->getTrace());
    }

    public function testHydrateFlatReadonlyInitialized()
    {
        $obj = new HydrateReadonly(123);
        try {
            deepclone_hydrate($obj, ['value' => 456]);
            $this->fail('Expected Error on readonly overwrite');
        } catch (\Error $e) {
            $this->assertStringContainsString('readonly', $e->getMessage());
        }
        $this->assertSame(123, $obj->getValue());
    }

    public function testHydrateFlatReadonlyUninitialized()
    {
        $obj = deepclone_hydrate(HydrateReadonly::class, ['value' => 456]);

        $this->assertSame(456, $obj->getValue());
    }

    public function testHydratePhpReferences()
    {
        $properties = ['p1' => 1];
        $properties['p2'] = &$properties['p1'];

        $obj = deepclone_hydrate('stdClass', $properties, \DEEPCLONE_HYDRATE_PRESERVE_REFS);

        $this->assertSame(1, $obj->p1);
        $this->assertSame(1, $obj->p2);

        $properties['p1'] = 2;
        $this->assertSame(2, $obj->p1);
        $this->assertSame(2, $obj->p2);
    }

    public function testHydratePhpReferencesNotPreservedByDefault()
    {
        $properties = ['p1' => 1];
        $properties['p2'] = &$properties['p1'];

        $obj = deepclone_hydrate('stdClass', $properties);

        $this->assertSame(1, $obj->p1);
        $this->assertSame(1, $obj->p2);

        $properties['p1'] = 2;
        $this->assertSame(1, $obj->p1);
        $this->assertSame(1, $obj->p2);
    }

    public function testHydrateScopedReferences()
    {
        $v = 'hello';
        $obj = deepclone_hydrate('stdClass', ['x' => &$v, 'y' => &$v], \DEEPCLONE_HYDRATE_PRESERVE_REFS);

        $v = 'world';
        $this->assertSame('world', $obj->x);
        $this->assertSame('world', $obj->y);
    }

    public function testHydrateScopedReferencesNotPreservedByDefault()
    {
        $v = 'hello';
        $obj = deepclone_hydrate('stdClass', ['x' => &$v, 'y' => &$v]);

        $v = 'world';
        $this->assertSame('hello', $obj->x);
        $this->assertSame('hello', $obj->y);
    }

    public function testHydrateClassNotFound()
    {
        $this->expectException(\DeepClone\ClassNotFoundException::class);
        $this->expectExceptionMessage('NoSuchClass99');
        deepclone_hydrate('NoSuchClass99');
    }

    public function testHydrateAbstractClass()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('SplHeap');
        deepclone_hydrate('SplHeap');
    }

    public function testHydrateInterface()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('Throwable');
        deepclone_hydrate('Throwable');
    }

    public function testHydrateNoArgInstantiate()
    {
        $o = deepclone_hydrate('stdClass');

        $this->assertInstanceOf(\stdClass::class, $o);
    }

    public function testHydrateEnum()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_hydrate(HydrateColor::class);
    }

    public function testHydrateTrait()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_hydrate(HydrateTrait::class);
    }

    public function testHydrateGrandparentPrivate()
    {
        $o = deepclone_hydrate(HydrateC::class, [
            "\0".HydrateGP::class."\0secret" => 'gp_val',
            "\0".HydrateP::class."\0mid" => 42,
            'pub' => 'hi',
        ]);

        $this->assertSame('gp_val', $o->getSecret());
        $this->assertSame(42, $o->getMid());
        $this->assertSame('hi', $o->pub);
    }

    public function testHydrateBareNameReachesParentPrivate()
    {
        // Bare "secret" on HydrateC (which extends HydrateP extends HydrateGP, where
        // GP declares private $secret) resolves to GP's private slot — no mangled key
        // needed as long as there's only one private of that name in the chain.
        $o = deepclone_hydrate(HydrateC::class, [
            'secret' => 'bare_gp_val',
            'mid' => 7,       // bare, resolves to HydrateP's private $mid
            'pub' => 'hi',    // bare public on HydrateC
        ]);

        $this->assertSame('bare_gp_val', $o->getSecret());
        $this->assertSame(7, $o->getMid());
        $this->assertSame('hi', $o->pub);

        // No stray dynamic properties should have been created.
        $props = (array) $o;
        $this->assertArrayNotHasKey('secret', $props);
        $this->assertArrayNotHasKey('mid', $props);
        $this->assertArrayHasKey("\0".HydrateGP::class."\0secret", $props);
        $this->assertArrayHasKey("\0".HydrateP::class."\0mid", $props);
    }

    public function testHydrateReflectorSubclass()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_hydrate('ReflectionClass');
    }

    public function testHydrateClosure()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_hydrate('Closure');
    }

    public function testHydrateSplFileInfo()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_hydrate('SplFileInfo');
    }

    public function testRoundtripWithAbstractParentScope()
    {
        $o = new AbstractScopeChild('entity');
        $o->setSecret('changed');
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame('entity', $clone->sourceEntity);
    }

    public function testRoundtripWithPrivatePropertyOnAbstractParent()
    {
        $o = new AbstractWithPrivateChild();
        $o->set('changed');
        $o->pub = 'hello';
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame('changed', $clone->get());
        $this->assertSame('hello', $clone->pub);
    }

    public function testSleepSilentlySkipsUninitializedTypedProperty()
    {
        $o = new AbstractScopeChild('entity');
        $errors = [];
        set_error_handler(static function ($_, $msg) use (&$errors) {
            $errors[] = $msg;

            return true;
        });

        try {
            deepclone_to_array($o);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors);
    }

    public function testCacheIsolationBetweenScopeHydratorAndClassReflector()
    {
        $child = new CacheIsolationChild();
        $child->pub = 'hi';
        $child->setPriv('override');
        deepclone_from_array(deepclone_to_array($child));

        $p = new CacheIsolationParent();
        $p->setPriv('direct');
        $clone = deepclone_from_array(deepclone_to_array($p));

        $this->assertSame('direct', $clone->getPriv());
    }

    public function testHydrateNulInMiddleOfPropertyNameMatchesUnserialize()
    {
        // Matches unserialize(): NUL in the middle of a dynamic property name
        // is silently accepted — the engine stores the raw name.
        $o = deepclone_hydrate('stdClass', ["foo\0bar" => 'val']);
        $this->assertSame('val', ((array) $o)["foo\0bar"]);
    }

    public function testHydrateNulInMangledKeyProperty()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('invalid mangled key');
        deepclone_hydrate('stdClass', ["\0*\0foo\0bar" => 'val']);
    }

    public function testHydrateIntegerKeyInsideScopeMatchesUnserialize()
    {
        // Matches unserialize(): integer keys coerce to string on dynamic
        // property access; no pre-validation rejects them.
        $o = deepclone_hydrate('stdClass', [0 => 'val']);
        $this->assertSame('val', $o->{'0'});
    }

    public function testNumericPropertyNameRoundTrip()
    {
        // GH-64548: a numeric property name like $o->{'999'} must round-trip
        // the same way serialize()/unserialize() handles it. PHP normalizes
        // numeric string keys to integers, so the wire format uses an int key.
        $cfg = new \stdClass();
        $cfg->{'999'} = ['TST'];

        $d = deepclone_to_array($cfg);
        $this->assertSame(999, array_key_first($d['properties']['stdClass']));

        $clone = deepclone_from_array($d);
        $this->assertEquals($cfg, $clone);
        $this->assertSame(['TST'], $clone->{'999'});
    }

    public function testNumericPropertyNameSurvivesSerializationFormats()
    {
        // The int-keyed payload must survive a var_export()/require round-trip
        // (the OPcache cache.php use case) and a JSON round-trip, both of which
        // re-normalize a "999" key back to the integer 999.
        $o = new \stdClass();
        $o->{'0'} = 'zero';
        $o->normal = 1;

        $d = deepclone_to_array($o);
        $viaExport = deepclone_from_array(eval('return '.var_export($d, true).';'));
        $viaJson = deepclone_from_array(json_decode(json_encode($d), true));

        $this->assertEquals($o, $viaExport);
        $this->assertEquals($o, $viaJson);
    }

    public function testNumericPropertyLeadingZeroStaysString()
    {
        // "007" is not a canonical integer key, so it stays a string — exactly
        // as PHP arrays and serialize() treat it.
        $o = new \stdClass();
        $o->{'007'} = 'keepstr';
        $o->{'8'} = 'int';

        $d = deepclone_to_array($o);
        $this->assertSame(['007', 8], array_keys($d['properties']['stdClass']));
        $this->assertEquals($o, deepclone_from_array($d));
    }

    public function testNumericPropertyOnTypedObjectRoundTrip()
    {
        $f = new DeepCloneNumericHolder();
        $f->{'999'} = ['TST'];
        $f->a = 5;

        $this->assertEquals($f, deepclone_from_array(deepclone_to_array($f)));
    }

    public function testNumericPropertyPreservesSharedObjectIdentity()
    {
        $shared = new \stdClass();
        $shared->x = 1;
        $o = new \stdClass();
        $o->{'42'} = $shared;
        $o->ref2 = $shared;

        $clone = deepclone_from_array(deepclone_to_array($o));
        $this->assertSame($clone->{'42'}, $clone->ref2);
    }

    public function testFromArrayRejectsUnloadedScope()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('scope "NoSuchScope"');
        deepclone_from_array([
            'classes' => ScopeChild::class,
            'objectMeta' => 1,
            'prepared' => 0,
            'properties' => ['NoSuchScope' => ['pub' => [0 => 1]]],
        ]);
    }

    public function testHydrateRejectsMangledKeyWithEmptyClass()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('invalid mangled key');
        deepclone_hydrate('stdClass', ["\0\0x" => 1]);
    }

    public function testHydrateRejectsMangledKeyNoSecondNul()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('invalid mangled key');
        deepclone_hydrate('stdClass', ["\0broken" => 1]);
    }

    public function testHydrateRejectsMangledKeyWithNonParentClass()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('not a parent');
        deepclone_hydrate('stdClass', ["\0SomeUnrelatedClass\0x" => 1]);
    }

    public function testHydrateRejectsMangledProtectedKeyForUndeclaredProp()
    {
        // "\0*\0undeclared" specifically targets a protected slot; if the
        // slot doesn't exist, reject instead of silently creating a dynamic
        // property.
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('does not declare');
        deepclone_hydrate(HydrateReadonly::class, ["\0*\0undeclared" => 1]);
    }

    public function testHydrateRejectsMangledPrivateKeyForUndeclaredProp()
    {
        // "\0ParentClass\0undeclared" with a valid parent class but a prop
        // not declared on it — same reasoning as above.
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('does not declare');
        deepclone_hydrate(HydrateC::class, ["\0".HydrateGP::class."\0undeclared" => 1]);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateInvokesSetHookForVirtualProperty()
    {
        $h = deepclone_hydrate(HookedProps::class, ['x' => 5]);
        $this->assertSame(6, $h->x);
    }

    /**
     * @requires PHP 8.4
     */
    public function testRoundtripOnlyWritesBackingStorage()
    {
        $orig = new HookedProps();
        $orig->x = 20;
        $clone = deepclone_from_array(deepclone_to_array($orig));
        $this->assertSame(21, $clone->x);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateBypassesSetHookForNonVirtualProperty()
    {
        $h = deepclone_hydrate(HookedBackingProps::class, ['x' => 7]);
        $this->assertSame(7, $h->x);
    }

    public function testPrivateShadowingDistinctSlotsPerScope()
    {
        $b = new PrivShadowB();
        deepclone_hydrate($b, [
            "\0".PrivShadowA::class."\0x" => 'A_written',
            'x' => 'B_written',
        ]);
        $this->assertSame('A_written', $b->get());
        $this->assertSame('B_written', $b->getChild());
    }

    public function testPrivateShadowingRoundtripPreservesBothSlots()
    {
        $orig = new PrivShadowB();
        (new \ReflectionProperty(PrivShadowA::class, 'x'))->setValue($orig, 'parent_val');
        (new \ReflectionProperty(PrivShadowB::class, 'x'))->setValue($orig, 'child_val');

        $clone = deepclone_from_array(deepclone_to_array($orig));
        $this->assertSame('parent_val', $clone->get());
        $this->assertSame('child_val', $clone->getChild());
    }

    public function testPrivateShadowingBareMangledNameTargetsMostDerived()
    {
        $b = new PrivShadowB();
        deepclone_hydrate($b, ['x' => 'bare_val']);
        $this->assertSame('a_init', $b->get());
        $this->assertSame('bare_val', $b->getChild());
    }

    public function testPrivateShadowingMangledKeyTargetsParentSlot()
    {
        $b = new PrivShadowB();
        deepclone_hydrate($b, ["\0".PrivShadowA::class."\0x" => 'parent_targeted']);
        $this->assertSame('parent_targeted', $b->get());
        $this->assertSame('b_init', $b->getChild());
    }

    public function testHydrateTypedPropTypeMismatchThrows()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('type int');
        deepclone_hydrate(TypedInt::class, ['x' => 'hello']);
    }

    public function testHydrateTypedPropCoercesNonStrict()
    {
        $o = deepclone_hydrate(TypedInt::class, ['x' => '42']);
        $this->assertSame(42, $o->x);
    }

    public function testHydrateReadonlyTypedMismatchThrows()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('type int');
        deepclone_hydrate(TypedReadonly::class, ['v' => 'nope']);
    }

    public function testFromArrayTypedPropMismatchThrows()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('type int');
        deepclone_from_array([
            'classes' => TypedInt::class,
            'objectMeta' => 1,
            'prepared' => 0,
            'properties' => [TypedInt::class => ['x' => [0 => 'hello']]],
        ]);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateFlagCallHooksInvokesSetHook()
    {
        $o = deepclone_hydrate(HookedBackingProps::class, ['x' => 7], \DEEPCLONE_HYDRATE_CALL_HOOKS);
        $this->assertSame(70, $o->x);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateDefaultFlagBypassesSetHook()
    {
        $o = deepclone_hydrate(HookedBackingProps::class, ['x' => 7]);
        $this->assertSame(7, $o->x);
    }

    public function testHydrateFlagsUnknownBitThrows()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('unknown bits');
        deepclone_hydrate(\stdClass::class, [], 1 << 30);
    }

    public function testHydrateFlagsMutuallyExclusiveThrows()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('mutually exclusive');
        deepclone_hydrate(\stdClass::class, [], \DEEPCLONE_HYDRATE_CALL_HOOKS | \DEEPCLONE_HYDRATE_NO_LAZY_INIT);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateNoLazyInitSkipsInitializer()
    {
        $rc = new \ReflectionClass(TypedInt::class);
        $initRan = 0;
        $ghost = $rc->newLazyGhost(static function (TypedInt $o) use (&$initRan) {
            ++$initRan;
            $o->x = 1;
        });
        deepclone_hydrate($ghost, ['x' => 99], \DEEPCLONE_HYDRATE_NO_LAZY_INIT);
        $this->assertSame(0, $initRan);
        $this->assertSame(99, $ghost->x);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateDefaultFlagRunsLazyInitializer()
    {
        $rc = new \ReflectionClass(TypedInt::class);
        $initRan = 0;
        $ghost = $rc->newLazyGhost(static function (TypedInt $o) use (&$initRan) {
            ++$initRan;
            $o->x = 1;
        });
        deepclone_hydrate($ghost, ['x' => 99]);
        $this->assertSame(1, $initRan);
        $this->assertSame(99, $ghost->x);
    }

    public function testHydrateReadonlyIdempotentSkipsSameValue()
    {
        $obj = new HydrateReadonly(123);
        $obj = deepclone_hydrate($obj, ['value' => 123]);
        $this->assertSame(123, $obj->getValue());
    }

    public function testHydrateReadonlyDifferentValueThrows()
    {
        $obj = new HydrateReadonly(123);
        try {
            deepclone_hydrate($obj, ['value' => 456]);
            $this->fail('Expected Error on readonly overwrite');
        } catch (\Error $e) {
            $this->assertStringContainsString('readonly', $e->getMessage());
        }
        $this->assertSame(123, $obj->getValue());
    }

    public function testHydrateReadonlyObjectIdentityIsSkipped()
    {
        $inner = new \stdClass();
        $host = new HydrateReadonlyObject($inner);
        $host = deepclone_hydrate($host, ['o' => $inner]);
        $this->assertSame($inner, $host->o);
    }

    public function testHydrateNullIntoNonNullableTypedUnsetsSlot()
    {
        $o = new TypedInt();
        $o = deepclone_hydrate($o, ['x' => null]);
        $this->assertFalse((new \ReflectionProperty(TypedInt::class, 'x'))->isInitialized($o));
    }

    public function testHydrateNullIntoNullableTypedKeepsNull()
    {
        $o = new HydrateNullableInt();
        deepclone_hydrate($o, ['y' => null]);
        $this->assertNull($o->y);
    }

    public function testHydrateCallHooksStillUnsetsOnNullForNonHookedProps()
    {
        // Per-prop gate: TypedInt::$x is not hooked, so A2 still applies
        // even under CALL_HOOKS.
        $o = new TypedInt();
        $o = deepclone_hydrate($o, ['x' => null], \DEEPCLONE_HYDRATE_CALL_HOOKS);
        $this->assertFalse((new \ReflectionProperty(TypedInt::class, 'x'))->isInitialized($o));
    }

    public function testHydrateStringCastToBackedEnum()
    {
        $o = deepclone_hydrate(WithBackedEnums::class, ['s' => 'S']);
        $this->assertSame(DeepCloneHydrateSuit::Spades, $o->s);
    }

    public function testHydrateIntCastToBackedEnum()
    {
        $o = deepclone_hydrate(WithBackedEnums::class, ['n' => 2]);
        $this->assertSame(DeepCloneHydrateSize::Large, $o->n);
    }

    public function testHydrateNullableBackedEnumKeepsNull()
    {
        $o = deepclone_hydrate(WithBackedEnums::class, ['ns' => null]);
        $this->assertNull($o->ns);
    }

    public function testHydrateNullableBackedEnumCastsScalar()
    {
        $o = deepclone_hydrate(WithBackedEnums::class, ['ns' => 'S']);
        $this->assertSame(DeepCloneHydrateSuit::Spades, $o->ns);
    }

    public function testHydrateUnknownEnumValueThrows()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('not a valid backing value for enum');
        deepclone_hydrate(WithBackedEnums::class, ['s' => 'X']);
    }

    public function testHydrateEnumCastStillAppliesToNonHookedPropsUnderCallHooks()
    {
        // Per-prop gate: WithBackedEnums::$s has no set hook, so A3 still
        // applies even under CALL_HOOKS.
        $o = deepclone_hydrate(WithBackedEnums::class, ['s' => 'S'], \DEEPCLONE_HYDRATE_CALL_HOOKS);
        $this->assertSame(DeepCloneHydrateSuit::Spades, $o->s);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateEnumCastAppliesToHookedPropUnderCallHooks()
    {
        // Property-type-only rule: the cast is decided from the prop type,
        // not the hook signature. The hook receives the enum case.
        $o = deepclone_hydrate(HookedEnumMatchingParam::class, ['s' => 'S'], \DEEPCLONE_HYDRATE_CALL_HOOKS);
        $this->assertSame(DeepCloneHydrateSuit::Spades, $o->s);
    }

    /**
     * @requires PHP 8.4
     */
    public function testHydrateEnumCastAppliesEvenWhenHookParamIsWider()
    {
        // Wider hook signature `set(Suit|string $v)` doesn't change the
        // hydrate decision: cast first, hook receives the enum case via
        // its non-string union arm.
        $o = deepclone_hydrate(HookedEnumWiderParam::class, ['s' => 'S'], \DEEPCLONE_HYDRATE_CALL_HOOKS);
        $this->assertSame(DeepCloneHydrateSuit::Spades, $o->s);
        $this->assertNull(HookedEnumWiderParam::$lastRaw);
    }

    /**
     * @requires PHP 8.4
     * @requires extension bcmath
     */
    public function testRoundTripBcMathNumber()
    {
        // BcMath\Number is a final internal class with a custom create_object,
        // so newInstanceWithoutConstructor() is rejected and an empty O:
        // unserialize is refused by __unserialize(). The round-trip must still
        // reconstruct it through a full serialization replay.
        $n = new \BcMath\Number('12.34');
        $o = (object) ['a' => $n, 'b' => $n, 'list' => [$n, new \BcMath\Number('5')]];

        $c = deepclone_from_array(deepclone_to_array($o));

        $this->assertInstanceOf(\BcMath\Number::class, $c->a);
        $this->assertSame('12.34', (string) $c->a);
        $this->assertSame(2, $c->a->scale);
        $this->assertNotSame($n, $c->a, 'is a real clone, not the original instance');
        $this->assertSame($c->a, $c->b, 'shared identity is preserved');
        $this->assertSame($c->a, $c->list[0], 'shared identity is preserved across the graph');
        $this->assertSame('5', (string) $c->list[1]);
    }

    /**
     * @requires PHP 8.4
     * @requires extension bcmath
     */
    public function testRoundTripBcMathNumberTopLevel()
    {
        $c = deepclone_from_array(deepclone_to_array(new \BcMath\Number('99.999')));

        $this->assertInstanceOf(\BcMath\Number::class, $c);
        $this->assertSame('99.999', (string) $c);
        $this->assertSame(3, $c->scale);
        $this->assertSame('100.499', (string) $c->add('0.5'));
    }

    /**
     * @requires PHP 8.4
     * @requires extension bcmath
     */
    public function testHydrateBcMathNumberThrows()
    {
        // deepclone_hydrate() injects properties into an empty shell; a class
        // that only becomes valid through __construct()/__unserialize() cannot
        // be built that way and must be rejected rather than yielding a broken
        // (uninitialized) instance.
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('Class "BcMath\Number" is not instantiable.');
        deepclone_hydrate(\BcMath\Number::class, ['value' => '7.5']);
    }

    /**
     * @requires PHP 8.2
     */
    public function testRoundTripRandomizerAsObjectProperty()
    {
        // Random\Randomizer is a final internal class whose __serialize() nests
        // its engine object, so its deep-clone state carries an object-ref mask
        // and it cannot be built as an early empty shell. Used as a property it
        // must still round-trip: it is finalized once the engine it references
        // exists, before references to it are resolved.
        $seed = 1234;
        $expected = (new \Random\Randomizer(new \Random\Engine\Mt19937($seed)))->getInt(1, \PHP_INT_MAX);

        $g = (object) ['r' => new \Random\Randomizer(new \Random\Engine\Mt19937($seed))];
        $c = deepclone_from_array(deepclone_to_array($g));

        $this->assertInstanceOf(\Random\Randomizer::class, $c->r);
        $this->assertSame($expected, $c->r->getInt(1, \PHP_INT_MAX));
    }

    /**
     * @requires PHP 8.2
     */
    public function testRoundTripRandomizerTopLevelAndNested()
    {
        $top = deepclone_from_array(deepclone_to_array(new \Random\Randomizer(new \Random\Engine\Mt19937(7))));
        $this->assertInstanceOf(\Random\Randomizer::class, $top);

        $arr = deepclone_from_array(deepclone_to_array([new \Random\Randomizer(new \Random\Engine\Mt19937(8))]));
        $this->assertInstanceOf(\Random\Randomizer::class, $arr[0]);

        $deep = deepclone_from_array(deepclone_to_array((object) ['list' => [(object) ['r' => new \Random\Randomizer(new \Random\Engine\Mt19937(9))]]]));
        $this->assertInstanceOf(\Random\Randomizer::class, $deep->list[0]->r);
    }

    public function testFromArrayRejectsUnserializeClassWithoutReplayFlag()
    {
        // A class with __unserialize() is always emitted as a negative-wakeup
        // state replay. A crafted payload that clears that flag and drops the
        // replay would leave the object uninitialized (e.g. a BcMath\Number
        // with a NULL bc_num), so it is rejected rather than reconstructed,
        // matching the extension.
        $d = deepclone_to_array(new DeepCloneSerializeFixture('x', 1));
        $d['objectMeta'][0][1] = 0; // clear the __unserialize flag
        $d['states'] = [];          // drop the replay

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('has an __unserialize() method but "objectMeta" does not flag it for an __unserialize state');
        deepclone_from_array($d);
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayConstExprClosureClassAttributeWireFormat()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, '', 0, 0, $line], $d['prepared']);
        $this->assertSame(1, $d['mask']);
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureClassAttributeRebindsScope()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];

        $clone = deepclone_from_array(deepclone_to_array($closure));

        $this->assertInstanceOf(\Closure::class, $clone);
        $this->assertNotSame($closure, $clone);
        $this->assertSame('class-secret', $clone());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureNestedAttributeArgument()
    {
        $closure = (new \ReflectionProperty(ConstExprClosureFixture::class, 'tagged'))->getAttributes()[0]->getArguments()['cb'][1]['x'];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, '$tagged', 0, 0, $line], $d['prepared']);
        $this->assertSame(6, deepclone_from_array($d)(3));
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureMultipleClosuresInOneAttribute()
    {
        $args = (new \ReflectionClassConstant(ConstExprClosureFixture::class, 'TAGGED'))->getAttributes()[0]->getArguments();

        foreach (['multi-0', 'multi-1'] as $i => $expected) {
            $line = (new \ReflectionFunction($args[$i]))->getStartLine();
            $d = deepclone_to_array($args[$i]);

            $this->assertSame([ConstExprClosureFixture::class, 'TAGGED', 0, $i, $line], $d['prepared']);
            $this->assertSame($expected, deepclone_from_array($d)());
        }
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureRepeatedAttribute()
    {
        $closure = (new \ReflectionMethod(ConstExprClosureFixture::class, 'tagged'))->getAttributes()[1]->getArguments()[0];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, 'tagged()', 1, 0, $line], $d['prepared']);
        $this->assertSame('repeated', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureParameterAttribute()
    {
        $closure = (new \ReflectionMethod(ConstExprClosureFixture::class, 'tagged'))->getParameters()[0]->getAttributes()[0]->getArguments()[0];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, 'tagged()#0', 0, 0, $line], $d['prepared']);
        $this->assertSame('param-attr', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureParameterDefault()
    {
        $closure = (new \ReflectionMethod(ConstExprClosureFixture::class, 'tagged'))->getParameters()[0]->getDefaultValue();
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, 'tagged()#0', null, 0, $line], $d['prepared']);
        $this->assertSame('param-default', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosurePropertyDefault()
    {
        $closure = (new \ReflectionProperty(ConstExprClosureFixture::class, 'factory'))->getDefaultValue();
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, '$factory', null, 0, $line], $d['prepared']);
        $this->assertSame('prop-default', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureStaticPropertyDefault()
    {
        $closure = (new \ReflectionProperty(ConstExprClosureFixture::class, 'staticFactory'))->getDefaultValue();
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, '$staticFactory', null, 0, $line], $d['prepared']);
        $this->assertSame('static-prop-default', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureClassConstantValue()
    {
        $closure = ConstExprClosureFixture::CALLBACKS['first'];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprClosureFixture::class, 'CALLBACKS', null, 0, $line], $d['prepared']);
        $this->assertSame('const-value', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureEnumCaseAttribute()
    {
        $closure = (new \ReflectionClassConstant(ConstExprEnumFixture::class, 'Active'))->getAttributes()[0]->getArguments()[0];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprEnumFixture::class, 'Active', 0, 0, $line], $d['prepared']);
        $this->assertSame('enum-case-attr', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureEnumConstant()
    {
        $closure = ConstExprEnumFixture::FILTER;
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprEnumFixture::class, 'FILTER', null, 0, $line], $d['prepared']);
        $this->assertSame('enum-const', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureTraitMethodAttribute()
    {
        $closure = (new \ReflectionClass(ConstExprTraitUserFixture::class))->getMethod('traitTagged')->getAttributes()[0]->getArguments()[0];

        $d = deepclone_to_array($closure);

        $this->assertSame(ConstExprTraitUserFixture::class, $d['prepared'][0]);
        $this->assertSame('trait-attr', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosurePromotedParameterAttribute()
    {
        $closure = (new \ReflectionProperty(ConstExprPromotedFixture::class, 'promoted'))->getAttributes()[0]->getArguments()[0];
        $line = (new \ReflectionFunction($closure))->getStartLine();

        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprPromotedFixture::class, '__construct()#0', 0, 0, $line], $d['prepared']);
        $this->assertSame('promoted-attr', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureSameLineDistinctSignatures()
    {
        $args = (new \ReflectionClass(ConstExprSameLineFixture::class))->getAttributes()[0]->getArguments();

        $this->assertSame('noargs', deepclone_from_array(deepclone_to_array($args[0]))());
        $this->assertSame('witharg', deepclone_from_array(deepclone_to_array($args[1]))(1));
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayConstExprClosureAmbiguousSameLine()
    {
        $args = (new \ReflectionClass(ConstExprAmbiguousFixture::class))->getAttributes()[0]->getArguments();

        if (\extension_loaded('deepclone') && !TestListenerTrait::$enabledPolyfills) {
            // The extension tells same-line closures apart by op_array identity.
            $this->assertSame('first', deepclone_from_array(deepclone_to_array($args[0]))());
            $this->assertSame('second', deepclone_from_array(deepclone_to_array($args[1]))());

            return;
        }

        // The polyfill only knows file/line/signature, which cannot tell them apart.
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('multiple closures share this declaration site');
        deepclone_to_array($args[0]);
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayRuntimeStaticClosureIsNotInstantiable()
    {
        $closure = static function (): string { return 'runtime'; };

        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('Type "Closure" is not instantiable.');
        deepclone_to_array($closure);
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureInsideObjectGraph()
    {
        $classClosure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];
        $propClosure = (new \ReflectionProperty(ConstExprClosureFixture::class, 'tagged'))->getAttributes()[0]->getArguments()['cb'][1]['x'];
        $graph = new ConstraintLikeFixture($classClosure, [new ConstraintLikeFixture($propClosure)]);

        $clone = deepclone_from_array(deepclone_to_array($graph));

        $this->assertInstanceOf(ConstraintLikeFixture::class, $clone);
        $this->assertInstanceOf(\Closure::class, $clone->callback);
        $this->assertSame('class-secret', ($clone->callback)());
        $this->assertSame(6, ($clone->constraints[0]->callback)(3));
    }

    /**
     * @requires PHP 8.5
     */
    public function testConstExprClosurePayloadSurvivesJsonRoundTrip()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];
        $graph = new ConstraintLikeFixture($closure);

        $d = json_decode(json_encode(deepclone_to_array($graph)), true);

        $this->assertSame('class-secret', (deepclone_from_array($d)->callback)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testFromArrayConstExprClosureStaleLineThrows()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];
        $d = deepclone_to_array($closure);
        ++$d['prepared'][4];

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('stale payload');
        deepclone_from_array($d);
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayAllowedClassesRejectsConstExprClosure()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"Closure" is not allowed');
        deepclone_to_array($closure, []);
    }

    /**
     * @requires PHP 8.5
     */
    public function testFromArrayAllowedClassesRejectsConstExprClosureInMask()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];
        $d = deepclone_to_array($closure);

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"Closure" is not allowed');
        deepclone_from_array($d, []);
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureWithAllowedClasses()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];

        $clone = deepclone_from_array(deepclone_to_array($closure, ['Closure']), ['Closure', ConstExprClosureFixture::class]);

        $this->assertSame('class-secret', $clone());
    }

    /**
     * @requires PHP 8.5
     */
    public function testFromArrayConstExprClosureClassMustBeAllowed()
    {
        $closure = (new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0];
        $d = deepclone_to_array($closure, ['Closure']);

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('class "'.ConstExprClosureFixture::class.'" is not allowed');
        deepclone_from_array($d, ['Closure']);
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayConstExprClosureAllowedGatePrecedesAmbiguity()
    {
        $closure = (new \ReflectionClass(ConstExprAmbiguousFixture::class))->getAttributes()[0]->getArguments()[0];

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('class "Closure" is not allowed');
        deepclone_to_array($closure, []);
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureFactoryConstant()
    {
        $outer = ConstExprFactoryFixture::FACTORY;

        $this->assertSame('inner', deepclone_from_array(deepclone_to_array($outer))()());

        $this->expectException(\DeepClone\NotInstantiableException::class);
        deepclone_to_array($outer());
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayRuntimeClosureCollidingWithConstExprSite()
    {
        $attrClosure = (new \ReflectionMethod(ConstExprRuntimeCollisionFixture::class, 'make'))->getAttributes()[0]->getArguments()[0];

        if (\extension_loaded('deepclone') && !TestListenerTrait::$enabledPolyfills) {
            // The extension tells the attribute literal and the same-line runtime
            // literal apart by op_array identity.
            $this->assertSame('const-expr', deepclone_from_array(deepclone_to_array($attrClosure))());

            $this->expectException(\DeepClone\NotInstantiableException::class);
            deepclone_to_array(ConstExprRuntimeCollisionFixture::make());

            return;
        }

        // Both closures share the method-derived name, line and signature; the
        // polyfill cannot tell them apart and refuses both.
        try {
            deepclone_to_array($attrClosure);
            $this->fail('Expected ValueError was not thrown');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('multiple closures share this declaration site', $e->getMessage());
        }
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('multiple closures share this declaration site');
        deepclone_to_array(ConstExprRuntimeCollisionFixture::make());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureEvaluatedThroughTwoSurfaces()
    {
        $closure = (new ConstExprDualSurfaceFixture())->cb;

        $this->assertSame('dual-surface', deepclone_from_array(deepclone_to_array($closure))());
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosureAliasedTraitMethod()
    {
        $closure = (new \ReflectionMethod(ConstExprTraitAliasFixture::class, 'traitTagged'))->getAttributes()[0]->getArguments()[0];

        $this->assertSame('trait-attr', deepclone_from_array(deepclone_to_array($closure))());
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayConstExprClosureWithoutSourcesInvitesExtension()
    {
        if (!class_exists('ConstExprNoSourcesFixture', false)) {
            $file = sys_get_temp_dir().'/ConstExprNoSourcesFixture.php';
            file_put_contents($file, '<?php class ConstExprNoSourcesFixture { #[Symfony\\Polyfill\\Tests\\DeepClone\\ConstExprAttr(static function (): string { return "no-sources"; })] public function m(): void {} }');
            require $file;
            unlink($file);
        }
        $closure = (new \ReflectionMethod(\ConstExprNoSourcesFixture::class, 'm'))->getAttributes()[0]->getArguments()[0];

        if (\extension_loaded('deepclone') && !TestListenerTrait::$enabledPolyfills) {
            // The extension matches op_arrays and needs no source files.
            $this->assertSame('no-sources', deepclone_from_array(deepclone_to_array($closure))());

            return;
        }

        // Method-scoped names require reading the file to rule out aliasing.
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('install the "deepclone" extension');
        deepclone_to_array($closure);
    }

    /**
     * @requires PHP 8.5
     */
    public function testRoundTripConstExprClosurePropertyHookAttributes()
    {
        $closure = (new \ReflectionProperty(ConstExprHookedFixture::class, 'virtual'))->getHook(\PropertyHookType::Get)->getAttributes()[0]->getArguments()[0];
        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprHookedFixture::class, '$virtual::get()', 0, 0, (new \ReflectionFunction($closure))->getStartLine()], $d['prepared']);
        $this->assertSame('get-hook-attr', deepclone_from_array($d)());

        $closure = (new \ReflectionProperty(ConstExprHookedFixture::class, 'stored'))->getHook(\PropertyHookType::Set)->getParameters()[0]->getAttributes()[0]->getArguments()[0];
        $d = deepclone_to_array($closure);

        $this->assertSame([ConstExprHookedFixture::class, '$stored::set()#0', 0, 0, (new \ReflectionFunction($closure))->getStartLine()], $d['prepared']);
        $this->assertSame('set-hook-param-attr', deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testFromArrayConstExprClosureMalformedPayloads()
    {
        $line = (new \ReflectionFunction((new \ReflectionClass(ConstExprClosureFixture::class))->getAttributes()[0]->getArguments()[0]))->getStartLine();
        $cases = [
            ['foo', 'const-expr-closure value must be of type array, string given'],
            [[ConstExprClosureFixture::class], 'const-expr-closure value must have 5 elements'],
            [[42, '', 0, 0, $line], 'const-expr-closure class name must be of type string, int given'],
            [['No\\Such\\ClassAtAll', '', 0, 0, $line], 'const-expr-closure references unknown class "No\\Such\\ClassAtAll"'],
            [[ConstExprClosureFixture::class, 42, 0, 0, $line], 'const-expr-closure site must be of type string, int given'],
            [[ConstExprClosureFixture::class, '', 'x', 0, $line], 'const-expr-closure attribute index must be of type int or null, string given'],
            [[ConstExprClosureFixture::class, '', 0, 'x', $line], 'const-expr-closure closure index must be of type int, string given'],
            [[ConstExprClosureFixture::class, '', 0, 0, 'x'], 'const-expr-closure line must be of type int, string given'],
            [[ConstExprClosureFixture::class, '$nope', 0, 0, $line], 'const-expr-closure references unknown property "$nope"'],
            [[ConstExprClosureFixture::class, 'nope()', 0, 0, $line], 'const-expr-closure references unknown method "nope()"'],
            [[ConstExprClosureFixture::class, 'NOPE', 0, 0, $line], 'const-expr-closure references unknown constant "NOPE"'],
            [[ConstExprClosureFixture::class, 'tagged()#9', 0, 0, $line], 'const-expr-closure references unknown parameter "tagged()#9"'],
            [[ConstExprClosureFixture::class, '', 9, 0, $line], 'const-expr-closure references unknown attribute index 9'],
            [[ConstExprClosureFixture::class, '', 0, 9, $line], 'const-expr-closure references unknown closure index 9'],
            [[ConstExprClosureFixture::class, '', null, 0, $line], 'const-expr-closure attribute index is required for site ""'],
            [[ConstExprClosureFixture::class, 'tagged()', null, 0, $line], 'const-expr-closure attribute index is required for site "tagged()"'],
        ];

        foreach ($cases as [$prepared, $expected]) {
            try {
                deepclone_from_array(['classes' => '', 'objectMeta' => 0, 'prepared' => $prepared, 'mask' => 1]);
                $this->fail('Expected ValueError was not thrown for: '.$expected);
            } catch (\ValueError $e) {
                $this->assertStringContainsString($expected, $e->getMessage());
            }
        }
    }

    /**
     * @requires PHP 8.5
     */
    public function testToArrayConstExprClosureFirstClassCallableUsesDeclarationSite()
    {
        // A first-class callable over a method of its own declaring class,
        // declared in a constant expression, is encoded as a declaration-site
        // reference (mask 1) like an anonymous const-expr closure, not by name,
        // so it round-trips without the allow_named_closures opt-in.
        $closure = (new \ReflectionMethod(ConstExprFccFixture::class, 'helper'))->getAttributes()[0]->getArguments()[0];

        $d = deepclone_to_array($closure);

        $this->assertSame(1, $d['mask']);
        $this->assertSame(ConstExprFccFixture::class, $d['prepared'][0]);
        $this->assertTrue(deepclone_from_array($d)());
    }

    /**
     * @requires PHP 8.5
     */
    public function testFromArrayConstExprClosureGlobalInternalFunction()
    {
        // The extension can reference a global internal function declared in an
        // attribute (e.g. #[ConstExprAttr(strlen(...))]); such a reference has
        // no start line and is encoded with line 0. The polyfill cannot produce
        // these (no reflection hook), but must decode them: ReflectionFunction
        // reports no start line for an internal function, so the line-0
        // reference must not be treated as stale.
        $payload = ['classes' => '', 'objectMeta' => 0, 'prepared' => [ConstExprGlobalFccFixture::class, '$p', 0, 0, 0], 'mask' => 1];

        $r = deepclone_from_array($payload);
        $this->assertSame(5, $r('hello'));
    }
}
