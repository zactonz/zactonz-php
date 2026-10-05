<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use LogicException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Zactonz\Exception\TimeoutException;
use Zactonz\Http\Request;
use Zactonz\Testing\FakeTransport;

final class FakeTransportTest extends BaseTestCase
{
    public function testAnswersInOrderAndRecordsRequests(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => 200]), FakeTransport::file('bytes', 'image/png'));
        $first     = new Request('GET', 'https://api.zactonz.com/a/');
        $second    = new Request('GET', 'https://api.zactonz.com/b/');

        self::assertSame('application/json', $transport->send($first)->contentType());
        self::assertSame('bytes', $transport->send($second)->body);
        self::assertSame([$first, $second], $transport->requests());
        self::assertSame($second, $transport->lastRequest());
    }

    public function testThrowsQueuedExceptions(): void
    {
        $transport = new FakeTransport();
        $transport->push(new TimeoutException('too slow'));

        $this->expectException(TimeoutException::class);

        $transport->send(new Request('GET', 'https://api.zactonz.com/a/'));
    }

    public function testFailsLoudlyWhenNothingIsQueued(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('GET https://api.zactonz.com/a/');

        (new FakeTransport())->send(new Request('GET', 'https://api.zactonz.com/a/'));
    }

    public function testLastRequestThrowsBeforeAnyRequest(): void
    {
        $this->expectException(LogicException::class);

        (new FakeTransport())->lastRequest();
    }
}
