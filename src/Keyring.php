<?php

declare(strict_types=1);

namespace Zactonz;

use SensitiveParameter;
use Zactonz\Exception\InvalidKeyException;
use Zactonz\Exception\MissingKeyException;

/**
 * Holds the configured API keys, one per product.
 *
 * @internal
 */
final class Keyring
{
    private const CONSOLE = 'https://developers.zactonz.com/console/';

    /**
     * @var array<string, string>
     */
    private array $keys = [];

    /**
     * @param string|array<int|string, string> $keys One key, a list of keys, or keys by product name.
     *
     * @throws InvalidKeyException When a listed key does not name its product.
     * @throws MissingKeyException When no key is given at all.
     */
    public function __construct(#[SensitiveParameter] string|array $keys)
    {
        foreach ((array) $keys as $product => $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            // A key says which product it belongs to: zk_<product>_<secret>.
            if (is_int($product)) {
                if (!preg_match('/^zk_([a-z0-9]+)_[A-Za-z0-9]+$/', $key, $match)) {
                    throw new InvalidKeyException(
                        'The key starting "' . substr($key, 0, 6) . '" is not in the form zk_<product>_<secret>. '
                        . 'To use a key in another form, name its product: [\'translator\' => $key].'
                    );
                }
                $product = $match[1];
            }
            $this->keys[strtolower($product)] = $key;
        }
        if (!$this->keys) {
            throw new MissingKeyException('No API key was given. Create one at ' . self::CONSOLE);
        }
    }

    public function has(string $product): bool
    {
        return isset($this->keys[$product]);
    }

    /**
     * @throws MissingKeyException
     */
    public function for(string $product): string
    {
        return $this->keys[$product] ?? throw new MissingKeyException(
            'No API key is configured for the "' . $product . '" product. Create one at ' . self::CONSOLE
        );
    }

    /**
     * Keeps key values out of var_dump(), logs and error reports.
     *
     * @return array{products: list<string>}
     */
    public function __debugInfo(): array
    {
        return ['products' => array_keys($this->keys)];
    }
}
