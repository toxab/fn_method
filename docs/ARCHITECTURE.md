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

## Event Sourcing Implementation

### Overview
The project implements **Event Sourcing** pattern alongside traditional CRUD operations, providing a complete audit trail of all state changes and enabling powerful capabilities like event replay, time travel debugging, and complex business analytics.

### Event Store Architecture

#### Core Components

**Event Store Interface:**
```php
interface EventStoreInterface
{
    public function saveEvents(string $aggregateId, array $events, int $expectedVersion): void;
    public function getEventsForAggregate(string $aggregateId): array;
    public function getEventsForAggregateFromVersion(string $aggregateId, int $version): array;
    public function getAllEvents(): array;
    public function getEventsByType(string $eventType): array;
}
```

**Event Store Implementation:**
- **Location:** `src/Shared/Infrastructure/EventStore/DoctrineEventStore.php`
- **Storage:** MySQL with optimized indexes
- **Features:** Optimistic concurrency control, event versioning, JSON serialization
- **Performance:** Composite indexes on aggregate_id, event_type, and occurred_at

#### Database Schema
```sql
CREATE TABLE event_store (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    aggregate_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data JSON NOT NULL,
    version INT NOT NULL,
    occurred_at DATETIME NOT NULL,
    INDEX idx_aggregate_id (aggregate_id),
    INDEX idx_event_type (event_type),
    INDEX idx_occurred_at (occurred_at),
    UNIQUE KEY unique_aggregate_version (aggregate_id, version)
);
```

### Domain Events

#### Event Base Classes
```php
abstract class AbstractDomainEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredAt;
    private int $version;

    public function getEventType(): string
    {
        return static::class;
    }

    abstract public function getAggregateId(): string;
    abstract public function getEventData(): array;
}
```

#### Account Domain Events

**AccountCreatedEvent:**
```php
class AccountCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $accountId,
        private string $userId,
        private Currency $currency
    ) {
        parent::__construct();
    }

    public function getEventData(): array
    {
        return [
            'accountId' => $this->accountId,
            'userId' => $this->userId,
            'currency' => $this->currency->value,
        ];
    }
}
```

**MoneyDepositedEvent:**
```php
class MoneyDepositedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $accountId,
        private Money $amount,
        private string $newBalance
    ) {
        parent::__construct();
    }

    public function getEventData(): array
    {
        return [
            'accountId' => $this->accountId,
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency()->value,
            'newBalance' => $this->newBalance,
        ];
    }
}
```

**MoneyWithdrawnEvent:**
```php
class MoneyWithdrawnEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $accountId,
        private Money $amount,
        private string $newBalance
    ) {
        parent::__construct();
    }

    public function getEventData(): array
    {
        return [
            'accountId' => $this->accountId,
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency()->value,
            'newBalance' => $this->newBalance,
        ];
    }
}
```

#### User Domain Events

**UserCreatedEvent:**
```php
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
```

### Event-Sourced Aggregates

#### Abstract Aggregate Root
```php
abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    private string $id;
    private int $version = 0;
    private array $uncommittedEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->uncommittedEvents[] = $event;
        $this->applyEvent($event);
    }

    public function applyEvent(DomainEventInterface $event): void
    {
        $this->version++;
        $this->when($event);
    }

    protected function when(DomainEventInterface $event): void
    {
        $method = $this->getEventHandlerMethod($event);
        
        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }

    public static function reconstitute(string $id, array $events): static
    {
        $reflectionClass = new \ReflectionClass(static::class);
        $instance = $reflectionClass->newInstanceWithoutConstructor();
        $instance->id = $id;
        $instance->version = 0;
        $instance->uncommittedEvents = [];
        
        foreach ($events as $event) {
            $instance->applyEvent($event);
        }
        
        return $instance;
    }
}
```

#### EventSourcedAccount Aggregate
```php
class EventSourcedAccount extends AbstractAggregateRoot
{
    private string $userId;
    private Currency $currency;
    private string $balance;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public static function create(string $accountId, string $userId, Currency $currency): self
    {
        $account = new self($accountId);
        $account->recordEvent(new AccountCreatedEvent($accountId, $userId, $currency));
        return $account;
    }

    public function deposit(Money $amount): void
    {
        if (!$amount->getCurrency()->equals($this->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        $newBalance = bcadd($this->balance, $amount->getAmount(), 2);
        $this->recordEvent(new MoneyDepositedEvent($this->getId(), $amount, $newBalance));
    }

    public function withdraw(Money $amount): void
    {
        if (!$amount->getCurrency()->equals($this->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        if (bccomp($this->balance, $amount->getAmount(), 2) < 0) {
            throw new \InvalidArgumentException('Insufficient funds');
        }

        $newBalance = bcsub($this->balance, $amount->getAmount(), 2);
        $this->recordEvent(new MoneyWithdrawnEvent($this->getId(), $amount, $newBalance));
    }

    protected function applyAccountCreatedEvent(AccountCreatedEvent $event): void
    {
        $this->userId = $event->getUserId();
        $this->currency = $event->getCurrency();
        $this->balance = '0.00';
        $this->createdAt = $event->getOccurredAt();
        $this->updatedAt = $event->getOccurredAt();
    }

    protected function applyMoneyDepositedEvent(MoneyDepositedEvent $event): void
    {
        $this->balance = $event->getNewBalance();
        $this->updatedAt = $event->getOccurredAt();
    }

    protected function applyMoneyWithdrawnEvent(MoneyWithdrawnEvent $event): void
    {
        $this->balance = $event->getNewBalance();
        $this->updatedAt = $event->getOccurredAt();
    }
}
```

### Event-Sourced Repositories

