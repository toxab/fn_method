<?php

namespace App\Account\Domain\Exception;

use App\Account\Domain\ValueObject\Currency;
use App\Shared\Domain\Exception\DomainException;

class CurrencyMismatchException extends DomainException
{
    public static function forOperation(Currency $accountCurrency, Currency $operationCurrency): self
    {
        return new self(
            sprintf(
                'Currency mismatch. Account currency: %s, Operation currency: %s',
                $accountCurrency->value,
                $operationCurrency->value
            )
        );
    }
}
