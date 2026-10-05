<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use LogicException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Zactonz\Result;

final class ResultTest extends BaseTestCase
{
    public function testReadsFieldsOfDataObject(): void
    {
        $result = new Result(200, ['status' => 200, 'data' => ['a' => ['b' => 1], 'status' => ['ok']]]);

        self::assertSame(['a' => ['b' => 1], 'status' => ['ok']], $result->data);
        self::assertSame(1, $result->get('a.b'));
        self::assertSame('fallback', $result->get('a.c', 'fallback'));
        self::assertSame(['ok'], $result['status']);
        self::assertTrue(isset($result['a']));
        self::assertFalse(isset($result['z']));
        self::assertNull($result['z']);
    }

    public function testDoesNotReadEnvelopeFieldsWhenDataIsAnObject(): void
    {
        $result = new Result(200, ['status' => 200, 'data' => ['a' => 1], 'extra' => 'beside']);

        self::assertNull($result['extra']);
        self::assertNull($result['status']);
        self::assertSame('beside', $result->body['extra']);
    }

    public function testReadsFieldsBesideScalarData(): void
    {
        $result = new Result(200, ['status' => '200', 'width' => 1280, 'height' => 1024, 'data' => 'https://example.com/shot.jpeg']);

        self::assertSame('https://example.com/shot.jpeg', $result->data);
        self::assertSame(1280, $result['width']);
        self::assertSame(1024, $result->get('height'));
        self::assertNull($result['status']);
        self::assertNull($result['data']);
    }

    public function testUsesWholeReplyWhenThereIsNoDataField(): void
    {
        $result = new Result(200, ['status' => '200', 'mails' => ['a@b.co' => 'valid'], 'ttr' => 2.1]);

        self::assertSame(['mails' => ['a@b.co' => 'valid'], 'ttr' => 2.1], $result->data);
        self::assertSame('valid', $result['mails']['a@b.co']);
        self::assertNull($result->get('mails.a@b.co'));
    }

    public function testSerialisesToItsData(): void
    {
        self::assertSame('{"a":1}', json_encode(new Result(200, ['status' => 200, 'data' => ['a' => 1]])));
    }

    public function testIsReadOnly(): void
    {
        $result = new Result(200, ['status' => 200, 'data' => []]);

        $this->expectException(LogicException::class);

        $result['a'] = 1;
    }
}
