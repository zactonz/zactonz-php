<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Zactonz\Client;

$zactonz = Client::fromEnvironment();

$html = <<<HTML
<!DOCTYPE html>
<html>
<body>
  <h1>Invoice #1042</h1>
  <p>Total due: <strong>120.00 USD</strong></p>
</body>
</html>
HTML;

$pdf  = $zactonz->screenshot()->captureHtml(html: $html, format: 'pdf');
$path = $zactonz->download($pdf->data)->save(__DIR__ . '/invoice-1042.pdf');

echo 'Saved ' . $path . PHP_EOL;
