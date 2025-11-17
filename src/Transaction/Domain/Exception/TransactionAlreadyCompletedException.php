<?php

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Transaction\Domain\ValueObject\TransactionStatus;

class TransactionAlreadyCompletedException extends DomainException
{
    public static function forTransaction(string $transactionId, TransactionStatus $status): self
    {
        return new self(
            sprintf(
                'Transaction "%s" is already in "%s" status and cannot be modified',
                $transactionId,
                $status->value
            )
        );
    }
}
