<?php

declare(strict_types=1);

namespace Zactonz\Http;

use Zactonz\Exception\TransportException;

/**
 * Sends one HTTP request and returns the response.
 *
 * Implement this to route requests through your own HTTP client, and pass
 * the implementation to the client's constructor. An implementation must not
 * follow redirects and must not throw for HTTP error statuses.
 */
interface Transport
{
    /**
     * @throws TransportException When no response was received.
     */
    public function send(Request $request): Response;
}
