<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use InvalidArgumentException;
use Zactonz\Client;
use Zactonz\Exception\MissingKeyException;
use Zactonz\Testing\FakeTransport;

final class ClientTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(Client::KEYS_VARIABLE);
        unset($_ENV[Client::KEYS_VARIABLE], $_SERVER[Client::KEYS_VARIABLE]);
    }

    public function testFromEnvironmentReadsCommaAndWhitespaceSeparatedKeys(): void
    {
        putenv(Client::KEYS_VARIABLE . "=zk_qr_abc, zk_domain_def\nzk_og_ghi");
        $transport = new FakeTransport(self::ok());

        $client = Client::fromEnvironment(transport: $transport);
        $client->domain()->dns(name: 'zactonz.com');

        self::assertTrue($client->hasKeyFor('qr'));
        self::assertTrue($client->hasKeyFor('og'));
        self::assertFalse($client->hasKeyFor('screen'));
        self::assertSame('Bearer zk_domain_def', $transport->lastRequest()->headers['Authorization']);
    }

    public function testFromEnvironmentFallsBackToSuperglobals(): void
    {
        $_ENV[Client::KEYS_VARIABLE] = 'zk_qr_abc';

        self::assertTrue(Client::fromEnvironment()->hasKeyFor('qr'));
    }

    public function testFromEnvironmentThrowsWhenVariableIsEmpty(): void
    {
        putenv(Client::KEYS_VARIABLE . '=');

        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage(Client::KEYS_VARIABLE);

        Client::fromEnvironment();
    }

    public function testRejectsNonPositiveTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('zk_qr_abc', timeout: 0);
    }

    public function testRejectsNegativeRetryCount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('zk_qr_abc', maxRetries: -1);
    }

    public function testUsesCustomBaseUrlWithoutDoublingSlashes(): void
    {
        $transport = new FakeTransport(self::ok('decoded'));
        $client    = new Client('zk_qr_abc', baseUrl: 'http://127.0.0.1:8082/', transport: $transport);

        $client->qr()->decode(image: 'https://example.com/a.png');

        self::assertSame('http://127.0.0.1:8082/qr/dec/', $transport->lastRequest()->url);
    }

    public function testKeyStatusCallsSelfCheckWithThatProductsKey(): void
    {
        $transport = new FakeTransport(self::ok(['key' => ['product' => 'og'], 'quota' => ['day' => ['remaining' => 480]]]));

        $status = $this->client($transport)->keyStatus('og');

        self::assertSame('https://api.zactonz.com/auth/me/', $transport->lastRequest()->url);
        self::assertSame('Bearer zk_og_dddddddddddddddddddddddddddddddddddddddd', $transport->lastRequest()->headers['Authorization']);
        self::assertSame(480, $status->get('quota.day.remaining'));
    }

    public function testDoesNotExposeKeysWhenDumped(): void
    {
        $dump = print_r(new Client('zk_qr_secretvalue'), true);

        self::assertStringNotContainsString('secretvalue', $dump);
    }

    public function testVersionMatchesTheLatestChangelogEntry(): void
    {
        $changelog = (string) file_get_contents(dirname(__DIR__) . '/CHANGELOG.md');

        preg_match('/^## (\d+\.\d+\.\d+)/m', $changelog, $match);

        self::assertSame(Client::VERSION, $match[1] ?? 'no version heading found');
    }
}
