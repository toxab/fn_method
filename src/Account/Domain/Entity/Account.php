<?php

namespace App\Account\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accounts')]
#[ApiResource]
class Account
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 3)]
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