<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Zactonz\Client;
use Zactonz\Exception\ApiException;

$zactonz = Client::fromEnvironment();

try {
    $qr = $zactonz->qr()->encode(content: 'https://zactonz.com', size: 6);
    echo 'QR code: ' . $qr['qr'] . PHP_EOL;
    echo 'Calls left this minute: ' . $qr->rateLimit->remaining . PHP_EOL;
} catch (ApiException $e) {
    echo 'The API refused the call (' . $e->status . '): ' . $e->getMessage() . PHP_EOL;
}
