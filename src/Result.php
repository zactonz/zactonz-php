<?php

declare(strict_types=1);

namespace Zactonz;

use ArrayAccess;
use JsonSerializable;
use LogicException;
use Zactonz\Http\ReadsRequestId;

/**
 * A successful reply from the API.
 *
 * Read a field with array access (`$result['title']`) or, for a nested
 * field, with {@see get()}. Most endpoints wrap their answer in a `data`
 * object, and those are the fields you read. A few answer with a single
 * value in `data` and put related fields beside it: a screenshot's link is
 * {@see $data} and its `width` and `height` are read the same way as any
 * other field.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Result implements ArrayAccess, JsonSerializable
{
    use ReadsRequestId;

    /**
     * The reply's `data` value. For an endpoint that answers without a
     * `data` field, the whole reply apart from `status`.
     */
    public readonly mixed $data;

    public readonly RateLimit $rateLimit;

    /**
     * @var array<int|string, mixed>
     */
    private readonly array $fields;

    /**
     * @param int                   $status  Status the API reported.
     * @param array<string, mixed>  $body    The complete decoded reply.
     * @param array<string, string> $headers Response headers, keyed by lower-case name.
     */
    public function __construct(
        public readonly int $status,
        public readonly array $body,
        public readonly array $headers = [],
    ) {
        $beside          = array_diff_key($body, ['status' => true, 'data' => true]);
        $this->data      = array_key_exists('data', $body) ? $body['data'] : $beside;
        $this->fields    = is_array($this->data) ? $this->data : $beside;
        $this->rateLimit = RateLimit::fromHeaders($headers);
    }

    /**
     * Reads a nested field by a dot-separated path, such as `quota.day.remaining`.
     *
     * A field whose own name contains a dot, such as an email address used
     * as a key, cannot be reached this way. Use array access for those.
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->fields;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->fields[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->fields[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('A result cannot be modified');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('A result cannot be modified');
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}
