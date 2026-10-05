<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Zactonz\SignedUrl;

final class SignedUrlTest extends BaseTestCase
{
    /**
     * @return array<string, string>
     */
    private static function query(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parsed);
        $query = [];
        foreach ($parsed as $name => $value) {
            $query[(string) $name] = is_scalar($value) ? (string) $value : '';
        }

        return $query;
    }

    public function testSignsSortedRfc3986Query(): void
    {
        $url   = SignedUrl::og(42, 's3cret', ['title' => 'Ship faster & safer', 'theme' => 'dark', 'subtitle' => null]);
        $query = self::query($url);

        self::assertStringStartsWith('https://api.zactonz.com/og/?', $url);
        self::assertSame(substr(hash_hmac('sha256', 'k=42&theme=dark&title=Ship%20faster%20%26%20safer', 's3cret'), 0, 40), $query['sig']);
        self::assertSame('42', $query['k']);
        self::assertSame('Ship faster & safer', $query['title']);
        self::assertArrayNotHasKey('subtitle', $query);
    }

    public function testSignatureDoesNotDependOnArgumentOrder(): void
    {
        self::assertSame(
            SignedUrl::og(7, 'key', ['title' => 'A', 'site' => 'b.com']),
            SignedUrl::og(7, 'key', ['site' => 'b.com', 'title' => 'A']),
        );
    }

    public function testIgnoresCallerSuppliedKeyIdAndSignature(): void
    {
        $query = self::query(SignedUrl::og(7, 'key', ['title' => 'A', 'k' => 999, 'sig' => 'forged']));

        self::assertSame('7', $query['k']);
        self::assertNotSame('forged', $query['sig']);
    }

    public function testNormalisesTrailingSlashOfBaseUrl(): void
    {
        self::assertStringStartsWith('http://127.0.0.1:8082/og/?', SignedUrl::og(1, 'key', ['title' => 'A'], 'http://127.0.0.1:8082/'));
    }
}
