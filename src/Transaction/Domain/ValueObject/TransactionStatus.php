<?php

namespace App\Transaction\Domain\ValueObject;

enum TransactionStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
    
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }
    
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
