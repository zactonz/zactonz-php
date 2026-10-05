<?php

declare(strict_types=1);

namespace Zactonz;

use SensitiveParameter;

/**
 * Builds URLs that authenticate with a signature instead of a header, for
 * places that cannot send one, such as an `og:image` meta tag.
 *
 * Building a URL makes no request and spends no quota.
 */
final class SignedUrl
{
    /**
     * A signed URL for the OG Image endpoint.
     *
     * @param int                                    $keyId      Id of the key, shown beside it in the console.
     * @param string                                 $secret     Signing secret of the key, shown beside it in the console.
     * @param array<string, string|int|bool|null>    $parameters Endpoint parameters by their API names, such as `title` and `theme`.
     *
     * @see https://developers.zactonz.com/apis/og-image/
     */
    public static function og(int $keyId, #[SensitiveParameter] string $secret, array $parameters, string $baseUrl = Client::BASE_URL): string
    {
        $query = ['k' => (string) $keyId];
        foreach ($parameters as $name => $value) {
            if ($value === null || $name === 'sig' || $name === 'k') {
                continue;
            }
            $query[$name] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }
        // The API signs the same thing: every parameter but the signature, sorted by name.
        ksort($query);
        $query['sig'] = substr(hash_hmac('sha256', http_build_query($query, '', '&', PHP_QUERY_RFC3986), $secret), 0, 40);

        return rtrim($baseUrl, '/') . '/og/?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
