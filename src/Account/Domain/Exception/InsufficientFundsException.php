<?php

namespace App\Account\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class InsufficientFundsException extends DomainException
{
    public static function forWithdrawal(string $balance, string $requested): self
    {
        return new self(
            sprintf(
                'Insufficient funds. Available: %s, Requested: %s',
                $balance,
                $requested
            )
        );
    }
}
