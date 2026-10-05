<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Zactonz\Exception\InvalidKeyException;
use Zactonz\Exception\MissingKeyException;
use Zactonz\Keyring;

final class KeyringTest extends BaseTestCase
{
    public function testMatchesKeysToProductsByPrefix(): void
    {
        $keys = new Keyring(['zk_qr_abc', ' zk_screen_def ']);

        self::assertSame('zk_qr_abc', $keys->for('qr'));
        self::assertSame('zk_screen_def', $keys->for('screen'));
        self::assertFalse($keys->has('og'));
    }

    public function testAcceptsSingleKeyAsString(): void
    {
        self::assertSame('zk_og_abc', (new Keyring('zk_og_abc'))->for('og'));
    }

    public function testAcceptsAnyKeyFormatWhenProductIsNamed(): void
    {
        $keys = new Keyring(['Translator' => 'legacy-value']);

        self::assertSame('legacy-value', $keys->for('translator'));
    }

    public function testRejectsListedKeyThatDoesNotNameItsProduct(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('zk_<product>_<secret>');

        new Keyring(['zk_qr_abc', 'plain-key']);
    }

    public function testRejectsKeyWithUpperCasePrefix(): void
    {
        $this->expectException(InvalidKeyException::class);

        new Keyring('ZK_QR_abc');
    }

    public function testDoesNotEchoTheKeyInTheError(): void
    {
        try {
            new Keyring('sk-live-0123456789abcdef');
            self::fail('Expected InvalidKeyException');
        } catch (InvalidKeyException $exception) {
            self::assertStringNotContainsString('0123456789abcdef', $exception->getMessage());
        }
    }

    public function testThrowsForProductWithoutKey(): void
    {
        $keys = new Keyring('zk_qr_abc');

        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('"screen"');

        $keys->for('screen');
    }

    public function testRejectsEmptyInput(): void
    {
        $this->expectException(MissingKeyException::class);

        new Keyring(['', '  ']);
    }

    public function testHidesKeyValuesFromDumps(): void
    {
        $dump = print_r(new Keyring(['zk_qr_secretvalue']), true);

        self::assertStringNotContainsString('secretvalue', $dump);
        self::assertStringContainsString('qr', $dump);
    }
}
