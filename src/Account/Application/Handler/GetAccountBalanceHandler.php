<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Repository\AccountRepositoryInterface;

class GetAccountBalanceHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function handle(GetAccountBalanceQuery $query): ?AccountBalanceResponse
    {
        return $this->accountRepository->getAccountBalance($query->getAccountId());
    }
}