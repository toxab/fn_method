# Fintech DDD Learning Project

Educational project demonstrating **Domain-Driven Design (DDD)**, **CQRS**, **Event Sourcing**, and **Hexagonal Architecture** using Symfony 7.

[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.0-green)](https://symfony.com/)
[![License](https://img.shields.io/badge/license-MIT-orange)](LICENSE)

---

## 📚 Table of Contents

- [Quick Start](#-quick-start)
- [Architecture Overview](#-architecture-overview)
- [DDD Patterns](#-ddd-patterns-implemented)
- [Project Structure](#-project-structure)
- [API Endpoints](#-api-endpoints)
- [Development](#-development)
- [Technology Stack](#-technology-stack)

---

## 🚀 Quick Start

### Prerequisites
- Docker Desktop (macOS/Windows) or Docker Engine (Linux)
- Make (optional, but recommended)

### One-Command Setup

```bash
# Clone repository
git clone <repository-url>
cd fn_method

# Run automated setup
make setup
```

That's it! The project will be available at:
- 🌐 **API**: http://localhost:8028
- 📚 **Swagger UI**: http://localhost:8028/api/docs
- 💾 **Adminer** (Database UI): http://localhost:8080
- 📧 **Mailpit** (Email Testing): http://localhost:8025

### Access Credentials

**Adminer (Database Management)**
- System: `MySQL`
- Server: `mysql`
- Username: `fintech_user`
- Password: `fintech_pass`
- Database: `fintech_db`

**Demo Users** (after running `make fixtures`):
- Admin: `admin@fintech.com` / `admin123`
- User: `user@fintech.com` / `user123`
- Another: `another@fintech.com` / `another123`

### Manual Setup

If you don't have Make:

```bash
# 1. Start containers
docker compose up -d

# 2. Install dependencies
docker compose exec php composer install

# 3. Run migrations
docker compose exec php bin/console doctrine:migrations:migrate

# 4. Generate JWT keys
docker compose exec php bin/console lexik:jwt:generate-keypair

# 5. Load demo data (optional)
docker compose exec php bin/console doctrine:fixtures:load
```

### Load Demo Data

To quickly start with pre-configured users and accounts:

```bash
make fixtures
# or
make db-seed  # migrations + fixtures
```

**Demo Users Created:**

| Role | Email | Password | Accounts |
|------|-------|----------|----------|
| **Admin** | admin@fintech.com | admin123 | UAH: 50,000.00<br>USD: 1,000.00 |
| **User** | user@fintech.com | user123 | UAH: 10,000.00<br>USD: 250.00 |
| **User** | another@fintech.com | another123 | UAH: 5,000.00 |

**Quick Test:**
```bash
# Get JWT token
curl -X POST http://localhost:8028/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@fintech.com", "password": "admin123"}'

# Use token in Swagger UI or API calls
```

---

## 🏗️ Architecture Overview

This project implements a clean architecture following DDD principles:

```
┌─────────────────────────────────────────┐
│      INFRASTRUCTURE LAYER               │
│  (Adapters - Technical Implementation) │
│  - API Platform (HTTP)                  │
│  - Doctrine ORM (Database)              │
│  - Symfony Framework                    │
└────────────┬────────────────────────────┘
             │ implements
             ▼
┌─────────────────────────────────────────┐
│      APPLICATION LAYER                  │
│  (Use Cases - Business Scenarios)       │
│  - Commands & Command Handlers          │
│  - State Processors                     │
└────────────┬────────────────────────────┘
             │ uses
             ▼
┌─────────────────────────────────────────┐
│      DOMAIN LAYER (CORE)                │
│  (Business Logic - Framework-free)      │
│  - Entities (Aggregates)                │
│  - Value Objects                        │
│  - Repository Interfaces (Ports)        │
│  - Domain Events                        │
└─────────────────────────────────────────┘
```

### Core Principles

✅ **Domain-Driven Design**: Business logic isolated in Domain layer
✅ **Hexagonal Architecture**: Domain doesn't depend on infrastructure
✅ **CQRS**: Separate models for reading and writing
✅ **Event Sourcing**: State stored as sequence of events
✅ **Bounded Contexts**: Logically separated subsystems

---

## 📦 DDD Patterns Implemented

### 1. Bounded Contexts

The system is divided into 3 bounded contexts:

#### **Account Context** (`src/Account/`)
Manages financial accounts and money operations.

```php
Account/
├── Domain/              # Business logic
│   ├── Entity/Account.php
│   ├── ValueObject/
│   │   ├── Money.php
│   │   └── Currency.php
│   └── Repository/
├── Application/         # Use cases
│   ├── Command/
│   └── Handler/
└── Infrastructure/      # Technical details
    ├── Repository/
    └── ApiPlatform/
```

**Responsibilities:**
- Account creation
- Deposits & withdrawals
- Balance inquiries

#### **User Context** (`src/User/`)
Manages users and authentication.

**Responsibilities:**
- User registration
- JWT authentication
- User management

#### **Shared Context** (`src/Shared/`)
Common infrastructure shared across contexts.

**Responsibilities:**
- Event Store for Event Sourcing
- Domain event interfaces

### 2. Aggregates

**Account Aggregate** is the core business entity:

```php
class Account  // Aggregate Root
{
    private string $id;           // Aggregate ID
    private string $userId;
    private Currency $currency;   // Value Object
    private string $balance;

    // Business invariants enforced
    public function deposit(Money $amount): void
    {
        // Validates currency match
        if (!$amount->getCurrency()->equals($this->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        $this->balance = bcadd($this->balance, $amount->getAmount(), 2);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function withdraw(Money $amount): void
    {
        // Validates sufficient funds
        if (bccomp($this->balance, $amount->getAmount(), 2) < 0) {
            throw new \InvalidArgumentException('Insufficient funds');
        }

        $this->balance = bcsub($this->balance, $amount->getAmount(), 2);
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

**Key Characteristics:**
- ✅ Transactional boundary
- ✅ Business invariants guaranteed
- ✅ All changes through Aggregate Root
- ✅ Identified by unique ID (UUID)

### 3. Value Objects

Immutable objects identified by their values, not identity.

#### **Currency**
```php
enum Currency: string
{
    case UAH = 'UAH';
    case USD = 'USD';

    public function equals(Currency $other): bool
    {
        return $this->value === $other->value;
    }
}
```

#### **Money**
```php
class Money
{
    public function __construct(
        private readonly string $amount,
        private readonly Currency $currency
    ) {}
}
```

**Benefits:**
- ✅ Type safety
- ✅ Prevents mixing currencies
- ✅ Immutability guarantees

### 4. Repository Pattern

**Domain Interface (Port):**
```php
interface AccountRepositoryInterface
{
    public function save(Account $account): void;
    public function findById(string $id): Account;
}
```

**Infrastructure Adapter:**
```php
class DoctrineAccountRepository implements AccountRepositoryInterface
{
    public function save(Account $account): void
    {
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }
}
```

### 5. CQRS (Command Query Responsibility Segregation)

**Command Side (Write):**
```php
class CreateAccountCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly Currency $currency
    ) {}
}

class CreateAccountHandler
{
    public function handle(CreateAccountCommand $command): string
    {
        $account = new Account(
            id: Uuid::uuid4()->toString(),
            userId: $command->userId,
            currency: $command->currency
        );

        $this->repository->save($account);
        return $account->getId();
    }
}
```

**Query Side (Read):**
```php
class AccountBalanceStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, ...): AccountBalanceResponse
    {
        $account = $this->repository->findById($id);

        return new AccountBalanceResponse(
            accountId: $account->getId(),
            balance: $account->getBalance()->getAmount(),
            currency: $account->getCurrency()->value,
            lastUpdated: $account->getUpdatedAt()
        );
    }
}
```

### 6. Event Sourcing

State is stored as a sequence of events:

```php
interface EventStoreInterface
{
    public function append(string $aggregateId, DomainEvent $event): void;
    public function getEventsForAggregate(string $aggregateId): array;
}

// Example: Account Created Event
class AccountCreatedEvent
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $userId,
        public readonly string $currency,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
```

**Benefits:**
- ✅ Complete audit trail
- ✅ State reconstruction at any point in time
- ✅ Event replay for debugging

---

## 📁 Project Structure

```
fn_method/
├── config/                    # Symfony configuration
│   ├── packages/
│   │   ├── api_platform.yaml  # API Platform + Swagger
│   │   ├── doctrine.yaml      # ORM configuration
│   │   └── security.yaml      # JWT authentication
│   ├── routes/
│   └── services.yaml          # DI container
│
├── src/
│   ├── Account/              # Account Bounded Context
│   │   ├── Domain/
│   │   │   ├── Entity/Account.php
│   │   │   ├── ValueObject/
│   │   │   │   ├── Currency.php
│   │   │   │   └── Money.php
│   │   │   └── Repository/
│   │   │       └── AccountRepositoryInterface.php
│   │   ├── Application/
│   │   │   ├── Command/
│   │   │   │   ├── CreateAccountCommand.php
│   │   │   │   ├── DepositMoneyCommand.php
│   │   │   │   └── WithdrawMoneyCommand.php
│   │   │   └── Handler/
│   │   └── Infrastructure/
│   │       ├── Repository/
│   │       │   └── DoctrineAccountRepository.php
│   │       └── ApiPlatform/
│   │           ├── Dto/CreateAccountDto.php
│   │           ├── StateProcessor/
│   │           └── StateProvider/
│   │
│   ├── User/                 # User Bounded Context
│   │   ├── Domain/
│   │   ├── Application/
│   │   └── Infrastructure/
│   │
│   ├── Shared/               # Shared Kernel
│   │   └── Infrastructure/
│   │       └── EventStore/
│   │
│   └── Infrastructure/       # Cross-cutting concerns
│       └── ApiPlatform/
│           └── OpenApiJwtDecorator.php
│
├── docker/                   # Docker configuration
├── migrations/               # Database migrations
├── tests/                    # Tests
├── Dockerfile               # PHP 8.3 + Xdebug
├── compose.yaml             # Main Docker Compose
├── compose.macos.yaml       # macOS optimizations
├── Makefile                 # Automation commands
└── README.md
```

---

## 🔌 API Endpoints

### Authentication

```http
POST /api/login_check
Content-Type: application/json

{
  "username": "test@example.com",
  "password": "password123"
}

Response: {"token": "eyJ0eXAiOiJKV1QiLCJhbGc..."}
```

### Account Operations

#### Create Account
```http
POST /api/accounts
Authorization: Bearer <token>
Content-Type: application/json

{
  "userId": "550e8400-e29b-41d4-a716-446655440000",
  "currency": "USD"
}
```

#### Get Account Balance
```http
GET /api/accounts/{id}
Authorization: Bearer <token>
```

#### Deposit Money
```http
PUT /api/accounts/{id}/deposit
Authorization: Bearer <token>
Content-Type: application/json

{
  "amount": "100.00",
  "currency": "USD"
}
```

#### Withdraw Money
```http
PUT /api/accounts/{id}/withdraw
Authorization: Bearer <token>
Content-Type: application/json

{
  "amount": "30.00",
  "currency": "USD"
}
```

### Swagger UI

Interactive API documentation available at:
**http://localhost:8028/api/docs**

Click **"Authorize"** button and enter:
```
Bearer <your_jwt_token>
```

---

## 🛠️ Development

### Make Commands

The project includes a comprehensive Makefile with 50+ commands:

```bash
make help          # Show all available commands
```

#### Quick Actions
```bash
make up            # Start containers
make down          # Stop containers
make restart       # Restart all services
make rebuild       # Rebuild containers from scratch
```

#### Symfony Commands
```bash
make bash                      # Enter PHP container
make cache-clear               # Clear Symfony cache
make sf CMD="list"             # Run Symfony console command
make composer-install          # Install dependencies
```

#### Database
```bash
make migrate                   # Run migrations
make db-reset                  # Reset database
make mysql                     # Enter MySQL CLI
make db-backup                 # Backup database
```

#### User & Account Management
```bash
make user-create EMAIL=user@example.com PASS=password
make jwt-generate              # Generate JWT keys
```

#### Testing
```bash
make test                      # Run all tests
make test-unit                 # Unit tests only
make test-integration          # Integration tests only
```

### Platform Support

The Makefile automatically detects your platform:

- **macOS Apple Silicon (M1/M2/M3)**: Uses optimized volumes
- **macOS Intel**: macOS-specific configuration
- **Linux/WSL**: Standard Docker setup

Check detected platform:
```bash
make info
```

### Xdebug Configuration

Xdebug 3.3.2 is pre-installed for development.

**PhpStorm Setup:**
1. Settings → PHP → Debug → Xdebug Port: `9003`
2. Settings → PHP → Servers:
   - Name: `localhost`
   - Host: `localhost`
   - Port: `8028`
   - Path mappings: `<project_root>` → `/var/www/html`

3. Click "Start Listening for PHP Debug Connections" (phone icon)

---

## 🧪 Testing

### Running Tests

```bash
# All tests
make test

# Unit tests (domain logic)
make test-unit

# Integration tests (with database)
make test-integration

# Coverage report
make test-coverage
```

### Test Structure

```
tests/
├── Unit/
│   ├── Account/
│   │   ├── Domain/
│   │   │   ├── Entity/AccountTest.php
│   │   │   └── ValueObject/MoneyTest.php
│   │   └── Application/
└── Integration/
    └── Account/
        └── Repository/
```

---

## 🔧 Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Framework** | Symfony 7.0 | HTTP/DI/Console foundation |
| **API** | API Platform 3.x | RESTful API & OpenAPI docs |
| **Language** | PHP 8.3 | Modern PHP features |
| **Database** | MySQL 8.0 | Data persistence |
| **ORM** | Doctrine ORM | Object-relational mapping |
| **Auth** | JWT (lexik/jwt) | Stateless authentication |
| **Cache** | Redis | Session & cache storage |
| **Debug** | Xdebug 3.3.2 | Step debugging |
| **Container** | Docker Compose | Development environment |
| **Mail** | Mailpit | Email testing |
| **DB Admin** | Adminer | Database management |

---

## 📖 Learning Resources

This project demonstrates:

### Domain-Driven Design
- Ubiquitous Language
- Bounded Contexts
- Aggregates & Entities
- Value Objects
- Domain Events
- Repository Pattern

### CQRS
- Command/Query separation
- Different models for reads/writes
- State Processors (Command)
- State Providers (Query)

### Event Sourcing
- Event Store implementation
- Event replay capability
- Audit trail

### Hexagonal Architecture
- Ports & Adapters pattern
- Infrastructure independence
- Testability through abstraction

### Clean Architecture
- Dependency Rule
- Layered structure
- Separation of Concerns

---

## 🤝 Contributing

This is an educational project. Feel free to:
- Fork and experiment
- Add new bounded contexts
- Implement additional patterns
- Improve documentation

---

## 📝 License

MIT License - feel free to use for learning purposes.

---

## 🎓 Author Notes

This project was created as a learning exercise to understand:
- How to structure complex business domains
- Proper separation of concerns
- Framework-independent business logic
- Event-driven architecture
- Modern PHP development practices

**Key Takeaways:**
- Domain logic should be framework-agnostic
- Bounded contexts prevent big balls of mud
- Value Objects prevent primitive obsession
- CQRS enables different optimization strategies
- Event Sourcing provides complete audit trail

---

**Happy Learning! 🚀**

For questions or discussions, please open an issue.
