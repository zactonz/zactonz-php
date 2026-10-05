<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;
use Zactonz\Exception\ConnectionException;
use Zactonz\Exception\TimeoutException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\CurlTransport;
use Zactonz\Http\Request;

/**
 * Exercises the cURL transport against a real HTTP server on localhost.
 */
final class CurlTransportTest extends BaseTestCase
{
    /**
     * @var resource|null
     */
    private static $server;

    private static string $origin;

    public static function setUpBeforeClass(): void
    {
        $port         = self::freePort();
        self::$origin = 'http://127.0.0.1:' . $port;
        $process      = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/fixtures/server.php'],
            [['file', '/dev/null', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('The fixture server could not be started');
        }
        self::$server = $process;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
            if (is_resource($socket)) {
                fclose($socket);
                return;
            }
            \usleep(100000);
        }
        throw new RuntimeException('The fixture server did not start listening');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        self::$server = null;
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            throw new RuntimeException('No free port could be found');
        }
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        return $port;
    }

    /**
     * @return array<string, mixed>
     */
    private static function echoed(Request $request): array
    {
        return json_decode((new CurlTransport())->send($request)->body, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testSendsGetWithQueryAndHeaders(): void
    {
        $response = (new CurlTransport())->send(new Request('GET', self::$origin . '/echo?a=1&b=two%20words', ['Authorization' => 'Bearer zk_qr_abc', 'Accept' => 'application/json']));
        $echo     = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->statusCode);
        self::assertSame('application/json', $response->contentType());
        self::assertSame('fixture-1', $response->header('X-Request-Id'));
        self::assertSame('GET', $echo['method']);
        self::assertSame(['a' => '1', 'b' => 'two words'], $echo['query']);
        self::assertSame('Bearer zk_qr_abc', $echo['authorization']);
        self::assertSame('application/json', $echo['accept']);
    }

    public function testSendsPostAsUrlEncodedForm(): void
    {
        $echo = self::echoed(new Request('POST', self::$origin . '/echo', [], ['html' => '<h1>A & B</h1>', 'quality' => '80']));

        self::assertSame('POST', $echo['method']);
        self::assertSame(['html' => '<h1>A & B</h1>', 'quality' => '80'], $echo['fields']);
        self::assertSame('application/x-www-form-urlencoded', $echo['content_type']);
    }

    public function testSendsUploadAsMultipart(): void
    {
        $echo = self::echoed(new Request('POST', self::$origin . '/echo', [], ['format' => 'webp'], ['file' => __FILE__]));

        self::assertSame(['format' => 'webp'], $echo['fields']);
        self::assertSame(basename(__FILE__), $echo['files']['file']['name']);
        self::assertSame(filesize(__FILE__), $echo['files']['file']['size']);
        self::assertStringStartsWith('multipart/form-data', $echo['content_type']);
    }

    public function testReturnsBinaryBodyUntouched(): void
    {
        $response = (new CurlTransport())->send(new Request('GET', self::$origin . '/bytes'));

        self::assertSame("\x89PNG\r\n\x1a\n", $response->body);
        self::assertSame('image/png', $response->contentType());
        self::assertSame('2', $response->header('x-image-width'));
    }

    public function testReturnsErrorStatusesInsteadOfThrowing(): void
    {
        $response = (new CurlTransport())->send(new Request('GET', self::$origin . '/status/429'));

        self::assertSame(429, $response->statusCode);
        self::assertFalse($response->isSuccessful());
    }

    public function testDoesNotFollowRedirects(): void
    {
        $response = (new CurlTransport())->send(new Request('GET', self::$origin . '/redirect'));

        self::assertSame(302, $response->statusCode);
        self::assertSame('/echo', $response->header('location'));
    }

    public function testRefusesNonWebProtocols(): void
    {
        $this->expectException(TransportException::class);

        (new CurlTransport())->send(new Request('GET', 'file://' . __FILE__));
    }

    public function testThrowsTimeoutExceptionWhenServerIsSlow(): void
    {
        $this->expectException(TimeoutException::class);

        (new CurlTransport())->send(new Request('GET', self::$origin . '/slow', [], [], [], 0.3));
    }

    public function testTreatsTimeoutBeforeConnectingAsConnectionFailure(): void
    {
        $this->expectException(ConnectionException::class);

        // 10.255.255.1 is not routable, so the connection attempt hangs until the timeout.
        (new CurlTransport())->send(new Request('GET', 'http://10.255.255.1/', [], [], [], 0.3));
    }

    public function testThrowsConnectionExceptionWhenNothingListens(): void
    {
        $this->expectException(ConnectionException::class);

        (new CurlTransport())->send(new Request('GET', 'http://127.0.0.1:' . self::freePort() . '/echo'));
    }
}
