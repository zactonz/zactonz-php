<?php

declare(strict_types=1);

namespace Zactonz\Http;

use InvalidArgumentException;
use Zactonz\Client;
use Zactonz\Exception\ApiException;
use Zactonz\Exception\AuthenticationException;
use Zactonz\Exception\ConnectionException;
use Zactonz\Exception\InvalidRequestException;
use Zactonz\Exception\PermissionException;
use Zactonz\Exception\RateLimitException;
use Zactonz\Exception\ServerException;
use Zactonz\Exception\UnexpectedResponseException;
use Zactonz\File;
use Zactonz\Keyring;
use Zactonz\Result;

/**
 * Turns a method call into an HTTP request and its response into a result
 * or an exception, retrying where that is safe.
 *
 * @internal
 */
final class Dispatcher
{
    /**
     * Seconds the request burst limit in front of the API blocks a client for.
     */
    private const BURST_BLOCK = 10;

    /**
     * Most seconds one call may spend waiting between attempts.
     */
    private const WAIT_BUDGET = 30;

    /**
     * Gateway statuses that mean the API itself never answered.
     */
    private const GATEWAY_FAILURES = [502, 503, 504];

    public function __construct(
        private readonly Keyring $keys,
        private readonly Transport $transport,
        private readonly string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
    ) {
    }

    /**
     * @param array<string, mixed>       $parameters
     * @param array<string, string|null> $files
     */
    public function result(string $product, string $method, string $path, array $parameters = [], array $files = []): Result
    {
        return self::parse($this->send($this->request($product, $method, $path, $parameters, $files, 'application/json')));
    }

    /**
     * @param array<string, mixed>       $parameters
     * @param array<string, string|null> $files
     */
    public function file(string $product, string $method, string $path, array $parameters = [], array $files = []): File
    {
        $response = $this->send($this->request($product, $method, $path, $parameters, $files, '*/*'));
        if (!$response->isSuccessful() || $response->contentType() === 'application/json' || self::isEnvelope($response->body)) {
            // An endpoint that cannot produce the file answers with its usual JSON instead.
            self::parse($response);
            throw new UnexpectedResponseException('The API answered with JSON where a file was expected', $response->statusCode, [], $response->headers);
        }
        return new File($response->body, $response->contentType(), $response->headers);
    }

    public function download(string $url): File
    {
        if (!in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http and https URLs can be downloaded');
        }
        $response = $this->send(new Request('GET', $url, ['User-Agent' => self::userAgent()], [], [], $this->timeout));
        if (!$response->isSuccessful()) {
            throw new UnexpectedResponseException('The file could not be downloaded: HTTP ' . $response->statusCode, $response->statusCode, [], $response->headers);
        }
        return new File($response->body, $response->contentType(), $response->headers);
    }

    /**
     * @param array<string, mixed>       $parameters
     * @param array<string, string|null> $files
     */
    private function request(string $product, string $method, string $path, array $parameters, array $files, string $accept): Request
    {
        $uploads = [];
        foreach ($files as $name => $file) {
            if ($file === null || $file === '') {
                continue;
            }
            if (!is_file($file) || !is_readable($file)) {
                throw new InvalidArgumentException('The file "' . $file . '" does not exist or cannot be read');
            }
            $uploads[$name] = $file;
        }
        $fields  = self::fields($parameters);
        $url     = rtrim($this->baseUrl, '/') . $path;
        $headers = [
            'Authorization' => 'Bearer ' . $this->keys->for($product),
            'Accept'        => $accept,
            'User-Agent'    => self::userAgent(),
        ];
        if ($method === 'GET') {
            $url   .= $fields ? '?' . http_build_query($fields, '', '&', PHP_QUERY_RFC3986) : '';
            $fields = [];
        }
        return new Request($method, $url, $headers, $fields, $uploads, $this->timeout);
    }

    private function send(Request $request): Response
    {
        $waited = 0;
        for ($attempt = 0;; $attempt++) {
            $mayRetry = $attempt < $this->maxRetries;
            try {
                $response = $this->transport->send($request);
                $wait     = self::waitBeforeRetry($request, $response, $attempt);
            } catch (ConnectionException $failure) {
                // The request never reached the API, so sending it again cannot repeat any work.
                if (!$mayRetry) {
                    throw $failure;
                }
                $response = null;
                $wait     = self::backoff($attempt);
            }
            if ($response !== null && ($wait === null || !$mayRetry || $waited + $wait > self::WAIT_BUDGET)) {
                return $response;
            }
            usleep((int) $wait * 1000000);
            $waited += (int) $wait;
        }
    }

