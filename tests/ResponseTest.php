<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Zactonz\Exception\ApiException;
use Zactonz\Exception\AuthenticationException;
use Zactonz\Exception\InvalidRequestException;
use Zactonz\Exception\PermissionException;
use Zactonz\Exception\RateLimitException;
use Zactonz\Exception\ServerException;
use Zactonz\Exception\UnexpectedResponseException;
use Zactonz\Http\Response;
use Zactonz\Testing\FakeTransport;

/**
 * How HTTP responses become results and exceptions.
 */
final class ResponseTest extends TestCase
{
    public function testReturnsResultWithRateLimitAndRequestId(): void
    {
        $transport = new FakeTransport(FakeTransport::json(
            ['status' => 200, 'data' => ['qr' => 'https://api.zactonz.com/qr/enc/i/x.png', 'size' => 6]],
            200,
            ['X-RateLimit-Limit' => '300', 'X-RateLimit-Remaining' => '287', 'X-Quota-Day-Remaining' => '24310', 'X-Request-Id' => 'abc123'],
        ));

        $result = $this->client($transport)->qr()->encode(content: 'https://zactonz.com', size: 6);

        self::assertSame(200, $result->status);
        self::assertSame('https://api.zactonz.com/qr/enc/i/x.png', $result['qr']);
        self::assertSame(300, $result->rateLimit->limit);
        self::assertSame(287, $result->rateLimit->remaining);
        self::assertSame(24310, $result->rateLimit->dayRemaining);
        self::assertNull($result->rateLimit->monthRemaining);
        self::assertSame('abc123', $result->requestId());
    }

