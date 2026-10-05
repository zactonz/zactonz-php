<?php

declare(strict_types=1);

namespace Zactonz\Tests\Live;

use PHPUnit\Framework\TestCase;
use Zactonz\Client;
use Zactonz\Exception\AuthenticationException;
use Zactonz\Exception\InvalidRequestException;
use Zactonz\Exception\PermissionException;
use Zactonz\SignedUrl;

/**
 * Calls the real API. Run with ZACTONZ_API_KEYS set; products without a key
 * are skipped. ZACTONZ_BASE_URL points the suite at another host, and
 * ZACTONZ_OG_KEY_ID with ZACTONZ_OG_SECRET enables the signed URL check.
 */
final class LiveTest extends TestCase
{
    private Client $zactonz;

    protected function setUp(): void
    {
        if (!getenv(Client::KEYS_VARIABLE)) {
            self::markTestSkipped('Set ' . Client::KEYS_VARIABLE . ' to run the live tests');
        }
        $this->zactonz = Client::fromEnvironment(baseUrl: self::baseUrl());
    }

    private static function baseUrl(): string
    {
        return getenv('ZACTONZ_BASE_URL') ?: Client::BASE_URL;
    }

    /**
     * @return array{int, int}
     */
    private static function dimensions(string $bytes): array
    {
        $size = getimagesizefromstring($bytes);
        self::assertIsArray($size);

        return [$size[0], $size[1]];
    }

    private function requireKey(string ...$products): void
    {
        foreach ($products as $product) {
            if (!$this->zactonz->hasKeyFor($product)) {
                self::markTestSkipped('No key for the ' . $product . ' product');
            }
        }
    }

    public function testKeyStatusForEveryConfiguredProduct(): void
    {
        $checked = 0;
        foreach (['qr', 'screen', 'gdrive', 'translator', 'mverifier', 'barcode', 'domain', 'email', 'unfurl', 'markdown', 'og', 'image'] as $product) {
            if ($this->zactonz->hasKeyFor($product)) {
                $status = $this->zactonz->keyStatus($product);
                self::assertSame($product, $status->get('key.product'));
                self::assertIsInt($status->get('quota.day.remaining'));
                $checked++;
            }
        }
        self::assertGreaterThan(0, $checked);
    }

    public function testQrEncode(): void
    {
        $this->requireKey('qr');

        $png = $this->zactonz->qr()->encode(content: 'https://zactonz.com/sdk', size: 6);
        self::assertStringContainsString('/qr/enc/i/', $png['qr']);
        self::assertSame(6, $png['size']);
        self::assertIsInt($png->rateLimit->remaining);

        self::assertStringEndsWith('.svg', $this->zactonz->qr()->encode(content: 'hello', format: 'svg')['qr']);
        self::assertMatchesRegularExpression('/^[01\n]+$/', $this->zactonz->qr()->encode(content: 'hello', format: 'text')['text']);
    }

    public function testQrEncodeFile(): void
    {
        $this->requireKey('qr');

        $png = $this->zactonz->qr()->encodeFile(content: 'https://zactonz.com/sdk', size: 6);
        self::assertSame('image/png', $png->contentType);
        self::assertStringStartsWith("\x89PNG", $png->bytes);

        self::assertStringContainsString('<svg', $this->zactonz->qr()->encodeFile(content: 'hello', format: 'svg')->bytes);
    }

    public function testQrDecodeFromBytesAndFromLink(): void
    {
        $this->requireKey('qr');

        $image = $this->zactonz->qr()->encodeFile(content: 'decode-me-from-bytes', size: 6);
        self::assertSame('decode-me-from-bytes', $this->zactonz->qr()->decode(image: $image->base64(), format: 'base64')->data);

        if (self::baseUrl() === Client::BASE_URL) {
            $link = $this->zactonz->qr()->encode(content: 'decode-me-from-a-link')['qr'];
            self::assertSame('decode-me-from-a-link', $this->zactonz->qr()->decode(image: $link)->data);
        }
    }

