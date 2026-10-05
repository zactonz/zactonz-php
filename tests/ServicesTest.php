<?php

declare(strict_types=1);

namespace Zactonz\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionNamedType;
use Zactonz\File;
use Zactonz\Result;
use Zactonz\Testing\FakeTransport;

/**
 * Checks every generated method against the specification it was generated
 * from: it must call the specified path with the specified method, use the
 * key of the specified product, and send each argument under the name the
 * specification gives it.
 */
final class ServicesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, array<string, mixed>, array<string, mixed>, array<string, mixed>}>
     */
    public static function methods(): iterable
    {
        $root   = dirname(__DIR__);
        $config = require $root . '/tools/endpoints.php';
        foreach ($config['endpoints'] as $slug => $endpoint) {
            $spec = json_decode((string) file_get_contents($root . '/specs/' . $slug . '.json'), true, 512, JSON_THROW_ON_ERROR);
            foreach ($endpoint['methods'] as $method) {
                yield $endpoint['service'] . '()->' . $method['name'] . '()' => [$endpoint['service'], $endpoint, $method, $spec];
            }
        }
    }

    /**
     * @param array<string, mixed> $endpoint
     * @param array<string, mixed> $method
     * @param array<string, mixed> $spec
     */
    #[DataProvider('methods')]
    public function testMethodMatchesItsSpecification(string $service, array $endpoint, array $method, array $spec): void
    {
        $path      = (string) array_key_first($spec['paths']);
        $operation = $spec['paths'][$path][array_key_first($spec['paths'][$path])];
        $names     = array_column($operation['parameters'] ?? [], 'name');
        $names     = array_merge($names, array_keys($operation['requestBody']['content']['application/x-www-form-urlencoded']['schema']['properties'] ?? []));
        $names     = array_merge($names, array_column($endpoint['add'] ?? [], 'name'), array_column($method['add'] ?? [], 'name'));
        $returns   = ($method['returns'] ?? 'result') === 'file' ? File::class : Result::class;
        $transport = new FakeTransport($returns === File::class ? FakeTransport::file('bytes', 'image/png') : self::ok(['ok' => true]));
        $object    = $this->client($transport)->{$service}();

        $arguments = [];
        $expected  = array_map('strval', $method['force'] ?? []);
        $uploads   = [];
        foreach ((new ReflectionMethod($object, $method['name']))->getParameters() as $parameter) {
            $apiName = self::apiName($parameter->getName(), $names);
            $type    = $parameter->getType();
            if (in_array($apiName, $endpoint['files'] ?? [], true)) {
                $arguments[$parameter->getName()] = __FILE__;
                $uploads[$apiName]                = __FILE__;
                continue;
            }
            [$value, $sent] = match ($type instanceof ReflectionNamedType ? $type->getName() : (string) $type) {
                'int'   => [7, '7'],
                'bool'  => [true, '1'],
                'string' => ['value', 'value'],
                default => str_contains((string) $type, 'array') ? [['a', 'b'], 'a,b'] : [1.5, '1.5'],
            };
            $arguments[$parameter->getName()] = $value;
            $expected[$apiName]               = $sent;
        }

        $returned = $object->{$method['name']}(...$arguments);
        $request  = $transport->lastRequest();
        $sent     = $request->fields;
        if ($request->method === 'GET') {
            parse_str((string) parse_url($request->url, PHP_URL_QUERY), $sent);
        }
        ksort($sent);
        ksort($expected);

        self::assertInstanceOf($returns, $returned);
        self::assertSame($method['http'] ?? strtoupper((string) array_key_first($spec['paths'][$path])), $request->method);
        self::assertSame('https://api.zactonz.com' . $path, explode('?', $request->url)[0]);
        self::assertSame('Bearer ' . self::keyOf($spec['x-zactonz']['product']), $request->headers['Authorization']);
        self::assertSame($expected, $sent);
        self::assertSame($uploads, $request->files);
    }

    /**
     * The API name behind a PHP argument: `maxChars` is sent as `max_chars`.
     *
     * @param list<string> $names
     */
    private static function apiName(string $argument, array $names): string
    {
        foreach ($names as $name) {
            if (strtolower(str_replace(['_', '-'], '', $name)) === strtolower($argument)) {
                return $name;
            }
        }
        self::fail('The argument $' . $argument . ' matches no parameter of the specification');
    }

    private static function keyOf(string $product): string
    {
        foreach (self::KEYS as $key) {
            if (str_starts_with($key, 'zk_' . $product . '_')) {
                return $key;
            }
        }
        self::fail('No test key for ' . $product);
    }
}
