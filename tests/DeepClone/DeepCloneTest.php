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

/**
 * @requires PHP 8.1
 */
class DeepCloneTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // deepclone_to_array.phpt — wire-format shape assertions
    // ─────────────────────────────────────────────────────────────────────

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
        $d = deepclone_to_array(\Closure::fromCallable('strlen'));

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_from_array.phpt — happy-path reconstruction
    // ─────────────────────────────────────────────────────────────────────

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
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable('strlen')));

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_from_array_validation.phpt — malformed-payload rejection
    // ─────────────────────────────────────────────────────────────────────

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

    public function testFromArrayRejectsPreparedRefIdUnknown()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"prepared" references unknown ref id 99');
        deepclone_from_array(['classes' => '', 'objectMeta' => 0, 'prepared' => -99]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_closures.phpt
    // ─────────────────────────────────────────────────────────────────────

    public function testClosureGlobalFunctionWireFormat()
    {
        $d = deepclone_to_array(\Closure::fromCallable('strlen'));

        $this->assertSame(0, $d['mask']);
        $this->assertNull($d['prepared'][0]);
        $this->assertSame('strlen', $d['prepared'][1]);
    }

    public function testClosureGlobalFunctionRoundTrip()
    {
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable('strlen')));
        $this->assertSame(5, $clone('hello'));
    }

    public function testClosureStaticMethodWireFormat()
    {
        $d = deepclone_to_array(\Closure::fromCallable([ClosureFixture::class, 'staticMethod']));

        $this->assertSame(ClosureFixture::class, $d['prepared'][0]);
        $this->assertSame('staticMethod', $d['prepared'][1]);
    }

    public function testClosureStaticMethodRoundTrip()
    {
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable([ClosureFixture::class, 'staticMethod'])));
        $this->assertSame('static', $clone());
    }

    public function testClosureInstanceMethodWireFormat()
    {
        $obj = new ClosureFixture();
        $d = deepclone_to_array(\Closure::fromCallable([$obj, 'instanceMethod']));

        $this->assertSame(ClosureFixture::class, $d['classes']);
    }

    public function testClosureInstanceMethodRoundTrip()
    {
        $obj = new ClosureFixture();
        $clone = deepclone_from_array(deepclone_to_array(\Closure::fromCallable([$obj, 'instanceMethod'])));
        $this->assertSame('instance', $clone());
    }

    public function testClosurePrivateMethodWireFormatAndRoundTrip()
    {
        $obj = new ClosureFixture();
        $fn = $obj->getPrivateClosure();
        $d = deepclone_to_array($fn);

        $this->assertSame(0, $d['mask']);

        $clone = deepclone_from_array($d);
        $this->assertSame('private', $clone());
    }

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_datetime.phpt
    // ─────────────────────────────────────────────────────────────────────

    public function testDateTimeRoundTrip()
    {
        $dt = \DateTime::createFromFormat('U', '0');
        $d = deepclone_to_array($dt);

        // Negative wakeup = __unserialize
        $this->assertLessThan(0, $d['objectMeta'][0][1]);

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_enums.phpt
    // ─────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_error_scope.phpt
    // ─────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_serialize.phpt — __serialize / __unserialize / __wakeup / __sleep
    // ─────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_serialize_edge.phpt
    // ─────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_typed_objects.phpt — visibility + inheritance + readonly
    // ─────────────────────────────────────────────────────────────────────

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
        $this->assertTrue(empty($d['properties']) || $d['properties'] === []);
    }

    public function testReadonlyProperties()
    {
        $o = new DeepCloneReadonlyFixture('test', 42);
        $clone = deepclone_from_array(deepclone_to_array($o));

        $this->assertSame('test', $clone->name);
        $this->assertSame(42, $clone->value);
    }

    // ─────────────────────────────────────────────────────────────────────
    // deepclone_doc_behaviors.phpt
    // ─────────────────────────────────────────────────────────────────────

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
        deepclone_to_array(new class {
            public int $x = 1;
        });
    }

    public function testDocBehaviorsSplFileInfoIsRejected()
    {
        $this->expectException(\DeepClone\NotInstantiableException::class);
        $this->expectExceptionMessage('SplFileInfo');
        deepclone_to_array(new \SplFileInfo('/etc/hostname'));
    }

    public function testDocBehaviorsSensitiveParameterValueIsRejected()
    {
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
        deepclone_to_array(STDIN);
    }

    public function testExceptionClassesExtendInvalidArgumentException()
    {
        $this->assertTrue(is_subclass_of(\DeepClone\NotInstantiableException::class, \InvalidArgumentException::class));
        $this->assertTrue(is_subclass_of(\DeepClone\ClassNotFoundException::class, \InvalidArgumentException::class));
    }

    // ── $allowedClasses tests ──────────────────────────────────────────

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
        deepclone_to_array(\Closure::fromCallable('strlen'), []);
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
        $d = deepclone_to_array(\Closure::fromCallable('strlen'));
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
}

