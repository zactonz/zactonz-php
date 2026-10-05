<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * The connection could not be made, so the request never reached the API.
 */
final class ConnectionException extends TransportException
{
}
