<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * The API could not process the request as sent. Sending it again unchanged will fail again.
 */
final class InvalidRequestException extends ApiException
{
}
