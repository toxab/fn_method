<?php

namespace App\Account\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\Account\Infrastructure\ApiPlatform\Dto\CreateAccountDto;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\CreateAccountStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\DepositMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProcessor\WithdrawMoneyStateProcessor;
use App\Account\Infrastructure\ApiPlatform\StateProvider\AccountBalanceStateProvider;
use App\Account\Infrastructure\ApiPlatform\StateProvider\UserAccountsStateProvider;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accounts')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/accounts/{id}',
            provider: AccountBalanceStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/users/{userId}/accounts',
            provider: UserAccountsStateProvider::class
        ),
        new Post(
            uriTemplate: '/accounts',
            processor: CreateAccountStateProcessor::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/deposit',
            input: MoneyOperationDto::class,
            processor: DepositMoneyStateProcessor::class
        ),
        new Put(
            uriTemplate: '/accounts/{id}/withdraw',
            input: MoneyOperationDto::class,
            processor: WithdrawMoneyStateProcessor::class
        )
    ]
)]
class Account
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    private Currency $currency;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $balance;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $id, string $userId, Currency $currency)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->currency = $currency;
        $this->balance = '0.00';
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getBalance(): Money
    {
        return new Money($this->balance, $this->currency);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deposit(Money $amount): void
    {
        if (!$amount->getCurrency()->equals($this->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        $this->balance = bcadd($this->balance, $amount->getAmount(), 2);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function withdraw(Money $amount): void
    {
        if (!$amount->getCurrency()->equals($this->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        if (bccomp($this->balance, $amount->getAmount(), 2) < 0) {
            throw new \InvalidArgumentException('Insufficient funds');
        }

        $this->balance = bcsub($this->balance, $amount->getAmount(), 2);
        $this->updatedAt = new \DateTimeImmutable();
    }
}