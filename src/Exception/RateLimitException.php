<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * A rate limit or quota was reached, or the request burst limit in front of
 * the API was tripped.
 */
final class RateLimitException extends ApiException
{
    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     * @param int|null              $retryAfter Seconds to wait before trying again, when the API said.
     */
    public function __construct(
        string $message,
        int $status,
        array $body = [],
        array $headers = [],
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $status, $body, $headers);
    }
}
