<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * No response arrived within the configured timeout. The API may still have processed the request.
 */
final class TimeoutException extends TransportException
{
}
