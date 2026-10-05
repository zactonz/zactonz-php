<?php

/**
 * Functions behind tools/generate.php.
 */

declare(strict_types=1);

const PORTAL = 'https://developers.zactonz.com';
const NOTICE = 'Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.';

function fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

/**
 * @param list<string> $expected Slugs that endpoints.php describes.
 *
 * @return array<string, array<string, mixed>> Specifications by slug.
 */
function loadSpecifications(string $directory, array $expected): array
{
    $specs = [];
    foreach (glob($directory . '/*.json') ?: [] as $file) {
        $spec = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $specs[$spec['x-zactonz']['slug'] ?? basename($file, '.json')] = $spec;
    }
    if ($unnamed = array_diff(array_keys($specs), $expected)) {
        fail('Specifications with no entry in tools/endpoints.php: ' . implode(', ', $unnamed));
    }
    if ($missing = array_diff($expected, array_keys($specs))) {
        fail('Entries in tools/endpoints.php with no specification: ' . implode(', ', $missing));
    }
    return $specs;
}

/**
 * Everything the templates need to know about one generated method.
 *
 * @param array<string, mixed> $spec
 * @param array<string, mixed> $endpoint
 * @param array<string, mixed> $method
 *
 * @return array<string, mixed>
 */
function describeMethod(string $slug, array $spec, array $endpoint, array $method): array
{
    $path      = (string) array_key_first($spec['paths']);
    $http      = (string) array_key_first($spec['paths'][$path]);
    $operation = $spec['paths'][$path][$http];
    $force     = $method['force'] ?? [];
    $hidden    = array_merge($endpoint['hide'] ?? [], $method['hide'] ?? [], array_keys($force));
    $describe  = ($method['describe'] ?? []) + ($endpoint['describe'] ?? []);

    $parameters = [];
    foreach (array_merge(specParameters($operation), $endpoint['add'] ?? [], $method['add'] ?? []) as $parameter) {
        $name = $parameter['name'];
        if (in_array($name, $hidden, true) || (isset($method['only']) && !in_array($name, $method['only'], true))) {
            continue;
        }
        $isList       = in_array($name, $endpoint['lists'] ?? [], true);
        $isFile       = in_array($name, $endpoint['files'] ?? [], true);
        $values       = $method['values'][$name] ?? $parameter['enum'] ?? [];
        $parameters[] = [
            'name'        => $name,
            'variable'    => camelCase($name),
            'type'        => $isList ? 'string|array' : phpType($parameter['type']),
            'docType'     => $isList ? 'string|list<string>' : phpType($parameter['type']),
            'required'    => !empty($parameter['required']) || in_array($name, $method['require'] ?? [], true),
            'isFile'      => $isFile,
            'default'     => $parameter['default'] ?? null,
            'example'     => $parameter['example'] ?? null,
            'description' => describeParameter($describe[$name] ?? $parameter['description'], $parameter['type'], $values, $isFile, $isList),
        ];
    }
    usort($parameters, static fn (array $a, array $b): int => $b['required'] <=> $a['required']);

    return [
        'name'       => $method['name'],
        'summary'    => $method['summary'],
        'slug'       => $slug,
        'product'    => $spec['x-zactonz']['product'] ?? fail("The specification for {$slug} has no x-zactonz.product"),
        'http'       => $method['http'] ?? strtoupper($http),
        'path'       => $path,
        'returns'    => ($method['returns'] ?? 'result') === 'file' ? 'File' : 'Result',
        'force'      => $force,
        'requireOne' => array_values(array_intersect($endpoint['requireOne'] ?? [], array_column($parameters, 'name'))),
        'parameters' => $parameters,
    ];
}

/**
 * Query parameters and form fields of an operation, in one list.
 *
 * @param array<string, mixed> $operation
 *
 * @return list<array<string, mixed>>
 */
function specParameters(array $operation): array
{
    $found = [];
    foreach ($operation['parameters'] ?? [] as $parameter) {
        $found[] = [$parameter['name'], $parameter['schema'] ?? [], !empty($parameter['required']), $parameter['description'] ?? '', $parameter['example'] ?? null];
    }
    $form = $operation['requestBody']['content']['application/x-www-form-urlencoded']['schema'] ?? [];
    foreach ($form['properties'] ?? [] as $name => $schema) {
        $found[] = [$name, $schema, in_array($name, $form['required'] ?? [], true), $schema['description'] ?? '', null];
    }
    return array_map(static fn (array $p): array => [
        'name'        => $p[0],
        'type'        => $p[1]['type'] ?? 'string',
        'required'    => $p[2],
        'description' => $p[3],
        'default'     => $p[1]['default'] ?? null,
        'enum'        => $p[1]['enum'] ?? [],
        'example'     => $p[4] ?? $p[1]['example'] ?? null,
    ], $found);
}

/**
 * The specifications describe parameters as they travel over HTTP. This
 * rewrites the parts that differ for a PHP caller.
 *
 * @param list<scalar> $values Allowed values, when the parameter has a fixed set.
 */
