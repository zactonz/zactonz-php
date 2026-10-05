<?php

declare(strict_types=1);

namespace Zactonz\Exception;

use Zactonz\Http\ReadsRequestId;

/**
 * The API answered, and the answer was a failure.
 *
 * Catch this to handle every refusal in one place, or one of its subclasses
 * to tell them apart.
 */
class ApiException extends ZactonzException
{
    use ReadsRequestId;

    /**
     * @param int                   $status  Status reported by the API: the `status` field of the reply when it has one, the HTTP status otherwise.
     * @param array<string, mixed>  $body    Decoded reply, or an empty array when the reply was not JSON.
     * @param array<string, string> $headers Response headers, keyed by lower-case name.
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly array $body = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message, $status);
    }
}
