<?php

namespace App\User\Domain\Event;

use App\User\Domain\ValueObject\UserRole;
use App\Shared\Domain\Event\AbstractDomainEvent;

class UserCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $userId,
        private string $email,
        private string $hashedPassword,
        private UserRole $role
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getEventData(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'hashedPassword' => $this->hashedPassword,
            'role' => $this->role->value,
        ];
    }
}