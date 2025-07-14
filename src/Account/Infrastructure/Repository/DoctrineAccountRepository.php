<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Application\Query\Response\AccountSummary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineAccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function save(Account $account): void
    {
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?Account
    {
        return $this->find($id);
    }

    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?Account
    {
        return $this->findOneBy([
            'userId' => $userId,
            'currency' => $currency
        ]);
    }

    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    public function getAccountBalance(string $accountId): ?AccountBalanceResponse
    {
        $account = $this->findById($accountId);
        
        if (!$account) {
            return null;
        }

        return new AccountBalanceResponse(
            $account->getId(),
            $account->getBalance()->getAmount(),
            $account->getBalance()->getCurrency()->value,
            $account->getUpdatedAt()
        );
    }

    public function getUserAccountsSummary(string $userId): array
    {
        $accounts = $this->findByUserId($userId);
        
        return array_map(function (Account $account) {
            return new AccountSummary(
                $account->getId(),
                $account->getBalance()->getAmount(),
                $account->getBalance()->getCurrency()->value,
                $account->getCreatedAt()
            );
        }, $accounts);
    }
}