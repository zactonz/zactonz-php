<?php

/**
 * Generates src/Service/* and docs/reference.md from the API specifications
 * in specs/, using the names in tools/endpoints.php.
 *
 *   php tools/generate.php           write the files
 *   php tools/generate.php --check   write nothing; fail if a file is out of date
 */

declare(strict_types=1);

require __DIR__ . '/generator.php';

$root   = dirname(__DIR__);
$config = require __DIR__ . '/endpoints.php';
$specs  = loadSpecifications($root . '/specs', array_map('strval', array_keys($config['endpoints'])));

$methods = [];
foreach ($config['endpoints'] as $slug => $endpoint) {
    foreach ($endpoint['methods'] as $method) {
        $methods[$endpoint['service']][] = describeMethod($slug, $specs[$slug], $endpoint, $method);
    }
}

$files = ['docs/reference.md' => renderReference($config['services'], $methods)];
foreach ($config['services'] as $key => $service) {
    if (empty($methods[$key])) {
        fail("The service {$key} has no methods");
    }
    $files["src/Service/{$service['class']}.php"] = renderService($service, $methods[$key]);
}
$files['src/Service/Services.php'] = renderAccessors($config['services']);

exit(in_array('--check', $_SERVER['argv'] ?? [], true) ? check($root, $files) : write($root, $files));
