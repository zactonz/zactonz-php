<?php

declare(strict_types=1);

namespace Zactonz;

/**
 * Where a key and its account stand against their limits, as reported in
 * the headers of a response. A value is null when the response did not
 * carry that header.
 */
final class RateLimit
{
    /**
     * @param int|null $limit          Requests allowed per minute.
     * @param int|null $remaining      Requests left in the current minute.
     * @param int|null $resetsAt       Unix time at which the minute window rolls over.
     * @param int|null $dayLimit       Units allowed per day.
     * @param int|null $dayRemaining   Units left today.
     * @param int|null $monthLimit     Units allowed per month.
     * @param int|null $monthRemaining Units left this month.
     */
    public function __construct(
        public readonly ?int $limit,
        public readonly ?int $remaining,
        public readonly ?int $resetsAt,
        public readonly ?int $dayLimit,
        public readonly ?int $dayRemaining,
        public readonly ?int $monthLimit,
        public readonly ?int $monthRemaining,
    ) {
    }

    /**
     * @param array<string, string> $headers Header values keyed by lower-case name.
     */
    public static function fromHeaders(array $headers): self
    {
        $read = static fn (string $name): ?int => is_numeric($headers[$name] ?? null) ? (int) $headers[$name] : null;

        return new self(
            $read('x-ratelimit-limit'),
            $read('x-ratelimit-remaining'),
            $read('x-ratelimit-reset'),
            $read('x-quota-day-limit'),
            $read('x-quota-day-remaining'),
            $read('x-quota-month-limit'),
            $read('x-quota-month-remaining'),
        );
    }
}
