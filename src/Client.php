<?php

declare(strict_types=1);

namespace Zactonz;

use InvalidArgumentException;
use SensitiveParameter;
use Zactonz\Exception\ApiException;
use Zactonz\Exception\ConfigurationException;
use Zactonz\Exception\MissingKeyException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\CurlTransport;
use Zactonz\Http\Dispatcher;
use Zactonz\Http\Transport;
use Zactonz\Service\Services;

/**
 * Entry point to the Zactonz APIs.
 *
 *     $zactonz = new Zactonz\Client(['zk_qr_…', 'zk_screen_…']);
 *     $qr      = $zactonz->qr()->encode(content: 'https://example.com');
 *
 * @see https://developers.zactonz.com/apis/
 */
final class Client
{
    use Services;

    public const VERSION = '0.1.0';
    public const BASE_URL = 'https://api.zactonz.com';

    /**
     * Environment variable read by {@see fromEnvironment()}.
     */
    public const KEYS_VARIABLE = 'ZACTONZ_API_KEYS';

    private readonly Keyring $keys;
    private readonly Dispatcher $dispatcher;

    /**
     * @param string|array<int|string, string> $keys       One key, a list of keys, or keys by product name. A key in the usual `zk_<product>_…` form is matched to its product automatically.
     * @param float                            $timeout    Seconds to wait for a response. Some endpoints render pages or talk to mail servers and need most of the default.
     * @param int                              $maxRetries How many times a request may be sent again after a rate limit or a failure that is safe to repeat. `0` turns retries off.
     * @param string                           $baseUrl    Where the API lives. Change it only to point at a proxy or a test double.
     * @param Transport|null                   $transport  Sends the HTTP requests. Defaults to cURL.
     *
     * @throws ConfigurationException   When no usable key is given.
     * @throws InvalidArgumentException When the timeout or retry count is out of range.
     */
    public function __construct(
        #[SensitiveParameter] string|array $keys,
        float $timeout = 90.0,
        int $maxRetries = 2,
        string $baseUrl = self::BASE_URL,
        ?Transport $transport = null,
    ) {
        if ($timeout <= 0) {
            throw new InvalidArgumentException('The timeout must be greater than zero');
        }
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('The retry count cannot be negative');
        }
        $this->keys       = new Keyring($keys);
        $this->dispatcher = new Dispatcher($this->keys, $transport ?? new CurlTransport(), $baseUrl, $timeout, $maxRetries);
    }

    /**
     * Creates a client from the keys in the `ZACTONZ_API_KEYS` environment
     * variable, separated by commas or whitespace.
     *
     * @throws ConfigurationException When the variable is empty or holds an unusable key.
     */
    public static function fromEnvironment(
        float $timeout = 90.0,
        int $maxRetries = 2,
        string $baseUrl = self::BASE_URL,
        ?Transport $transport = null,
    ): self {
        // getenv() is empty when a framework loads .env files into the superglobals only.
        $value = getenv(self::KEYS_VARIABLE) ?: ($_ENV[self::KEYS_VARIABLE] ?? $_SERVER[self::KEYS_VARIABLE] ?? '');
        $keys  = preg_split('/[\s,]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$keys) {
            throw new MissingKeyException('Set ' . self::KEYS_VARIABLE . ' to one or more API keys separated by commas');
        }
        return new self($keys, $timeout, $maxRetries, $baseUrl, $transport);
    }

    /**
     * Whether a key is configured for a product.
     *
     * @param string $product Product name as it appears inside a key: `zk_<product>_…`.
     */
    public function hasKeyFor(string $product): bool
    {
        return $this->keys->has($product);
    }

    /**
     * Reports what the API knows about the configured key of a product: its
     * state, plan, and the quota left today and this month. The call itself
     * spends no quota.
     *
     * @param string $product Product name as it appears inside a key: `zk_<product>_…`.
     *
     * @throws ApiException       When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/authentication/
     */
    public function keyStatus(string $product): Result
    {
        return $this->dispatcher->result($product, 'GET', '/auth/me/');
    }

    /**
     * Fetches a file from a link the API returned, such as the `data` of a
     * screenshot or the `image` of a conversion. No API key is sent.
     *
     * @throws InvalidArgumentException When the URL is not http or https.
     * @throws ApiException             When the server does not answer with the file.
     * @throws TransportException       When the request does not complete.
     */
    public function download(string $url): File
    {
        return $this->dispatcher->download($url);
    }
}
