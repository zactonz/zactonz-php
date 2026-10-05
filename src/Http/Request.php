<?php

declare(strict_types=1);

namespace Zactonz\Http;

/**
 * An HTTP request, ready for a transport to send.
 */
final class Request
{
    /**
     * @param string                $method  `GET` or `POST`.
     * @param string                $url     Absolute URL. For `GET` it already carries the query string.
     * @param array<string, string> $headers Header values keyed by name.
     * @param array<string, string> $fields  Form fields of a `POST` body.
     * @param array<string, string> $files   Paths of local files to upload, keyed by field name. When present the body is `multipart/form-data`.
     * @param float                 $timeout Seconds to wait for the whole exchange.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers = [],
        public readonly array $fields = [],
        public readonly array $files = [],
        public readonly float $timeout = 90.0,
    ) {
    }
}
