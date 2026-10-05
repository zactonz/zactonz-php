<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * The API key is missing, unknown or expired (HTTP 401 or 406).
 */
final class AuthenticationException extends ApiException
{
}
