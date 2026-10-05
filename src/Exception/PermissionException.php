<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * The key belongs to another product, or the account is suspended (HTTP 403).
 */
final class PermissionException extends ApiException
{
}
