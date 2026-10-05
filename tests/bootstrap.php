<?php

declare(strict_types=1);

namespace Zactonz\Http {
    /**
     * Stands in for the global usleep() inside the library's HTTP namespace,
     * so tests of the retry logic record the wait instead of sitting through it.
     */
    function usleep(int $microseconds): void
    {
        \Zactonz\Tests\Waits::$seconds[] = intdiv($microseconds, 1000000);
    }
}

namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';
}
