<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Repository\AccountRepositoryInterface;

class GetUserAccountsHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function handle(GetUserAccountsQuery $query): UserAccountsResponse
    {
        $accounts = $this->accountRepository->getUserAccountsSummary($query->getUserId());
        
        return new UserAccountsResponse(
            $query->getUserId(),
            $accounts
        );
    }
}