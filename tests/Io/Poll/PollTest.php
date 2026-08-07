<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Io\Poll;

use Io\IoException;
use Io\Poll\Backend;
use Io\Poll\BackendUnavailableException;
use Io\Poll\Context;
use Io\Poll\Event;
use Io\Poll\FailedHandleAddException;
use Io\Poll\FailedPollOperationException;
use Io\Poll\FailedPollWaitException;
use Io\Poll\FailedWatcherModificationException;
use Io\Poll\Handle;
use Io\Poll\HandleAlreadyWatchedException;
use Io\Poll\InactiveWatcherException;
use Io\Poll\InvalidHandleException;
use Io\Poll\PollException;
use Io\Poll\Watcher;
use PHPUnit\Framework\TestCase;
use Time\Duration;

/**
 * @requires PHP >= 8.1
 */
class PollTest extends TestCase
{
    protected function setUp(): void
    {
        if (\PHP_VERSION_ID >= 80600) {
            $this->markTestSkipped('The Io\Poll polyfill is only used on PHP < 8.6; the native implementation has backend-specific behavior that cannot be reproduced.');
        }
    }

    public function testBackendEnumCases()
    {
        $this->assertTrue(enum_exists(Backend::class));

        $names = array_column(Backend::cases(), 'name');
        $this->assertContains('Auto', $names);
        $this->assertContains('Poll', $names);
        $this->assertContains('Epoll', $names);
        $this->assertContains('Kqueue', $names);
        $this->assertContains('EventPorts', $names);
        $this->assertContains('WSAPoll', $names);
    }

    public function testBackendAutoAndPollAreAvailable()
    {
        $this->assertTrue((Backend::Auto)->isAvailable());
        $this->assertTrue((Backend::Poll)->isAvailable());
    }

    public function testBackendUnavailableInPolyfill()
    {
        $this->assertFalse((Backend::Epoll)->isAvailable());
        $this->assertFalse((Backend::Kqueue)->isAvailable());
        $this->assertFalse((Backend::EventPorts)->isAvailable());
        $this->assertFalse((Backend::WSAPoll)->isAvailable());
    }

    public function testBackendGetAvailableBackends()
    {
        $available = Backend::getAvailableBackends();
        $this->assertContains((Backend::Poll), $available);
        $this->assertNotContains((Backend::Auto), $available);
        $this->assertNotContains((Backend::Epoll), $available);
    }

    public function testBackendSupportsEdgeTriggering()
    {
        $this->assertFalse((Backend::Auto)->supportsEdgeTriggering());
        $this->assertFalse((Backend::Poll)->supportsEdgeTriggering());
    }

    public function testEventEnumCases()
    {
        $this->assertTrue(enum_exists(Event::class));

        $names = array_column(Event::cases(), 'name');
        $this->assertContains('Read', $names);
        $this->assertContains('Write', $names);
        $this->assertContains('Error', $names);
        $this->assertContains('HangUp', $names);
        $this->assertContains('ReadHangUp', $names);
        $this->assertContains('OneShot', $names);
        $this->assertContains('EdgeTriggered', $names);
    }

    public function testHandleIsInterface()
    {
        $this->assertTrue(interface_exists(Handle::class));
    }

