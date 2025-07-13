# Architecture Documentation

## Domain-Driven Design (DDD) Bounded Contexts

### Account Context
**Purpose:** Manages financial accounts, balances, and money operations

**Location:** `src/Account/`

**Key Components:**
- **Account Entity:** Core aggregate managing user balances
- **Money Value Object:** Immutable monetary amounts with currency
- **Currency Enum:** Supported currencies (UAH, USD)

**Example Usage:**
```php
// Creating an account
$account = new Account($accountId, $userId, Currency::UAH);

// Depositing money
$money = new Money('100.00', Currency::UAH);
$account->deposit($money);

// Withdrawing with validation
$account->withdraw(new Money('50.00', Currency::UAH));
```

**Business Rules:**
- One account per user per currency
- Negative balances not allowed
- Currency mismatch validation
- Immutable money operations

### User Context
**Purpose:** Handles authentication, authorization, and user management

**Location:** `src/User/`

**Key Components:**
- **User Entity:** Implements Symfony UserInterface
- **UserRole Enum:** Role-based access (USER, ADMIN)

**Example Usage:**
```php
// Creating a user
$user = new User($userId, 'user@example.com', $hashedPassword, UserRole::USER);

// Authentication integration
$roles = $user->getRoles(); // ['ROLE_USER']
$identifier = $user->getUserIdentifier(); // email
```

**Business Rules:**
- Unique email addresses
- Role-based authorization
- Secure password handling

### Shared Context
**Purpose:** Common interfaces and domain events used across contexts

**Location:** `src/Shared/`

**Key Components:**
- **DomainEvent Interface:** Contract for all domain events
- **Common Value Objects:** Shared across contexts
- **Repository Interfaces:** Base contracts

**Example Usage:**
```php
// Domain event implementation
class AccountCreated implements DomainEvent
{
    public function getAggregateId(): string { return $this->accountId; }
    public function getEventType(): string { return 'account.created'; }
    public function getOccurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

## CQRS (Command Query Responsibility Segregation)

### Commands (Write Operations)
**Purpose:** Modify system state, enforce business rules

**Location:** `src/{Context}/Application/Command/`

**Structure:**
```php
// Command - Data Transfer Object
class CreateAccountCommand
{
    public function __construct(
        private string $userId,
        private Currency $currency
    ) {}
}

// Handler - Business Logic
class CreateAccountHandler
{
    public function handle(CreateAccountCommand $command): string
    {
        // Validate business rules
        // Create aggregate
        // Persist changes
        // Return result
    }
}
```

**Example Commands:**
- `CreateAccountCommand` - New account creation
- `DepositMoneyCommand` - Add funds to account
- `TransferMoneyCommand` - Move money between accounts

### Queries (Read Operations)
**Purpose:** Retrieve data without side effects

**Location:** `src/{Context}/Application/Query/`

**Structure:**
```php
// Query - Request specification
class GetAccountBalanceQuery
{
    public function __construct(
        private string $accountId
    ) {}
}

// Handler - Data retrieval
class GetAccountBalanceHandler
{
    public function handle(GetAccountBalanceQuery $query): AccountBalance
    {
        // Fetch data
        // Transform to read model
        // Return view
    }
}
```

**Benefits:**
- **Separation of Concerns:** Read/write operations isolated
- **Performance:** Optimized queries vs. business logic
- **Scalability:** Different scaling strategies for reads/writes
- **Complexity Management:** Simple, focused handlers

## Domain Entities with Value Objects

### Entities
**Purpose:** Objects with identity that can change over time

**Characteristics:**
- Unique identifier
- Mutable state
- Business logic
- Lifecycle management

**Example - Account Entity:**
```php
class Account
{
    private string $id;           // Identity
    private string $userId;       // Reference
    private Currency $currency;   // Value Object
    private string $balance;      // State
    
    public function deposit(Money $amount): void
    {
        // Business rule validation
        if (!$amount->getCurrency()->equals($this->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        
        // State modification
        $this->balance = bcadd($this->balance, $amount->getAmount(), 2);
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

### Value Objects
**Purpose:** Immutable objects representing concepts without identity

**Characteristics:**
- No identity
- Immutable
- Equality by value
- Self-validating

**Example - Money Value Object:**
```php
class Money
{
    public function __construct(
        private string $amount,
        private Currency $currency
    ) {
        // Self-validation
        if (bccomp($amount, '0', 2) < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }
    
    public function add(Money $other): Money
    {
        // Immutable operations
        if (!$this->currency->equals($other->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        
        return new Money(
            bcadd($this->amount, $other->amount, 2),
            $this->currency
        );
    }
}
```

**Benefits:**
- **Data Integrity:** Self-validating objects
- **Thread Safety:** Immutable by design
- **Expressiveness:** Domain concepts as first-class objects
- **Reusability:** Shareable across contexts

## Repository Pattern Implementation

### Interface (Port)
**Purpose:** Define data access contract in domain layer

**Location:** `src/{Context}/Domain/Repository/`

```php
interface AccountRepositoryInterface
{
    public function save(Account $account): void;
    public function findById(string $id): ?Account;
    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?Account;
    public function findByUserId(string $userId): array;
}
```

### Implementation (Adapter)
**Purpose:** Concrete data access using specific technology

**Location:** `src/{Context}/Infrastructure/Repository/`

```php
class DoctrineAccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function save(Account $account): void
    {
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
    }
    
    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?Account
    {
        return $this->findOneBy([
            'userId' => $userId,
            'currency' => $currency
        ]);
    }
}
```

### Usage in Application Layer
```php
class CreateAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}
    
    public function handle(CreateAccountCommand $command): string
    {
        // Check business rules
        $existingAccount = $this->accountRepository->findByUserIdAndCurrency(
            $command->getUserId(),
            $command->getCurrency()
        );
        
        if ($existingAccount) {
            throw new \DomainException('Account already exists');
        }
        
        // Create and save
        $account = new Account(/*...*/);
        $this->accountRepository->save($account);
        
        return $account->getId();
    }
}
```

**Benefits:**
- **Testability:** Mock repositories for unit tests
- **Technology Independence:** Domain doesn't know about database
- **Flexibility:** Easy to switch data storage
- **Clean Architecture:** Dependency inversion principle

## Integration Example

```php
// Complete flow example
class TransferMoneyHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}
    
    public function handle(TransferMoneyCommand $command): void
    {
        // Load aggregates
        $fromAccount = $this->accountRepository->findById($command->getFromAccountId());
        $toAccount = $this->accountRepository->findById($command->getToAccountId());
        
        // Business logic
        $money = new Money($command->getAmount(), $command->getCurrency());
        $fromAccount->withdraw($money);
        $toAccount->deposit($money);
        
        // Persist changes
        $this->accountRepository->save($fromAccount);
        $this->accountRepository->save($toAccount);
        
        // Publish events
        $this->eventDispatcher->dispatch(
            new MoneyTransferred($fromAccount->getId(), $toAccount->getId(), $money)
        );
    }
}
```

This architecture ensures:
- **Clean separation** of business logic and infrastructure
- **Testable** components with clear dependencies
- **Scalable** design with separated read/write operations
- **Maintainable** code following SOLID principles