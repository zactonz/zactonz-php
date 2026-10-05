<?php

declare(strict_types=1);

namespace Zactonz\Testing;

use LogicException;
use Throwable;
use Zactonz\Http\Request;
use Zactonz\Http\Response;
use Zactonz\Http\Transport;

/**
 * A transport for your own tests. It answers with the responses you queue,
 * makes no network calls and records every request it is given.
 *
 *     $transport = new FakeTransport(FakeTransport::json(['status' => 200, 'data' => ['qr' => 'https://…']]));
 *     $zactonz   = new Client('zk_qr_test', maxRetries: 0, transport: $transport);
 *
 * Pass `maxRetries: 0` so that a queued failure is returned at once instead
 * of being waited on.
 */
final class FakeTransport implements Transport
{
    /**
     * @var list<Request>
     */
    private array $requests = [];

    /**
     * @var list<Response|Throwable>
     */
    private array $queue;

    public function __construct(Response|Throwable ...$responses)
    {
        $this->queue = array_values($responses);
    }

    /**
     * A JSON response, as the API sends them.
     *
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    public static function json(array $body, int $statusCode = 200, array $headers = []): Response
    {
        return new Response($statusCode, $headers + ['Content-Type' => 'application/json; charset=utf-8'], (string) json_encode($body));
    }

    /**
     * A file response, as the methods that return a File receive them.
     *
     * @param array<string, string> $headers
     */
    public static function file(string $bytes, string $contentType, array $headers = []): Response
    {
        return new Response(200, $headers + ['Content-Type' => $contentType], $bytes);
    }

    /**
     * Queues more responses. A queued exception is thrown in place of a response.
     */
    public function push(Response|Throwable ...$responses): void
    {
        array_push($this->queue, ...array_values($responses));
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;
        $next             = array_shift($this->queue);
        if ($next === null) {
            throw new LogicException('FakeTransport received ' . $request->method . ' ' . $request->url . ' with no response queued');
        }
        if ($next instanceof Throwable) {
            throw $next;
        }
        return $next;
    }

    /**
     * @return list<Request>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    public function lastRequest(): Request
    {
        return end($this->requests) ?: throw new LogicException('No request was sent');
    }
}
