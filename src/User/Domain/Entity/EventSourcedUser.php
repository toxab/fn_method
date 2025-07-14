<?php

namespace App\User\Domain\Entity;

use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\ValueObject\UserRole;
use App\Shared\Domain\Aggregate\AbstractAggregateRoot;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class EventSourcedUser extends AbstractAggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $email;
    private string $password;
    private UserRole $role;

    public static function create(string $userId, string $email, string $hashedPassword, UserRole $role): self
    {
        $user = new self($userId);
        $user->recordEvent(new UserCreatedEvent($userId, $email, $hashedPassword, $role));
        return $user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Nothing to clear as we don't store plain passwords
    }

    protected function applyUserCreatedEvent(UserCreatedEvent $event): void
    {
        $this->email = $event->getEmail();
        $this->password = $event->getHashedPassword();
        $this->role = $event->getRole();
    }
}