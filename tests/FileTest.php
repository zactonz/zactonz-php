<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Zactonz\Exception\ZactonzException;
use Zactonz\File;

final class FileTest extends BaseTestCase
{
    public function testSavesToDisk(): void
    {
        $path = sys_get_temp_dir() . '/zactonz-' . bin2hex(random_bytes(6)) . '.txt';

        self::assertSame($path, (new File('hello', 'text/plain'))->save($path));
        self::assertSame('hello', file_get_contents($path));

        unlink($path);
    }

    public function testSaveThrowsWithoutEmittingAWarning(): void
    {
        $warnings = 0;
        set_error_handler(static function () use (&$warnings): bool {
            $warnings++;
            return true;
        });

        try {
            (new File('hello', 'text/plain'))->save(sys_get_temp_dir() . '/zactonz-missing-dir/file.txt');
            self::fail('Expected ZactonzException');
        } catch (ZactonzException $exception) {
            self::assertStringContainsString('zactonz-missing-dir', $exception->getMessage());
        } finally {
            restore_error_handler();
        }
        self::assertSame(0, $warnings);
    }

    public function testEncodesAsBase64AndDataUri(): void
    {
        $file = new File('hello', 'text/plain');

        self::assertSame(5, $file->size());
        self::assertSame('aGVsbG8=', $file->base64());
        self::assertSame('data:text/plain;base64,aGVsbG8=', $file->dataUri());
    }

    public function testDerivesExtensionFromContentType(): void
    {
        self::assertSame('jpg', (new File('x', 'image/jpeg'))->extension());
        self::assertSame('pdf', (new File('x', 'application/pdf'))->extension());
        self::assertSame('bin', (new File('x', 'application/x-unknown'))->extension());
    }

    public function testExposesRequestIdAndLimits(): void
    {
        $file = new File('x', 'image/png', ['x-request-id' => 'r7', 'x-quota-day-remaining' => '99']);

        self::assertSame('r7', $file->requestId());
        self::assertSame(99, $file->rateLimit->dayRemaining);
    }
}
