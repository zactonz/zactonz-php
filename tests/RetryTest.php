<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use Zactonz\Exception\ConnectionException;
use Zactonz\Exception\PermissionException;
use Zactonz\Exception\RateLimitException;
use Zactonz\Exception\ServerException;
use Zactonz\Exception\TimeoutException;
use Zactonz\Http\Response;
use Zactonz\Testing\FakeTransport;

final class RetryTest extends TestCase
{
    private static function throttled(string $retryAfter): Response
    {
        return FakeTransport::json(['status' => 429, 'message' => 'Rate limit exceeded'], 429, ['Retry-After' => $retryAfter]);
    }

    private static function burstBlock(): Response
    {
        return new Response(403, ['Content-Type' => 'text/html; charset=iso-8859-1'], '<html><body>Forbidden</body></html>');
    }

    public function testWaitsForRetryAfterThenSucceeds(): void
    {
        $transport = new FakeTransport(self::throttled('7'), self::ok(['name' => 'zactonz.com']));

        $result = $this->client($transport)->domain()->dns(name: 'zactonz.com');

        self::assertSame('zactonz.com', $result['name']);
        self::assertSame([7], Waits::$seconds);
    }

    public function testRetriesThrottledPostBecauseItWasNotProcessed(): void
    {
        $transport = new FakeTransport(self::throttled('2'), self::ok('https://api.zactonz.com/screen/url/s/a.jpeg'));

        $this->client($transport)->screenshot()->capture(url: 'https://zactonz.com');

        self::assertCount(2, $transport->requests());
    }

    public function testDoesNotWaitLongerThanTheBudget(): void
    {
        $transport = new FakeTransport(self::throttled('41000'));

        try {
            $this->client($transport)->domain()->dns(name: 'zactonz.com');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException) {
            self::assertSame([], Waits::$seconds);
            self::assertCount(1, $transport->requests());
        }
    }

    public function testBudgetCoversAllWaitsOfOneCall(): void
    {
        $transport = new FakeTransport(self::throttled('20'), self::throttled('20'));

        try {
            $this->client($transport, 5)->domain()->dns(name: 'zactonz.com');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException) {
            self::assertSame([20], Waits::$seconds);
        }
    }

    public function testStopsAtMaxRetries(): void
    {
        $transport = new FakeTransport(self::throttled('1'), self::throttled('1'), self::throttled('1'));

        try {
            $this->client($transport, 2)->domain()->dns(name: 'zactonz.com');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException) {
            self::assertCount(3, $transport->requests());
            self::assertSame([1, 1], Waits::$seconds);
        }
    }

    public function testMaxRetriesZeroDisablesRetries(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => 503, 'message' => 'Renderer busy'], 503));

        try {
            $this->client($transport, 0)->domain()->ssl(host: 'zactonz.com');
            self::fail('Expected ServerException');
        } catch (ServerException) {
            self::assertCount(1, $transport->requests());
            self::assertSame([], Waits::$seconds);
        }
    }

    public function testRetriesGatewayFailureForGetWithBackoff(): void
    {
        $transport = new FakeTransport(
            new Response(504, ['Content-Type' => 'text/html'], 'Gateway Timeout'),
            new Response(502, ['Content-Type' => 'text/html'], 'Bad Gateway'),
            self::ok(['valid' => true]),
        );

        self::assertTrue($this->client($transport)->domain()->ssl(host: 'zactonz.com')['valid']);
        self::assertSame([1, 2], Waits::$seconds);
    }

    public function testDoesNotRetryGatewayFailureForPost(): void
    {
        $transport = new FakeTransport(new Response(504, ['Content-Type' => 'text/html'], 'Gateway Timeout'));

        try {
            $this->client($transport)->screenshot()->capture(url: 'https://zactonz.com');
            self::fail('Expected ServerException');
        } catch (ServerException) {
            self::assertCount(1, $transport->requests());
        }
    }

    public function testWaitsOutBurstBlockOnce(): void
    {
        $transport = new FakeTransport(self::burstBlock(), self::ok('decoded text'));

        $result = $this->client($transport)->qr()->decode(image: 'https://example.com/qr.png');

        self::assertSame('decoded text', $result->data);
        self::assertSame([10], Waits::$seconds);
    }

    public function testReportsRepeatedBurstBlockAsRateLimit(): void
    {
        $transport = new FakeTransport(self::burstBlock(), self::burstBlock());

        try {
            $this->client($transport, 5)->qr()->decode(image: 'https://example.com/qr.png');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException $exception) {
            self::assertSame(403, $exception->status);
            self::assertSame(10, $exception->retryAfter);
            self::assertSame([10], Waits::$seconds);
            self::assertCount(2, $transport->requests());
        }
    }

    public function testJson403IsNotTreatedAsBurstBlock(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => 403, 'message' => 'This API key is not licensed for this endpoint'], 403));

        try {
            $this->client($transport)->qr()->decode(image: 'https://example.com/qr.png');
            self::fail('Expected PermissionException');
        } catch (PermissionException) {
            self::assertSame([], Waits::$seconds);
            self::assertCount(1, $transport->requests());
        }
    }

    public function testRetriesWhenConnectionFailsWhateverTheMethod(): void
    {
        $transport = new FakeTransport(new ConnectionException('Could not resolve host'), self::ok('https://api.zactonz.com/screen/url/s/a.jpeg'));

        $this->client($transport)->screenshot()->capture(url: 'https://zactonz.com');

        self::assertCount(2, $transport->requests());
        self::assertSame([1], Waits::$seconds);
    }

    public function testDoesNotRetryAfterTimeout(): void
    {
        $transport = new FakeTransport(new TimeoutException('Operation timed out'));

        try {
            $this->client($transport)->domain()->ssl(host: 'zactonz.com');
            self::fail('Expected TimeoutException');
        } catch (TimeoutException) {
            self::assertCount(1, $transport->requests());
        }
    }

    public function testGivesUpOnConnectionFailureAfterMaxRetries(): void
    {
        $failure   = new ConnectionException('Connection refused');
        $transport = new FakeTransport($failure, $failure, $failure);

        $this->expectException(ConnectionException::class);

        $this->client($transport, 2)->domain()->ssl(host: 'zactonz.com');
    }
}
