<?php

declare(strict_types=1);

namespace Zactonz\Http;

use CURLFile;
use Zactonz\Exception\ConnectionException;
use Zactonz\Exception\TimeoutException;
use Zactonz\Exception\TransportException;

/**
 * The default transport, built on the cURL extension.
 */
final class CurlTransport implements Transport
{
    private const CONNECT_TIMEOUT = 15;

    /**
     * cURL errors that mean the request was never delivered.
     */
    private const NOT_DELIVERED = [
        CURLE_COULDNT_RESOLVE_PROXY,
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_COULDNT_CONNECT,
        CURLE_SSL_CONNECT_ERROR,
    ];

    public function send(Request $request): Response
    {
        if ($request->url === '' || $request->method === '') {
            throw new TransportException('A request needs a method and a URL');
        }
        $headers = [];
        $handle  = curl_init();
        $options = [
            CURLOPT_URL            => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT_MS     => max(1, (int) round($request->timeout * 1000)),
            CURLOPT_HTTPHEADER     => self::headerLines($request->headers),
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ];
        // Only web URLs are ever fetched, whatever a caller or a redirect asks for.
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'http,https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        if ($request->method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $request->method;
            $options[CURLOPT_POSTFIELDS]    = self::body($request);
        }
        curl_setopt_array($handle, $options);

        $body = curl_exec($handle);
        if (!is_string($body)) {
            throw self::failure(curl_errno($handle), curl_error($handle), curl_getinfo($handle, CURLINFO_CONNECT_TIME) > 0);
        }

        return new Response((int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $headers, $body);
    }

    private static function failure(int $code, string $message, bool $connected): TransportException
    {
        $message = $message !== '' ? $message : 'The request could not be sent';

        // A timeout while resolving the host or connecting means nothing was sent.
        return match (true) {
            in_array($code, self::NOT_DELIVERED, true)        => new ConnectionException($message, $code),
            $code === CURLE_OPERATION_TIMEDOUT && !$connected => new ConnectionException($message, $code),
            $code === CURLE_OPERATION_TIMEDOUT                => new TimeoutException($message, $code),
            default                                           => new TransportException($message, $code),
        };
    }

    /**
     * @return array<string, string|CURLFile>|string
     */
    private static function body(Request $request): array|string
    {
        if (!$request->files) {
            return http_build_query($request->fields, '', '&', PHP_QUERY_RFC3986);
        }
        $parts = $request->fields;
        foreach ($request->files as $name => $path) {
            $type         = function_exists('mime_content_type') ? mime_content_type($path) : false;
            $parts[$name] = new CURLFile($path, $type ?: 'application/octet-stream', basename($path));
        }
        return $parts;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return list<string>
     */
    private static function headerLines(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        return $lines;
    }
}
