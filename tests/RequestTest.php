<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use InvalidArgumentException;
use Zactonz\Exception\MissingKeyException;
use Zactonz\Testing\FakeTransport;

/**
 * How method calls become HTTP requests.
 */
final class RequestTest extends TestCase
{
    public function testGetPutsArgumentsInQueryString(): void
    {
        $transport = new FakeTransport(self::ok());

        $this->client($transport)->links()->preview(url: 'https://zactonz.com/a b?x=1&y=2', render: true, fresh: false);

        $request = $transport->lastRequest();
        self::assertSame('GET', $request->method);
        self::assertSame('https://api.zactonz.com/unfurl/?url=https%3A%2F%2Fzactonz.com%2Fa%20b%3Fx%3D1%26y%3D2&render=1&fresh=0', $request->url);
        self::assertSame([], $request->fields);
    }

    public function testPostPutsArgumentsInFormFields(): void
    {
        $transport = new FakeTransport(self::ok());

        $this->client($transport)->screenshot()->captureHtml(html: '<h1>A & B</h1>', quality: 80);

        $request = $transport->lastRequest();
        self::assertSame('POST', $request->method);
        self::assertSame('https://api.zactonz.com/screen/html/', $request->url);
        self::assertSame(['html' => '<h1>A & B</h1>', 'quality' => '80'], $request->fields);
    }

    public function testOmitsArgumentsThatWereNotGiven(): void
    {
        $transport = new FakeTransport(self::ok());

        $this->client($transport)->domain()->ssl(host: 'zactonz.com');

        self::assertSame('https://api.zactonz.com/domain/ssl/?host=zactonz.com', $transport->lastRequest()->url);
    }

    public function testJoinsListArgumentsWithCommas(): void
    {
        $transport = new FakeTransport(self::ok());

        $this->client($transport)->email()->verify(emails: ['jane@example.com', 'sales@zactonz.com']);

        self::assertSame(['emails' => 'jane@example.com,sales@zactonz.com'], $transport->lastRequest()->fields);
    }

    public function testRejectsNestedListArguments(): void
    {
        $transport = new FakeTransport();
        $nested    = self::untyped([['A']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"type"');

        $this->client($transport)->domain()->dns(name: 'zactonz.com', type: $nested);
    }

    /**
     * Hands a value past the static type check, the way calling code without
     * type analysis could.
     */
    private static function untyped(mixed $value): mixed
    {
        return $value;
    }

    public function testSendsUploadAsFileNotField(): void
    {
        $transport = new FakeTransport(self::ok());

        $this->client($transport)->image()->convert(file: __FILE__, format: 'webp', width: 800, grayscale: true);

        $request = $transport->lastRequest();
        self::assertSame(['file' => __FILE__], $request->files);
        self::assertSame(['format' => 'webp', 'width' => '800', 'grayscale' => '1', 'resp' => 'json'], $request->fields);
    }

    public function testRejectsUploadOfMissingFileBeforeSending(): void
    {
        $transport = new FakeTransport();

        try {
            $this->client($transport)->image()->info(file: __DIR__ . '/does-not-exist.png');
            self::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('does-not-exist.png', $exception->getMessage());
            self::assertSame([], $transport->requests());
        }
    }

    public function testRejectsImageCallWithNeitherFileNorUrl(): void
    {
        $transport = new FakeTransport();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file, url');

        $this->client($transport)->image()->convert(format: 'webp');
    }

    public function testSendsTheKeyOfTheProductBeingCalled(): void
    {
        $transport = new FakeTransport(self::ok(), self::ok());
        $client    = $this->client($transport);

        $client->qr()->encode(content: 'x');
        $client->barcode()->encode(content: 'x');

        self::assertSame('Bearer zk_qr_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $transport->requests()[0]->headers['Authorization']);
        self::assertSame('Bearer zk_barcode_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $transport->requests()[1]->headers['Authorization']);
    }

    public function testSendsNothingWhenProductHasNoKey(): void
    {
        $transport = new FakeTransport();
        $client    = new \Zactonz\Client('zk_qr_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', transport: $transport);

        try {
            $client->screenshot()->capture(url: 'https://zactonz.com');
            self::fail('Expected MissingKeyException');
        } catch (MissingKeyException $exception) {
            self::assertStringContainsString('"screen"', $exception->getMessage());
            self::assertSame([], $transport->requests());
        }
    }

    public function testIdentifiesTheLibraryAndPassesTheTimeout(): void
    {
        $transport = new FakeTransport(self::ok());

        $this->client($transport)->drive()->directLink(url: 'https://drive.google.com/file/d/abc/view');

        $request = $transport->lastRequest();
        self::assertMatchesRegularExpression('#^zactonz-php/\d+\.\d+\.\d+ PHP/\d+\.\d+$#', $request->headers['User-Agent']);
        self::assertSame('application/json', $request->headers['Accept']);
        self::assertSame(30.0, $request->timeout);
    }

    public function testDownloadSendsNoKey(): void
    {
        $transport = new FakeTransport(FakeTransport::file('%PDF-1.7', 'application/pdf'));

        $file = $this->client($transport)->download('https://api.zactonz.com/screen/url/s/a.pdf');

        self::assertSame('pdf', $file->extension());
        self::assertArrayNotHasKey('Authorization', $transport->lastRequest()->headers);
    }

    public function testDownloadRejectsNonWebUrls(): void
    {
        $transport = new FakeTransport();

        foreach (['file:///etc/hosts', 'ftp://example.com/a.png', '/etc/hosts', 'php://filter/resource=x'] as $url) {
            try {
                $this->client($transport)->download($url);
                self::fail('Expected InvalidArgumentException for ' . $url);
            } catch (InvalidArgumentException) {
                self::assertSame([], $transport->requests());
            }
        }
    }
}
