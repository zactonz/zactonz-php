<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Zactonz\Client;
use Zactonz\Testing\FakeTransport;

abstract class TestCase extends BaseTestCase
{
    protected const KEYS = [
        'zk_qr_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'zk_barcode_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'zk_screen_cccccccccccccccccccccccccccccccccccccccc',
        'zk_og_dddddddddddddddddddddddddddddddddddddddd',
        'zk_image_eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
        'zk_unfurl_ffffffffffffffffffffffffffffffffffffffff',
        'zk_markdown_gggggggggggggggggggggggggggggggggggggggg',
        'zk_domain_hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh',
        'zk_email_iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii',
        'zk_mverifier_jjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjj',
        'zk_translator_kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk',
        'zk_gdrive_llllllllllllllllllllllllllllllllllllllll',
    ];

    protected function setUp(): void
    {
        Waits::$seconds = [];
    }

    protected function client(FakeTransport $transport, int $maxRetries = 2): Client
    {
        return new Client(self::KEYS, 30.0, $maxRetries, Client::BASE_URL, $transport);
    }

    protected static function ok(mixed $data = []): \Zactonz\Http\Response
    {
        return FakeTransport::json(['status' => 200, 'data' => $data]);
    }
}
