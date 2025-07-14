<?php

namespace App\Account\Infrastructure\ApiPlatform\Dto;

use App\Account\Domain\ValueObject\Currency;
use Symfony\Component\Validator\Constraints as Assert;

class CreateAccountDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $userId;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['UAH', 'USD'])]
    public string $currency;

    public function getCurrency(): Currency
    {
        return Currency::from($this->currency);
    }
}