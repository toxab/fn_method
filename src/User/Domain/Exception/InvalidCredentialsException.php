<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class InvalidCredentialsException extends DomainException
{
    public static function create(): self
    {
        return new self('Invalid credentials provided');
    }
}
