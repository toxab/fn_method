<?php

namespace App\Account\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class AccountNotFoundException extends DomainException
{
    public static function withId(string $accountId): self
    {
        return new self(
            sprintf('Account with ID %s not found', $accountId)
        );
    }
}
