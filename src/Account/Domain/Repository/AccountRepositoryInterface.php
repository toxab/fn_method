<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Application\Query\Response\AccountSummary;

interface AccountRepositoryInterface
{
    // Write operations
    public function save(Account $account): void;
    
    // Read operations (existing)
    public function findById(string $id): ?Account;
    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?Account;
    public function findByUserId(string $userId): array;
    
    // Query operations (optimized for reading)
    public function getAccountBalance(string $accountId): ?AccountBalanceResponse;
    
    /**
     * @return AccountSummary[]
     */
    public function getUserAccountsSummary(string $userId): array;
}