    public function testEndpointFailureInsideHttp200IsThrown(): void
    {
        $this->requireKey('qr');

        $this->expectException(InvalidRequestException::class);

        $this->zactonz->qr()->encode(content: '');
    }

    public function testBarcode(): void
    {
        $this->requireKey('barcode');

        $barcode = $this->zactonz->barcode()->encode(content: 'ZCTZ-0042', scale: 3);
        self::assertSame('code128', $barcode['type']);
        self::assertSame([$barcode['width'], 60], self::dimensions($this->zactonz->download($barcode['barcode'])->bytes));

        self::assertSame('svg', $this->zactonz->barcode()->encodeFile(content: '590123412345', type: 'ean13', format: 'svg')->extension());
    }

    public function testBarcodeRejectsBadCheckDigit(): void
    {
        $this->requireKey('barcode');

        try {
            $this->zactonz->barcode()->encode(content: '5901234123450', type: 'ean13');
            self::fail('Expected InvalidRequestException');
        } catch (InvalidRequestException $exception) {
            self::assertSame(400, $exception->status);
            self::assertStringContainsString('check digit', $exception->getMessage());
            self::assertNotNull($exception->requestId());
        }
    }

    public function testKeyOfAnotherProductIsRefused(): void
    {
        $this->requireKey('qr', 'barcode');
        preg_match('/zk_qr_[A-Za-z0-9]+/', (string) getenv(Client::KEYS_VARIABLE), $match);
        $misfiled = new Client(['barcode' => $match[0] ?? ''], maxRetries: 0, baseUrl: self::baseUrl());

        $this->expectException(PermissionException::class);

        $misfiled->barcode()->encode(content: 'x');
    }

    public function testUnknownKeyIsRejected(): void
    {
        $unknown = new Client('zk_barcode_' . str_repeat('x', 40), maxRetries: 0, baseUrl: self::baseUrl());

        $this->expectException(AuthenticationException::class);

        $unknown->barcode()->encode(content: 'x');
    }

    public function testDomain(): void
    {
        $this->requireKey('domain');

        $dns = $this->zactonz->domain()->dns(name: 'zactonz.com', type: ['A', 'MX']);
        self::assertNotEmpty($dns->get('records.A.0.address'));
        self::assertArrayHasKey('MX', $dns['records']);

        $ssl = $this->zactonz->domain()->ssl(host: 'developers.zactonz.com');
        self::assertTrue($ssl['valid']);
        self::assertGreaterThan(0, $ssl['days_remaining']);

        self::assertSame('zactonz.com', $this->zactonz->domain()->whois(domain: 'www.zactonz.com')['domain']);
    }

    public function testEmailHealth(): void
    {
        $this->requireKey('email');

        $health = $this->zactonz->email()->health(domain: 'zactonz.com', selectors: ['default', 'google']);

        self::assertIsInt($health['score']);
        self::assertNotEmpty($health['mx']);
    }

    public function testEmailVerify(): void
    {
        $this->requireKey('mverifier');

        $verified = $this->zactonz->email()->verify(emails: ['info@zactonz.com']);

        self::assertContains($verified['mails']['info@zactonz.com'], ['valid', 'invalid']);
    }

    public function testLinkPreview(): void
    {
        $this->requireKey('unfurl');

        $preview = $this->zactonz->links()->preview(url: 'https://developers.zactonz.com/apis/');

        self::assertStringContainsString('API', $preview['title']);
        self::assertStringStartsWith('https://developers.zactonz.com/', $preview['canonical']);
    }

    public function testMarkdown(): void
    {
        $this->requireKey('markdown');

        $page = $this->zactonz->markdown()->fromUrl(url: 'https://developers.zactonz.com/apis/authentication/', maxChars: 2000);
        self::assertStringContainsString('Bearer', $page['markdown']);
        self::assertLessThanOrEqual(2000, $page['length']);

        $own = $this->zactonz->markdown()->fromHtml(html: '<html><body><article><h1>Invoice 1042</h1><p>Total due: <strong>120 USD</strong>.</p></article></body></html>', mode: 'full');
        self::assertStringContainsString('**120 USD**', $own['markdown']);
    }

