<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class UserAlreadyExistsException extends DomainException
{
    public static function withEmail(string $email): self
    {
        return new self(
            sprintf('User with email %s already exists', $email)
        );
    }
}
