<?php

declare(strict_types=1);

namespace Zactonz\Exception;

/**
 * A value given as an API key does not look like a Zactonz key.
 */
final class InvalidKeyException extends ConfigurationException
{
}