function describeParameter(string $text, string $type, array $values, bool $isFile, bool $isList): string
{
    $text = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('#\]\(/(?!/)#', '](' . PORTAL . '/', $text)));
    if ($type === 'boolean') {
        $text = (string) preg_replace(['/^`1` /', '/^`0` /'], ['When true, ', 'When false, '], $text);
    }
    if ($isFile) {
        $text = 'Path of a local file. ' . $text;
    }
    if ($isList) {
        $text .= ' A list is also accepted.';
    }
    $unmentioned = array_filter($values, static fn (mixed $value): bool => !str_contains($text, '`' . $value . '`'));
    if ($unmentioned) {
        $text .= ' One of ' . implode(', ', array_map(static fn (mixed $value): string => '`' . $value . '`', $values)) . '.';
    }
    return $text;
}

function phpType(string $type): string
{
    return match ($type) {
        'integer' => 'int',
        'number'  => 'int|float',
        'boolean' => 'bool',
        default   => 'string',
    };
}

function camelCase(string $name): string
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name))));
}

function literal(mixed $value): string
{
    return match (true) {
        is_bool($value)                  => $value ? 'true' : 'false',
        is_int($value), is_float($value) => (string) $value,
        default                          => "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value) . "'",
    };
}

/**
 * @param array<string, mixed>       $service
 * @param list<array<string, mixed>> $methods
 */
function renderService(array $service, array $methods): string
{
    $imports = ['Zactonz\\Exception\\ApiException', 'Zactonz\\Exception\\TransportException', 'Zactonz\\Http\\Dispatcher'];
    foreach ($methods as $method) {
        $imports[] = 'Zactonz\\' . $method['returns'];
        if ($method['requireOne'] || array_filter($method['parameters'], static fn (array $p): bool => $p['isFile'] || $p['type'] === 'string|array')) {
            $imports[] = 'InvalidArgumentException';
        }
    }
    $imports = array_unique($imports);
    sort($imports);

    $code  = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Zactonz\\Service;\n\n";
    $code .= implode("\n", array_map(static fn (string $class): string => "use {$class};", $imports)) . "\n\n";
    $code .= "/**\n * {$service['title']}.\n *\n * " . NOTICE . "\n */\n";
    $code .= "final class {$service['class']}\n{\n";
    $code .= "    /**\n     * @internal Use the accessor on Zactonz\\Client instead.\n     */\n";
    $code .= "    public function __construct(private readonly Dispatcher \$dispatcher)\n    {\n    }\n\n";
    $code .= implode("\n\n", array_map('renderMethod', $methods)) . "\n}\n";

    return $code;
}

/**
 * @param array<string, mixed> $method
 */
function renderMethod(array $method): string
{
    $doc       = ['/**', ' * ' . $method['summary']];
    $arguments = [];
    $fields    = [];
    $uploads   = [];
    $throwsOwn = (bool) $method['requireOne'];
    if ($method['parameters']) {
        $doc[] = ' *';
    }
    foreach ($method['parameters'] as $parameter) {
        $doc[]       = " * @param {$parameter['docType']}" . ($parameter['required'] ? '' : '|null') . " \${$parameter['variable']} {$parameter['description']}";
        $nullable    = str_contains($parameter['type'], '|') ? $parameter['type'] . '|null' : '?' . $parameter['type'];
        $arguments[] = $parameter['required'] ? "{$parameter['type']} \${$parameter['variable']}," : "{$nullable} \${$parameter['variable']} = null,";
        if ($parameter['isFile']) {
            $uploads[] = "'{$parameter['name']}' => \${$parameter['variable']},";
        } else {
            $fields[] = "'{$parameter['name']}' => \${$parameter['variable']},";
        }
        $throwsOwn = $throwsOwn || $parameter['isFile'] || $parameter['type'] === 'string|array';
    }
    foreach ($method['force'] as $name => $value) {
        $fields[] = "'{$name}' => " . literal($value) . ',';
    }

    $doc[] = ' *';
    if ($throwsOwn) {
        $doc[] = ' * @throws InvalidArgumentException When an argument cannot be sent as given.';
    }
    $doc[] = ' * @throws ApiException When the API refuses the request.';
    $doc[] = ' * @throws TransportException When the request does not complete.';
    $doc[] = ' *';
    $doc[] = ' * @see ' . PORTAL . '/apis/' . $method['slug'] . '/';
    $doc[] = ' */';

    $body = [];
    if ($method['requireOne']) {
        $unset  = implode(' && ', array_map(static fn (string $name): string => '$' . camelCase($name) . ' === null', $method['requireOne']));
        $body[] = "if ({$unset}) {";
        $body[] = "    throw new InvalidArgumentException('Give one of: " . implode(', ', array_map('camelCase', $method['requireOne'])) . "');";
        $body[] = '}';
    }
    $call = '$this->dispatcher->' . strtolower($method['returns']) . "('{$method['product']}', '{$method['http']}', '{$method['path']}'";
    if ($fields || $uploads) {
        $call .= $fields ? ", [\n    " . implode("\n    ", $fields) . "\n]" : ', []';
        $call .= $uploads ? ", [\n    " . implode("\n    ", $uploads) . "\n]" : '';
    }
    $body[] = 'return ' . $call . ');';

    $signature = $arguments
        ? "public function {$method['name']}(\n    " . implode("\n    ", $arguments) . "\n): {$method['returns']} {"
        : "public function {$method['name']}(): {$method['returns']}\n{";

    return indent(implode("\n", $doc) . "\n" . $signature . "\n" . indent(implode("\n", $body)) . "\n}");
}