#### Repository Interface
```php
interface EventSourcedAccountRepositoryInterface
{
    public function save(EventSourcedAccount $account): void;
    public function findById(string $id): ?EventSourcedAccount;
    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?EventSourcedAccount;
    public function findByUserId(string $userId): array;
}
```

#### Repository Implementation
```php
class EventSourcedAccountRepository implements EventSourcedAccountRepositoryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore
    ) {}

    public function save(EventSourcedAccount $account): void
    {
        $events = $account->getUncommittedEvents();
        
        if (empty($events)) {
            return;
        }

        $expectedVersion = $account->getVersion() - count($events);
        
        $this->eventStore->saveEvents(
            $account->getId(),
            $events,
            $expectedVersion
        );
        
        $account->markEventsAsCommitted();
    }

    public function findById(string $id): ?EventSourcedAccount
    {
        $events = $this->eventStore->getEventsForAggregate($id);
        
        if (empty($events)) {
            return null;
        }

        return EventSourcedAccount::reconstitute($id, $events);
    }
}
```

### Event-Sourced CQRS Handlers

#### Command Handlers
```php
class EventSourcedCreateAccountHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository
    ) {}

    public function handle(CreateAccountCommand $command): string
    {
        $existingAccount = $this->accountRepository->findByUserIdAndCurrency(
            $command->getUserId(),
            $command->getCurrency()
        );
        
        if ($existingAccount) {
            throw new \DomainException('Account already exists for this user and currency');
        }

        $accountId = Uuid::v4()->toRfc4122();
        
        $account = EventSourcedAccount::create(
            $accountId,
            $command->getUserId(),
            $command->getCurrency()
        );

        $this->accountRepository->save($account);

        return $accountId;
    }
}
```

#### Query Handlers
```php
class EventSourcedGetAccountBalanceHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository
    ) {}

    public function handle(GetAccountBalanceQuery $query): ?AccountBalanceResponse
    {
        $account = $this->accountRepository->findById($query->getAccountId());
        
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
}
```

### Event Sourcing Benefits

#### 1. **Complete Audit Trail**
- Every state change is recorded as an event
- Full history of all business operations
- Compliance and regulatory requirements
- Forensic analysis capabilities

#### 2. **Event Replay**
- Reconstruct aggregate state from events
- Time travel debugging
- Bug reproduction and analysis
- Historical data analysis

#### 3. **Business Intelligence**
- Rich event data for analytics
- Customer behavior analysis
- Business process optimization
- Real-time dashboards

#### 4. **Scalability**
- Append-only event store
- Optimized for writes
- Event-driven architecture
- Horizontal scaling possibilities

#### 5. **Flexibility**
- Event-driven integrations
- Event handlers for side effects
- Projection to different read models
- Event sourcing projections

### Testing Event Sourcing

#### Console Command for Testing
```php
#[AsCommand(name: 'app:test-event-sourcing')]
class TestEventSourcingCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Create User
        $userId = $this->createUserHandler->handle($createUserCommand);
        
        // 2. Create Account
        $accountId = $this->createAccountHandler->handle($createAccountCommand);
        
        // 3. Deposit Money
        $this->depositMoneyHandler->handle($depositCommand);
        
        // 4. Withdraw Money
        $this->withdrawMoneyHandler->handle($withdrawCommand);
        
        // 5. Show Event History
        $events = $this->eventStore->getEventsForAggregate($accountId);
        $this->displayEventHistory($events);
        
        return Command::SUCCESS;
    }
}
```

#### Example Test Results
```
✅ User created with ID: 11f22365-d000-4c60-9cd8-b099145fcaa2
✅ Account created with ID: a572854d-ee84-4220-acb3-27a6e63839b5
✅ Initial balance: 0.00 UAH
✅ Money deposited successfully
✅ Balance after deposit: 100.50 UAH
✅ Money withdrawn successfully
✅ Final balance: 70.25 UAH

Event Store Contents:
┌─────────────────────────────────────────────┬──────────────────────────────────────────────┬─────────────────────┐
│ Event Type                                  │ Data                                         │ Occurred At         │
├─────────────────────────────────────────────┼──────────────────────────────────────────────┼─────────────────────┤
│ App\Account\Domain\Event\AccountCreatedEvent│ {"accountId":"a572...","userId":"11f22..."}  │ 2025-07-14 16:33:54 │
│ App\Account\Domain\Event\MoneyDepositedEvent│ {"amount":"100.50","newBalance":"100.50"}    │ 2025-07-14 16:33:54 │
│ App\Account\Domain\Event\MoneyWithdrawnEvent│ {"amount":"30.25","newBalance":"70.25"}      │ 2025-07-14 16:33:54 │
└─────────────────────────────────────────────┴──────────────────────────────────────────────┴─────────────────────┘
```

### Configuration

#### Services Configuration
```yaml
# Event Store
App\Shared\Infrastructure\EventStore\EventStoreInterface:
    class: App\Shared\Infrastructure\EventStore\DoctrineEventStore
    arguments:
        $connection: '@doctrine.dbal.default_connection'

# Event-Sourced repositories
App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface:
    class: App\Account\Infrastructure\Repository\EventSourcedAccountRepository
    arguments:
        $eventStore: '@App\Shared\Infrastructure\EventStore\EventStoreInterface'

App\User\Domain\Repository\EventSourcedUserRepositoryInterface:
    class: App\User\Infrastructure\Repository\EventSourcedUserRepository
    arguments:
        $eventStore: '@App\Shared\Infrastructure\EventStore\EventStoreInterface'
```

---

This architecture ensures:
- **Clean separation** of business logic and infrastructure
- **Testable** components with clear dependencies
- **Scalable** design with separated read/write operations
- **Maintainable** code following SOLID principles
- **Complete audit trail** through Event Sourcing
- **Event-driven architecture** for complex business scenarios
- **Flexible data projections** for optimized queries