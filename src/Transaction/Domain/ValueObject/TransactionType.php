<?php

namespace App\Transaction\Domain\ValueObject;

enum TransactionType: string
{
    case DEPOSIT = 'DEPOSIT';
    case WITHDRAWAL = 'WITHDRAWAL';
    case TRANSFER = 'TRANSFER';
    
    public function isDeposit(): bool
    {
        return $this === self::DEPOSIT;
    }
    
    public function isWithdrawal(): bool
    {
        return $this === self::WITHDRAWAL;
    }
    
    public function isTransfer(): bool
    {
        return $this === self::TRANSFER;
    }
}