    public function testStreamPollHandleImplementsHandle()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $this->assertInstanceOf(Handle::class, $handle);
        $this->assertSame($stream, $handle->getStream());
        $this->assertTrue($handle->isValid());
        fclose($stream);
        $this->assertFalse($handle->isValid());
    }

    public function testStreamPollHandleRejectsNonStream()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('StreamPollHandle::__construct(): Argument #1 ($stream) must be an open stream resource');
        new \StreamPollHandle('not a stream');
    }

    public function testStreamPollHandleRejectsClosedResource()
    {
        $stream = fopen('php://temp', 'r+');
        fclose($stream);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('StreamPollHandle::__construct(): Argument #1 ($stream) must be an open stream resource');
        new \StreamPollHandle($stream);
    }

    public function testStreamPollHandleIsValidFalseAtEof()
    {
        $stream = tmpfile();
        fwrite($stream, 'x');
        rewind($stream);
        $handle = new \StreamPollHandle($stream);
        $this->assertTrue($handle->isValid());

        fread($stream, 8192);
        $this->assertTrue(feof($stream));
        $this->assertFalse($handle->isValid());

        fclose($stream);
    }

    public function testExceptionHierarchy()
    {
        $this->assertTrue(class_exists(IoException::class));
        $this->assertTrue(class_exists(PollException::class));
        $this->assertTrue(class_exists(FailedPollOperationException::class));
        $this->assertTrue(class_exists(BackendUnavailableException::class));
        $this->assertTrue(class_exists(InactiveWatcherException::class));
        $this->assertTrue(class_exists(HandleAlreadyWatchedException::class));
        $this->assertTrue(class_exists(InvalidHandleException::class));

        $this->assertTrue(is_subclass_of(PollException::class, IoException::class));
        $this->assertTrue(is_subclass_of(FailedPollOperationException::class, PollException::class));
        $this->assertTrue(is_subclass_of(BackendUnavailableException::class, PollException::class));
        $this->assertTrue(is_subclass_of(InactiveWatcherException::class, PollException::class));
        $this->assertTrue(is_subclass_of(HandleAlreadyWatchedException::class, PollException::class));
        $this->assertTrue(is_subclass_of(InvalidHandleException::class, PollException::class));

        $this->assertTrue(is_subclass_of(IoException::class, \Exception::class));
    }

    public function testErrorConstantsOnFailedPollOperationException()
    {
        $constants = [
            'ERROR_NONE', 'ERROR_SYSTEM', 'ERROR_NOMEM', 'ERROR_INVALID',
            'ERROR_EXISTS', 'ERROR_NOTFOUND', 'ERROR_TIMEOUT', 'ERROR_INTERRUPTED',
            'ERROR_PERMISSION', 'ERROR_TOOBIG', 'ERROR_AGAIN', 'ERROR_NOSUPPORT',
        ];

        foreach ($constants as $const) {
            $this->assertTrue(
                \defined(FailedPollOperationException::class.'::'.$const),
                "FailedPollOperationException::$const is defined"
            );
        }
    }

    public function testContextDefaultBackendIsPoll()
    {
        $context = new Context();
        $this->assertSame(Backend::Poll, $context->getBackend());
    }

    public function testContextExplicitPollBackend()
    {
        $context = new Context(Backend::Poll);
        $this->assertSame(Backend::Poll, $context->getBackend());
    }

    public function testContextUnavailableBackendThrows()
    {
        $this->expectException(BackendUnavailableException::class);
        $this->expectExceptionMessage('Backend Epoll not available');
        new Context(Backend::Epoll);
    }

    public function testContextAddReturnsWatcher()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();

        $watcher = $context->add($handle, [Event::Read], 'user data');

        $this->assertInstanceOf(Watcher::class, $watcher);
        $this->assertSame($handle, $watcher->getHandle());
        $this->assertSame([Event::Read], $watcher->getWatchedEvents());
        $this->assertSame('user data', $watcher->getData());
        $this->assertTrue($watcher->isActive());
        $this->assertSame([], $watcher->getTriggeredEvents());

        fclose($stream);
    }

    public function testContextAddNormalizesWatchedEvents()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();

        $watcher = $context->add($handle, [Event::OneShot, Event::Read, Event::Read]);

        $this->assertSame([Event::Read, Event::OneShot], $watcher->getWatchedEvents());

        fclose($stream);
    }

    public function testContextAddDuplicateHandleThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $context->add($handle, [Event::Read]);

        $this->expectException(HandleAlreadyWatchedException::class);
        $this->expectExceptionMessage('Handle already added');
        try {
            $context->add($handle, [Event::Write]);
        } finally {
            fclose($stream);
        }
    }

    public function testContextAddClosedStreamThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        fclose($stream);
        $context = new Context();

        $this->expectException(InvalidHandleException::class);
        $this->expectExceptionMessage('Invalid handle for polling');
        $context->add($handle, [Event::Read]);
    }

    public function testContextAddMemoryStreamThrows()
    {
        $stream = fopen('php://memory', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();

        $this->expectException(InvalidHandleException::class);
        $this->expectExceptionMessage('Invalid handle for polling');
        try {
            $context->add($handle, [Event::Read]);
        } finally {
            fclose($stream);
        }
    }

    public function testContextAddEdgeTriggeredThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();

        try {
            $context->add($handle, [Event::Read, Event::EdgeTriggered]);
            $this->fail('FailedHandleAddException was not thrown');
        } catch (FailedHandleAddException $e) {
            $this->assertSame('Failed to add handle', $e->getMessage());
            $this->assertSame(FailedPollOperationException::ERROR_NOSUPPORT, $e->getCode());
        } finally {
            fclose($stream);
        }
    }

    public function testContextAddEmptyEventsThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Io\\Poll\\Context::add(): Argument #2 ($events) must be array of Event enums');
        try {
            $context->add($handle, []);
        } finally {
            fclose($stream);
        }
    }

    public function testContextAddInvalidEventsThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Io\\Poll\\Context::add(): Argument #2 ($events) must be array of Event enums');
        try {
            $context->add($handle, ['nope']);
        } finally {
            fclose($stream);
        }
    }

    public function testWaitNegativeTimeoutThrows()
    {
        $context = new Context();
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Io\\Poll\\Context::wait(): Argument #1 ($timeout) must not be negative');
        $context->wait(Duration::fromSeconds(1)->negate());
    }

    public function testWaitNonPositiveMaxEventsThrows()
    {
        $context = new Context();
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Io\\Poll\\Context::wait(): Argument #2 ($maxEvents) must be greater than 0');
        $context->wait(Duration::fromSeconds(0), 0);
    }

    public function testWaitThrowsWhenInterruptedBySignal()
    {
        if (!\function_exists('pcntl_alarm') || !\function_exists('pcntl_signal')) {
            $this->markTestSkipped('The pcntl extension is required.');
        }

        pcntl_signal(\SIGALRM, static function () {}, false);

        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $context = new Context();
        $context->add(new \StreamPollHandle($r), [Event::Read]);

        pcntl_alarm(1);

        $this->expectException(FailedPollWaitException::class);
        $this->expectExceptionMessage('Poll wait failed');
        try {
            $context->wait(Duration::fromSeconds(10));
        } finally {
            pcntl_alarm(0);
            pcntl_signal_dispatch();
            pcntl_signal(\SIGALRM, \SIG_DFL);
            fclose($r);
            fclose($w);
        }
    }

    public function testWaitNullTimeoutWaitsForReadiness()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        fwrite($w, 'hello');
        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($r), [Event::Read]);

        $result = $context->wait();

        $this->assertSame([$watcher], $result);

        fclose($r);
        fclose($w);
    }

    public function testWaitImmediateTimeoutReturnsEmpty()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $handle = new \StreamPollHandle($r);
        $context = new Context();
        $context->add($handle, [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertSame([], $result);
        fclose($r);
        fclose($w);
    }

    public function testWaitDetectsReadable()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        fwrite($w, 'hello');

        $context = new Context();
        $handle = new \StreamPollHandle($r);
        $watcher = $context->add($handle, [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertCount(1, $result);
        $this->assertSame($watcher, $result[0]);
        $this->assertTrue($watcher->hasTriggered(Event::Read));
        $this->assertSame([Event::Read], $watcher->getTriggeredEvents());

        fclose($r);
        fclose($w);
    }

    public function testWaitDetectsWritable()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        $context = new Context();
        $handle = new \StreamPollHandle($w);
        $watcher = $context->add($handle, [Event::Write]);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertCount(1, $result);
        $this->assertSame($watcher, $result[0]);
        $this->assertTrue($watcher->hasTriggered(Event::Write));

        fclose($r);
        fclose($w);
    }

    public function testWaitBlocksUntilTimeoutWithNoEvents()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $handle = new \StreamPollHandle($r);
        $context = new Context();
        $context->add($handle, [Event::Read]);

        $start = hrtime(true);
        $result = $context->wait(Duration::fromMicroseconds(50000));
        $elapsed = (hrtime(true) - $start) / 1000000;

        $this->assertSame([], $result);
        $this->assertGreaterThanOrEqual(40, $elapsed);

        fclose($r);
        fclose($w);
    }

    public function testWaitDoesNotReturnEarlyOnUnwatchedReadiness()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        fwrite($w, 'pending data');

        $context = new Context();
        $context->add(new \StreamPollHandle($r), [Event::HangUp]);

        $start = hrtime(true);
        $result = $context->wait(Duration::fromMicroseconds(60000));
        $elapsed = (hrtime(true) - $start) / 1000000;

        $this->assertSame([], $result);
        $this->assertGreaterThanOrEqual(50, $elapsed);

        fclose($r);
        fclose($w);
    }

    public function testWaitReportsReadAndHangUpOnClosedSocketPeer()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        $context = new Context();
        $handle = new \StreamPollHandle($r);
        $watcher = $context->add($handle, [Event::Read]);

        fclose($w);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertSame([$watcher], $result);
        $this->assertSame([Event::Read, Event::HangUp], $watcher->getTriggeredEvents());

        fclose($r);
    }

    public function testWaitReportsHangUpEvenWhenNotWatched()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($r), [Event::ReadHangUp]);

        fclose($w);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertSame([$watcher], $result);
        $this->assertSame([Event::HangUp], $watcher->getTriggeredEvents());

        fclose($r);
    }

    public function testWaitReportsReadOnRegularFileAtEof()
    {
        $stream = tmpfile();
        fwrite($stream, 'x');
        rewind($stream);
        fread($stream, 8192);
        $this->assertTrue(feof($stream));

        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($stream), [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertSame([$watcher], $result);
        $this->assertTrue($watcher->hasTriggered(Event::Read));
        $this->assertFalse($watcher->hasTriggered(Event::HangUp));

        fclose($stream);
    }

    public function testWaitReportsHangUpOnPipeAtEof()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Pipes are not selectable on Windows.');
        }

        $pipe = popen('echo x', 'r');
        while (!feof($pipe)) {
            fread($pipe, 8192);
        }

        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($pipe), [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertSame([$watcher], $result);
        $this->assertTrue($watcher->hasTriggered(Event::HangUp));
        $this->assertFalse($watcher->hasTriggered(Event::Read));

        pclose($pipe);
    }

    public function testWaitReportsReadForEmptyUdpDatagram()
    {
        $server = stream_socket_server('udp://127.0.0.1:0', $errno, $errstr, \STREAM_SERVER_BIND);
        $client = stream_socket_client('udp://'.stream_socket_get_name($server, false));
        stream_socket_sendto($client, '');

        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($server), [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(1));

        $this->assertSame([$watcher], $result);
        $this->assertTrue($watcher->hasTriggered(Event::Read));
        $this->assertFalse($watcher->hasTriggered(Event::HangUp));

        fclose($client);
        fclose($server);
    }

    public function testWaitReportsReadOnReadableNonSocketStream()
    {
        $stream = tmpfile();
        fwrite($stream, 'data');
        rewind($stream);
        $this->assertFalse(feof($stream));

        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($stream), [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(0));

        $this->assertSame([$watcher], $result);
        $this->assertTrue($watcher->hasTriggered(Event::Read));
        $this->assertFalse($watcher->hasTriggered(Event::HangUp));

        fclose($stream);
    }

    public function testWaitRetainsTriggeredEventsWhenNotRetriggered()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        fwrite($w, 'hello');

        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($r), [Event::Read]);

        $context->wait(Duration::fromSeconds(0));
        $this->assertSame([Event::Read], $watcher->getTriggeredEvents());

        fread($r, 5);

        $result = $context->wait(Duration::fromSeconds(0));
        $this->assertSame([], $result);
        $this->assertSame([Event::Read], $watcher->getTriggeredEvents());

        fclose($r);
        fclose($w);
    }

    public function testWaitMaxEventsLimitsReturn()
    {
        [$r1, $w1] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        [$r2, $w2] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        fwrite($w1, 'a');
        fwrite($w2, 'b');

        $context = new Context();
        $context->add(new \StreamPollHandle($r1), [Event::Read]);
        $context->add(new \StreamPollHandle($r2), [Event::Read]);

        $result = $context->wait(Duration::fromSeconds(0), 1);
        $this->assertCount(1, $result);

        fclose($r1);
        fclose($w1);
        fclose($r2);
        fclose($w2);
    }

    public function testWaitReturnsImmediatelyWhenAllHandlesClosed()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($r), [Event::Read]);

        fclose($r);
        fclose($w);

        $start = hrtime(true);
        $result = $context->wait(Duration::fromSeconds(5));
        $elapsed = (hrtime(true) - $start) / 1000000;

        $this->assertSame([], $result);
        $this->assertLessThan(1000, $elapsed);
        $this->assertTrue($watcher->isActive());

        $start = hrtime(true);
        $result = $context->wait(Duration::fromMicroseconds(50000));
        $elapsed = (hrtime(true) - $start) / 1000000;

        $this->assertSame([], $result);
        $this->assertGreaterThanOrEqual(40, $elapsed);
    }

    public function testWaitOneShotKeepsWatcherActive()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        fwrite($w, 'x');

        $context = new Context();
        $handle = new \StreamPollHandle($r);
        $watcher = $context->add($handle, [Event::Read, Event::OneShot]);

        $result = $context->wait(Duration::fromSeconds(0));
        $this->assertCount(1, $result);
        $this->assertTrue($watcher->isActive());

        $result = $context->wait(Duration::fromSeconds(0));
        $this->assertSame([], $result);

        try {
            $watcher->modifyEvents([Event::Read]);
            $this->fail('FailedWatcherModificationException was not thrown');
        } catch (FailedWatcherModificationException $e) {
            $this->assertSame('Failed to modify watcher in polling system', $e->getMessage());
            $this->assertSame(FailedPollOperationException::ERROR_NOTFOUND, $e->getCode());
        }

        $watcher->remove();
        $this->assertFalse($watcher->isActive());

        $watcher2 = $context->add($handle, [Event::Read]);
        $this->assertTrue($watcher2->isActive());

        fclose($r);
        fclose($w);
    }

    public function testWatcherRemove()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);

        $this->assertTrue($watcher->isActive());
        $watcher->remove();
        $this->assertFalse($watcher->isActive());

        $watcher2 = $context->add($handle, [Event::Read]);
        $this->assertTrue($watcher2->isActive());

        fclose($stream);
    }

    public function testWatcherRemoveSucceedsOnClosedStream()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $context = new Context();
        $watcher = $context->add(new \StreamPollHandle($r), [Event::Read]);

        fclose($r);
        fclose($w);

        $watcher->remove();
        $this->assertFalse($watcher->isActive());
    }

    public function testWatcherRemoveTwiceThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);
        $watcher->remove();

        $this->expectException(InactiveWatcherException::class);
        $this->expectExceptionMessage('Cannot remove inactive watcher');
        try {
            $watcher->remove();
        } finally {
            fclose($stream);
        }
    }

    public function testWatcherModifyEvents()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);

        $watcher->modifyEvents([Event::Write]);
        $this->assertSame([Event::Write], $watcher->getWatchedEvents());

        fclose($stream);
    }

    public function testWatcherModifyEventsRejectsEdgeTriggered()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);

        try {
            $watcher->modifyEvents([Event::Read, Event::EdgeTriggered]);
            $this->fail('FailedWatcherModificationException was not thrown');
        } catch (FailedWatcherModificationException $e) {
            $this->assertSame('Failed to modify watcher in polling system', $e->getMessage());
            $this->assertSame(FailedPollOperationException::ERROR_NOSUPPORT, $e->getCode());
        } finally {
            fclose($stream);
        }

        $this->assertSame([Event::Read], $watcher->getWatchedEvents());
    }

    public function testWatcherModifyEventsOnClosedStreamThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);

        fclose($stream);

        $this->expectException(InvalidHandleException::class);
        $this->expectExceptionMessage('Invalid handle for polling');
        $watcher->modifyEvents([Event::Write]);
    }

    public function testWatcherModifyData()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read], 'old');

        $watcher->modifyData('new');
        $this->assertSame('new', $watcher->getData());
        $this->assertSame([Event::Read], $watcher->getWatchedEvents());

        fclose($stream);
    }

    public function testWatcherModifyBoth()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read], 'old');

        $watcher->modify([Event::Write], 'new');
        $this->assertSame([Event::Write], $watcher->getWatchedEvents());
        $this->assertSame('new', $watcher->getData());

        fclose($stream);
    }

    public function testWatcherModifyPreservesDataWhenOmitted()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read], 'payload');

        $watcher->modify([Event::Write]);
        $this->assertSame('payload', $watcher->getData());

        $watcher->modify([Event::Read], null);
        $this->assertNull($watcher->getData());

        fclose($stream);
    }

    public function testWatcherModifyAfterRemoveThrows()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);
        $watcher->remove();

        $this->expectException(InactiveWatcherException::class);
        $this->expectExceptionMessage('Cannot modify inactive watcher');
        try {
            $watcher->modifyEvents([Event::Write]);
        } finally {
            fclose($stream);
        }
    }

    public function testWatcherInactiveAfterContextDestroyed()
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);

        $this->assertTrue($watcher->isActive());
        unset($context);
        $this->assertFalse($watcher->isActive());

        $this->expectException(InactiveWatcherException::class);
        $this->expectExceptionMessage('Cannot remove inactive watcher');
        try {
            $watcher->remove();
        } finally {
            fclose($stream);
        }
    }

    /**
     * @dataProvider provideInvalidModifyEvents
     */
    public function testWatcherModifyRejectsInvalidEvents(string $method, array $events)
    {
        $stream = fopen('php://temp', 'r+');
        $handle = new \StreamPollHandle($stream);
        $context = new Context();
        $watcher = $context->add($handle, [Event::Read]);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(\sprintf('Io\\Poll\\Watcher::%s(): Argument #1 ($events) must be array of Event enums', $method));
        try {
            $watcher->$method($events);
        } finally {
            fclose($stream);
        }
    }

    public static function provideInvalidModifyEvents(): array
    {
        return [
            'modify empty' => ['modify', []],
            'modify invalid' => ['modify', ['nope']],
            'modifyEvents empty' => ['modifyEvents', []],
            'modifyEvents invalid' => ['modifyEvents', ['nope']],
        ];
    }

    public function testWatcherConstructorIsPrivate()
    {
        $ref = new \ReflectionMethod(Watcher::class, '__construct');
        $this->assertTrue($ref->isPrivate());
    }

    /**
     * @dataProvider provideNotSerializable
     */
    public function testNotSerializable(string $class, \Closure $factory)
    {
        $instance = $factory();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of '$class' is not allowed");
        serialize($instance);
    }

    public static function provideNotSerializable(): array
    {
        return [
            'Context' => ['Io\\Poll\\Context', static function () {
                return new Context();
            }],
            'StreamPollHandle' => ['StreamPollHandle', static function () {
                return new \StreamPollHandle(fopen('php://temp', 'r+'));
            }],
            'Watcher' => ['Io\\Poll\\Watcher', static function () {
                $ctx = new Context();

                return $ctx->add(new \StreamPollHandle(fopen('php://temp', 'r+')), [Event::Read]);
            }],
        ];
    }

    public function testTimeoutMicroOverflowIntoSeconds()
    {
        [$r, $w] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $handle = new \StreamPollHandle($r);
        $context = new Context();
        $context->add($handle, [Event::Read]);

        $start = hrtime(true);
        $context->wait(Duration::fromMicroseconds(1100000));
        $elapsed = (hrtime(true) - $start) / 1000000;

        $this->assertGreaterThanOrEqual(1000, $elapsed);

        fclose($r);
        fclose($w);
    }
}