    public function testAcceptsStatusSentAsString(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => '200', 'message' => null, 'mails' => ['a@b.co' => 'valid'], 'ttr' => 1.5]));

        $result = $this->client($transport)->email()->verify(emails: 'a@b.co');

        self::assertSame(200, $result->status);
        self::assertSame('valid', $result['mails']['a@b.co']);
    }

    public function testAcceptsReplyWithoutStatusField(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['data' => ['ok' => true]]));

        self::assertTrue($this->client($transport)->domain()->ssl(host: 'zactonz.com')['ok']);
    }

    public function testThrowsOnFailureReportedInsideHttp200(): void
    {
        $transport = new FakeTransport(new Response(200, ['Content-Type' => 'text/html; charset=UTF-8'], '{"status":400,"data":"Missing content"}'));

        try {
            $this->client($transport)->qr()->encode(content: '');
            self::fail('Expected InvalidRequestException');
        } catch (InvalidRequestException $exception) {
            self::assertSame(400, $exception->status);
            self::assertSame('Missing content', $exception->getMessage());
        }
    }

    public function testEndpointLevel401IsNotAnAuthenticationFailure(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => 401, 'data' => 'The image could not be loaded']));

        $this->expectException(InvalidRequestException::class);

        $this->client($transport)->qr()->decode(image: 'https://example.com/missing.png');
    }

    public function testThrowsWhenStatusFieldIsNotANumber(): void
    {
        foreach (['error', false, null] as $status) {
            $transport = new FakeTransport(FakeTransport::json(['status' => $status, 'message' => 'Something went wrong']));

            try {
                $this->client($transport)->domain()->whois(domain: 'example.com');
                self::fail('Expected UnexpectedResponseException');
            } catch (UnexpectedResponseException $exception) {
                self::assertSame('Something went wrong', $exception->getMessage());
            }
        }
    }

    public function testReadsFailureMessageFromWhicheverFieldCarriesIt(): void
    {
        $transport = new FakeTransport(
            FakeTransport::json(['status' => 400, 'data' => null, 'errors' => ['Invalid target language.', 'Text is empty.']]),
            FakeTransport::json(['status' => '403', 'message' => '', 'reason' => 'Invalid key']),
            FakeTransport::json(['status' => 422]),
        );
        $client = $this->client($transport);

        foreach (['Invalid target language. Text is empty.', 'Invalid key', 'The API answered with status 422'] as $expected) {
            try {
                $client->translator()->translate(text: 'x', to: 'es');
                self::fail('Expected InvalidRequestException');
            } catch (InvalidRequestException $exception) {
                self::assertSame($expected, $exception->getMessage());
            }
        }
    }

    /**
     * @return iterable<string, array{int, class-string<ApiException>}>
     */
    public static function httpFailures(): iterable
    {
        yield 'unknown key' => [401, AuthenticationException::class];
        yield 'expired key' => [406, AuthenticationException::class];
        yield 'key of another product' => [403, PermissionException::class];
        yield 'bad request' => [400, InvalidRequestException::class];
        yield 'not found' => [404, InvalidRequestException::class];
        yield 'too large' => [413, InvalidRequestException::class];
        yield 'unprocessable' => [422, InvalidRequestException::class];
        yield 'server error' => [500, ServerException::class];
        yield 'redirect' => [302, UnexpectedResponseException::class];
    }

    /**
     * @param class-string<ApiException> $class
     */
    #[DataProvider('httpFailures')]
    public function testMapsHttpStatusToException(int $status, string $class): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => $status, 'message' => 'Reason'], $status, ['X-Request-Id' => 'r1']));

        try {
            $this->client($transport, 0)->domain()->whois(domain: 'example.com');
            self::fail('Expected ' . $class);
        } catch (ApiException $exception) {
            self::assertSame($class, $exception::class);
            self::assertSame($status, $exception->status);
            self::assertSame('Reason', $exception->getMessage());
            self::assertSame('r1', $exception->requestId());
            self::assertSame(['status' => $status, 'message' => 'Reason'], $exception->body);
        }
    }

    public function testRateLimitExceptionCarriesRetryAfter(): void
    {
        $transport = new FakeTransport(FakeTransport::json(['status' => 429, 'message' => 'Quota exceeded for this plan'], 429, ['Retry-After' => '41000']));

        try {
            $this->client($transport, 0)->domain()->dns(name: 'zactonz.com');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException $exception) {
            self::assertSame(41000, $exception->retryAfter);
            self::assertSame(429, $exception->status);
        }
    }

    public function testThrowsServerExceptionForNonJsonErrorPage(): void
    {
        $transport = new FakeTransport(new Response(500, ['Content-Type' => 'text/html'], '<h1>Internal Server Error</h1>'));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('HTTP 500');

        $this->client($transport, 0)->domain()->ssl(host: 'zactonz.com');
    }

    public function testThrowsUnexpectedResponseForNonJsonSuccess(): void
    {
        $transport = new FakeTransport(new Response(200, ['Content-Type' => 'text/html'], '<html>maintenance</html>'));

        $this->expectException(UnexpectedResponseException::class);

        $this->client($transport)->domain()->ssl(host: 'zactonz.com');
    }

    public function testFileMethodReturnsBytesAndContentType(): void
    {
        $transport = new FakeTransport(FakeTransport::file("\x89PNG-bytes", 'image/png', ['X-RateLimit-Remaining' => '12']));

        $file = $this->client($transport)->barcode()->encodeFile(content: 'ZCTZ-0042');

        self::assertSame("\x89PNG-bytes", $file->bytes);
        self::assertSame('image/png', $file->contentType);
        self::assertSame(12, $file->rateLimit->remaining);
        self::assertSame('*/*', $transport->lastRequest()->headers['Accept']);
    }

    public function testFileMethodThrowsWhenEndpointAnswersWithJsonError(): void
    {
        $transport = new FakeTransport(new Response(200, ['Content-Type' => 'text/html; charset=UTF-8'], ' {"status":"400","message":"The url is not valid"}'));

        try {
            $this->client($transport)->screenshot()->captureFile(url: 'nope');
            self::fail('Expected InvalidRequestException');
        } catch (InvalidRequestException $exception) {
            self::assertSame('The url is not valid', $exception->getMessage());
        }
    }

    public function testFileMethodThrowsWhenEndpointAnswersWithJsonSuccess(): void
    {
        $transport = new FakeTransport(self::ok(['image' => 'https://example.com/a.png']));

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('where a file was expected');

        $this->client($transport)->og()->generateFile(title: 'Hello');
    }

    public function testFileMethodDoesNotTreatRedirectPageAsFile(): void
    {
        $transport = new FakeTransport(new Response(302, ['Content-Type' => 'text/html', 'Location' => 'https://example.com/'], '<a href="https://example.com/">moved</a>'));

        $this->expectException(UnexpectedResponseException::class);

        $this->client($transport)->qr()->encodeFile(content: 'x');
    }

    public function testDownloadThrowsForNonSuccessStatus(): void
    {
        foreach ([302, 404, 500] as $status) {
            $transport = new FakeTransport(new Response($status, ['Content-Type' => 'text/html'], 'nope'));

            try {
                $this->client($transport, 0)->download('https://api.zactonz.com/f/?p=gone');
                self::fail('Expected UnexpectedResponseException');
            } catch (UnexpectedResponseException $exception) {
                self::assertSame($status, $exception->status);
            }
        }
    }
}