function indent(string $code): string
{
    return (string) preg_replace('/^(?=.)/m', '    ', $code);
}

/**
 * @param array<string, array<string, mixed>> $services
 */
function renderAccessors(array $services): string
{
    $accessors = [];
    foreach ($services as $name => $service) {
        $accessors[] = "    /**\n     * {$service['title']}.\n     */\n    public function {$name}(): {$service['class']}\n    {\n        return new {$service['class']}(\$this->dispatcher);\n    }";
    }
    return "<?php\n\ndeclare(strict_types=1);\n\nnamespace Zactonz\\Service;\n\n/**\n * Accessors for every service, used by Zactonz\\Client.\n *\n * " . NOTICE . "\n *\n * @internal\n */\ntrait Services\n{\n" . implode("\n\n", $accessors) . "\n}\n";
}

/**
 * @param array<string, array<string, mixed>>       $services
 * @param array<string, list<array<string, mixed>>> $methods
 */
function renderReference(array $services, array $methods): string
{
    $out = [
        '# Method reference',
        '',
        'Every method of the client. Arguments are passed by name, and an argument you leave out is not sent, so the API applies its own default.',
        '',
        'A method returns a `Zactonz\\Result` unless its name ends in `File`, in which case it returns a `Zactonz\\File`. The full description of each endpoint, with its response fields, is linked under each method.',
        '',
        '<!-- ' . NOTICE . ' -->',
    ];
    foreach ($services as $key => $service) {
        $out[] = '';
        $out[] = "## {$service['title']}";
        foreach ($methods[$key] as $method) {
            $sample = [];
            foreach ($method['parameters'] as $parameter) {
                $show = $parameter['example'] !== null && !$parameter['isFile']
                    && ($parameter['required'] || ($parameter['default'] !== null && $parameter['example'] !== $parameter['default']));
                if ($show) {
                    $sample[] = $parameter['variable'] . ': ' . literal($parameter['example']);
                }
            }
            if (!$sample) {
                foreach ($method['parameters'] as $parameter) {
                    if ($parameter['example'] !== null && !$parameter['isFile']) {
                        $sample[] = $parameter['variable'] . ': ' . literal($parameter['example']);
                        break;
                    }
                }
            }
            $out[] = '';
            $out[] = "### {$key}()->{$method['name']}()";
            $out[] = '';
            $out[] = $method['summary'];
            $out[] = '';
            $out[] = '```php';
            $out[] = '$' . strtolower($method['returns']) . " = \$zactonz->{$key}()->{$method['name']}(" . implode(', ', $sample) . ');';
            $out[] = '```';
            $out[] = '';
            $out[] = "- **Endpoint:** `{$method['http']} {$method['path']}`";
            $out[] = "- **Key:** `{$method['product']}`";
            $out[] = '- **Full reference:** ' . PORTAL . '/apis/' . $method['slug'] . '/';
            $out[] = '';
            $out[] = '| Argument | Type | Required | Default | Description |';
            $out[] = '|---|---|---|---|---|';
            foreach ($method['parameters'] as $parameter) {
                $default = $parameter['default'] === null || $parameter['default'] === '' ? '' : '`' . trim(literal($parameter['default']), "'") . '`';
                $out[]   = "| `{$parameter['variable']}` | `" . str_replace('|', '\\|', $parameter['docType']) . '` | ' . ($parameter['required'] ? 'yes' : 'no') . " | {$default} | " . str_replace('|', '\\|', $parameter['description']) . ' |';
            }
        }
    }
    return implode("\n", $out) . "\n";
}

/**
 * @param array<string, string> $files Contents by path relative to the repository root.
 */
function write(string $root, array $files): int
{
    foreach (glob($root . '/src/Service/*.php') ?: [] as $stale) {
        unlink($stale);
    }
    foreach ($files as $path => $contents) {
        file_put_contents($root . '/' . $path, $contents);
    }
    printf("%d files written\n", count($files));
    return 0;
}

/**
 * @param array<string, string> $files
 */
function check(string $root, array $files): int
{
    $stale = [];
    foreach ($files as $path => $contents) {
        if (!is_file($root . '/' . $path) || file_get_contents($root . '/' . $path) !== $contents) {
            $stale[] = $path;
        }
    }
    foreach (glob($root . '/src/Service/*.php') ?: [] as $existing) {
        if (!isset($files['src/Service/' . basename($existing)])) {
            $stale[] = 'src/Service/' . basename($existing) . ' (no longer generated)';
        }
    }
    if ($stale) {
        fwrite(STDERR, "Out of date; run php tools/generate.php:\n  " . implode("\n  ", $stale) . "\n");
        return 1;
    }
    echo "Generated files are up to date\n";
    return 0;
}