    public function testImage(): void
    {
        $this->requireKey('image');
        $source = sys_get_temp_dir() . '/zactonz-' . bin2hex(random_bytes(6)) . '.png';
        $canvas = imagecreatetruecolor(320, 200);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 103, 0));
        imagepng($canvas, $source);

        try {
            $info = $this->zactonz->image()->info(file: $source);
            self::assertSame(320, $info->get('source.width'));
            self::assertSame('png', $info->get('source.format'));

            $webp = $this->zactonz->image()->convertFile(file: $source, format: 'webp', width: 160);
            self::assertSame('image/webp', $webp->contentType);
            self::assertSame('160', $webp->headers['x-image-width'] ?? null);

            $hosted = $this->zactonz->image()->convert(file: $source, format: 'jpeg', width: 100, grayscale: true);
            self::assertSame(100, $hosted->get('output.width'));
            self::assertSame('image/jpeg', $this->zactonz->download($hosted['image'])->contentType);
        } finally {
            unlink($source);
        }
    }

    public function testScreenshot(): void
    {
        $this->requireKey('screen');

        $shot = $this->zactonz->screenshot()->capture(url: 'https://zactonz.com', width: 800, height: 600, format: 'png', delay: 1);
        self::assertSame(800, $shot['width']);
        self::assertIsString($shot->data);
        self::assertStringEndsWith('.png', $shot->data);

        $file = $this->zactonz->screenshot()->captureFile(url: 'https://zactonz.com', width: 640, height: 480, format: 'jpeg', quality: 70, delay: 1);
        self::assertSame('image/jpeg', $file->contentType);
        self::assertSame(640, self::dimensions($file->bytes)[0]);

        $pdf = $this->zactonz->screenshot()->captureHtml(html: '<html><body><h1>Invoice 1042</h1></body></html>', format: 'pdf');
        self::assertStringStartsWith('%PDF', $this->zactonz->download((string) $pdf->data)->bytes);
    }

    public function testOgImage(): void
    {
        $this->requireKey('og');

        $card = $this->zactonz->og()->generateFile(title: 'Ship faster with the Zactonz APIs', theme: 'dark');
        self::assertSame('image/png', $card->contentType);
        self::assertSame([1200, 630], self::dimensions($card->bytes));

        $hosted = $this->zactonz->og()->generate(title: 'Hosted card', subtitle: 'From the live suite', format: 'webp');
        self::assertSame('webp', $hosted['format']);
        self::assertSame('image/webp', $this->zactonz->download($hosted['image'])->contentType);
    }

    public function testSignedOgUrl(): void
    {
        if (!getenv('ZACTONZ_OG_KEY_ID') || !getenv('ZACTONZ_OG_SECRET')) {
            self::markTestSkipped('Set ZACTONZ_OG_KEY_ID and ZACTONZ_OG_SECRET to check signed URLs');
        }
        $url = SignedUrl::og((int) getenv('ZACTONZ_OG_KEY_ID'), (string) getenv('ZACTONZ_OG_SECRET'), ['title' => 'Signed & sealed', 'theme' => 'dark'], self::baseUrl());

        self::assertSame('image/png', $this->zactonz->download($url)->contentType);

        $this->expectException(\Zactonz\Exception\ApiException::class);

        $this->zactonz->download(str_replace('Signed', 'Forged', $url));
    }

    public function testDriveDirectLink(): void
    {
        $this->requireKey('gdrive');

        $link = $this->zactonz->drive()->directLink(url: 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view');

        self::assertStringContainsString('1AbCdEfGhIjKlMnOpQrStUvWxYz012345', (string) $link->data);
    }

    public function testTranslate(): void
    {
        $this->requireKey('translator');

        $translated = $this->zactonz->translator()->translate(text: 'Good morning', to: 'es');

        self::assertSame('es', $translated['to']);
        self::assertNotEmpty($translated['text']);
    }
}