    /**
     * Seconds to wait before sending the request again, or null when it
     * should not be sent again.
     */
    private static function waitBeforeRetry(Request $request, Response $response, int $attempt): ?int
    {
        if ($response->statusCode === 429) {
            // A refused request was not processed, so any method can be repeated.
            $given = $response->header('retry-after');
            return is_numeric($given) ? max(1, (int) $given) : self::backoff($attempt);
        }
        if (self::isBurstBlock($response)) {
            return $attempt === 0 ? self::BURST_BLOCK : null;
        }
        if ($request->method === 'GET' && in_array($response->statusCode, self::GATEWAY_FAILURES, true)) {
            // A POST may have been processed before the gateway gave up, so only reads are repeated.
            return self::backoff($attempt);
        }
        return null;
    }

    /**
     * The firewall in front of the API answers a burst of requests with an
     * HTML 403 page. The API's own refusals are always JSON.
     */
    private static function isBurstBlock(Response $response): bool
    {
        return $response->statusCode === 403 && $response->contentType() === 'text/html' && !self::isEnvelope($response->body);
    }

    private static function backoff(int $attempt): int
    {
        return 1 << min($attempt, 4);
    }

    /**
     * @throws ApiException
     */
    private static function parse(Response $response): Result
    {
        $body = json_decode($response->body, true);
        if (!is_array($body)) {
            throw self::notJson($response);
        }
        // Several endpoints report a failure in the body of an HTTP 200 reply, some as a numeric string.
        $declared = array_key_exists('status', $body);
        $status   = is_numeric($body['status'] ?? null) ? (int) $body['status'] : null;
        $accepted = !$declared || ($status !== null && $status >= 200 && $status < 300);
        if ($response->isSuccessful() && $accepted) {
            return new Result($status ?? $response->statusCode, $body, $response->headers);
        }
        throw self::failure($response, $body, $status ?? $response->statusCode, $declared && $status === null);
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function failure(Response $response, array $body, int $status, bool $unreadableStatus): ApiException
    {
        $http    = $response->statusCode;
        $message = self::message($body) ?? 'The API answered with status ' . $status;

        // A 401 or 403 inside an HTTP 200 reply is an endpoint's own verdict on the
        // input, not a verdict on the key, so only the HTTP status selects those.
        if ($http === 429 || $status === 429) {
            $given = $response->header('retry-after');
            return new RateLimitException($message, $status, $body, $response->headers, is_numeric($given) ? max(0, (int) $given) : null);
        }
        $class = match (true) {
            $http === 401, $http === 406                   => AuthenticationException::class,
            $http === 403                                  => PermissionException::class,
            $http >= 500, $status >= 500                   => ServerException::class,
            $unreadableStatus, $http >= 300 && $http < 400 => UnexpectedResponseException::class,
            default                                        => InvalidRequestException::class,
        };
        return new $class($message, $status, $body, $response->headers);
    }

    private static function notJson(Response $response): ApiException
    {
        if (self::isBurstBlock($response)) {
            return new RateLimitException(
                'The request burst limit in front of the API blocked this request. Wait ' . self::BURST_BLOCK . ' seconds before sending more.',
                $response->statusCode,
                [],
                $response->headers,
                self::BURST_BLOCK,
            );
        }
        $message = 'The API answered with HTTP ' . $response->statusCode . ' and a body that is not JSON';
        return $response->statusCode >= 500
            ? new ServerException($message, $response->statusCode, [], $response->headers)
            : new UnexpectedResponseException($message, $response->statusCode, [], $response->headers);
    }

    /**
     * Whether a body is one of the API's JSON replies, whatever its Content-Type says.
     */
    private static function isEnvelope(string $body): bool
    {
        if (!str_starts_with(ltrim(substr($body, 0, 16)), '{')) {
            return false;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) && array_key_exists('status', $decoded);
    }

    /**
     * Endpoints put the reason for a failure in different fields.
     *
     * @param array<string, mixed> $body
     */
    private static function message(array $body): ?string
    {
        foreach ([$body['message'] ?? null, $body['data'] ?? null, $body['reason'] ?? null] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }
        $errors = array_filter((array) ($body['errors'] ?? []), 'is_string');
        return $errors ? implode(' ', $errors) : null;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, string>
     */
    private static function fields(array $parameters): array
    {
        $fields = [];
        foreach ($parameters as $name => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                if (array_filter($value, static fn (mixed $item): bool => !is_scalar($item))) {
                    throw new InvalidArgumentException('The "' . $name . '" argument must be a string or a list of strings');
                }
                $value = implode(',', array_map('strval', $value));
            }
            $fields[$name] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }
        return $fields;
    }

    private static function userAgent(): string
    {
        return 'zactonz-php/' . Client::VERSION . ' PHP/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }
}
