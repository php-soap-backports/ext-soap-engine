<?php

declare(strict_types=1);

namespace Soap\ExtSoapEngine\Exception;

use Soap\Engine\Exception\RuntimeException;

final class UnexpectedConfigurationException extends RuntimeException
{
    /**
     * @param string $configurationKey
     * @param string $expectedType
     * @param mixed $value
     * @return static
     */
    public static function expectedTypeButGot(string $configurationKey, string $expectedType, $value): self
    {
        return new self(
            sprintf(
                'Invalid configuration. Expected value of option %s to be of type %s but got %s.',
                $configurationKey,
                $expectedType,
                gettype($value)
            )
        );
    }
}