// ─────────────────────────────────────────────────────────────────────────
// Fixtures
// ─────────────────────────────────────────────────────────────────────────

enum DeepCloneColor
{
    case Red;
    case Blue;
}

enum DeepCloneSuit: string
{
    case Hearts = 'H';
    case Spades = 'S';
}

class ClosureFixture
{
    public function instanceMethod(): string { return 'instance'; }
    public static function staticMethod(): string { return 'static'; }
    private function privateMethod(): string { return 'private'; }
    public function getPrivateClosure(): \Closure { return \Closure::fromCallable([$this, 'privateMethod']); }
}

class DeepCloneFinalError extends \Error {}

class DeepCloneSerializeFixture
{
    public function __construct(public string $name = '', public int $val = 0) {}
    public function __serialize(): array { return ['n' => $this->name, 'v' => $this->val]; }
    public function __unserialize(array $data): void { $this->name = $data['n']; $this->val = $data['v']; }
}

class DeepCloneWakeupFixture
{
    public string $status = 'sleeping';
    public function __wakeup(): void { $this->status = 'awake'; }
}

class DeepCloneSleepFixture
{
    public string $keep = '';
    public string $skip = '';
    public function __sleep(): array { return ['keep']; }
}

class DeepCloneParentNoUnser
{
    private string $foo = 'foo';
}

class DeepCloneChildNoUnser extends DeepCloneParentNoUnser
{
    public string $baz = '';
    private string $bar = '';
    public function __serialize(): array { return ['foo' => 'foo', 'baz' => 'ccc', 'bar' => 'ddd']; }
}

class DeepCloneUnserOnly
{
    public string $foo = '';
    public function __unserialize(array $data): void { $this->foo = $data['foo'] ?? ''; }
}

class DeepCloneSleepPrivate
{
    public string $good = '';
    protected string $foo = '';
    private string $bar = '';

    public function __sleep(): array
    {
        return ['good', 'foo', "\0*\0foo", "\0".self::class."\0bar"];
    }

    public function setAll(string $g, string $f, string $b): void
    {
        $this->good = $g;
        $this->foo = $f;
        $this->bar = $b;
    }
}

class DeepCloneParentSleep
{
    private string $secret = '';
}

class DeepCloneChildSleep extends DeepCloneParentSleep
{
    public string $pub = '';
    // __sleep returns unmangled "secret" — should NOT match parent's private.
    public function __sleep(): array { return ['pub', 'secret']; }
}

class DeepCloneParentClass
{
    public string $pub = 'pub_default';
    protected int $prot = 0;
    private string $priv = 'parent_priv';

    public function setProt(int $v): void { $this->prot = $v; }
    public function setPriv(string $v): void { $this->priv = $v; }
}

class DeepCloneChildClass extends DeepCloneParentClass
{
    private string $childPriv = 'child_default';
    public function setChildPriv(string $v): void { $this->childPriv = $v; }
}

class DeepCloneReadonlyFixture
{
    public function __construct(
        public readonly string $name,
        public readonly int $value,
    ) {
    }
}
