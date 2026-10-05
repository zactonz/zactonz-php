<?php

declare(strict_types=1);

namespace Zactonz\Http;

/**
 * @internal
 */
trait ReadsRequestId
{
    /**
     * The id the API assigned to the request. Quote it when contacting support.
     */
    public function requestId(): ?string
    {
        return $this->headers['x-request-id'] ?? null;
    }
}
