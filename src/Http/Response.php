<?php

declare(strict_types=1);

namespace Zactonz\Http;

/**
 * An HTTP response as a transport received it.
 */
final class Response
{
    /**
     * Header values keyed by lower-case name.
     *
     * @var array<string, string>
     */
    public readonly array $headers;

    /**
     * @param array<string, string> $headers Header values keyed by name, in any case.
     */
    public function __construct(
        public readonly int $statusCode,
        array $headers,
        public readonly string $body,
    ) {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * The media type without its parameters, in lower case.
     */
    public function contentType(): string
    {
        return strtolower(trim(explode(';', (string) $this->header('content-type'))[0]));
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
