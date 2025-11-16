# 📝 Practice Tasks for Learning DDD

Ці завдання допоможуть тобі закріпити знання про DDD, CQRS, Event Sourcing та інші паттерни, які ми реалізували в проекті.

**Складність:** 🟢 Легко | 🟡 Середньо | 🔴 Складно

---

## 🟢 Завдання 1: Додати Unit тести для EventSourcedAccount

**Мета:** Навчитися тестувати event-sourced aggregates

**Що зробити:**
1. Створи файл `tests/Unit/Account/Domain/Entity/EventSourcedAccountTest.php`
2. Напиши тести для:
   - ✅ Створення event-sourced account
   - ✅ Deposit створює MoneyDepositedEvent
   - ✅ Withdraw створює MoneyWithdrawnEvent
   - ✅ Reconstitute відновлює aggregate з events
   - ✅ Перевірка uncommitted events

**Підказка:**
```php
public function testCreateAccountRecordsEvent(): void
{
    $account = EventSourcedAccount::create('acc-1', 'user-1', Currency::UAH);

    $events = $account->getUncommittedEvents();

    $this->assertCount(1, $events);
    $this->assertInstanceOf(AccountCreatedEvent::class, $events[0]);
}
```

**Перевірка:** `make test`

---

## 🟢 Завдання 2: Додати валідацію в DTOs

**Мета:** Навчитися додавати Symfony Constraints для валідації HTTP input

**Що зробити:**
1. Відкрий `src/Account/Infrastructure/ApiPlatform/Dto/CreateAccountDto.php`
2. Додай Symfony Constraints:
   - `userId` - має бути UUID
   - `currency` - має бути один з: UAH, USD

**Підказка:**
```php
use Symfony\Component\Validator\Constraints as Assert;

class CreateAccountDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $userId;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['UAH', 'USD'])]
    public string $currency;
}
```

**Перевірка:** Спробуй відправити невалідні дані через API

---

## 🟡 Завдання 3: Додати новий Domain Exception

**Мета:** Практика створення domain exceptions з static factory methods

**Що зробити:**
1. Створи `src/Account/Domain/Exception/InvalidAmountException.php`
2. Додай static factory method: `public static function tooSmall(string $amount, string $minimum): self`
3. Використай в Account::deposit() щоб перевіряти мінімальну суму (наприклад, 0.01)

**Підказка:**
```php
class InvalidAmountException extends DomainException
{
    public static function tooSmall(string $amount, string $minimum): self
    {
        return new self(
            sprintf('Amount %s is too small. Minimum: %s', $amount, $minimum)
        );
    }
}
```

**Перевірка:** Спробуй deposit 0.00 - має кинути exception

---

## 🟡 Завдання 4: Додати Command Handler з валідацією

**Мета:** Навчитися додавати бізнес-правила в handler

**Що зробити:**
1. Оновити `CreateAccountHandler`
2. Додай перевірку: чи існує вже account для цього user+currency
3. Якщо так - кинути `AccountAlreadyExistsException`

**Підказка:**
```php
public function handle(CreateAccountCommand $command): string
{
    $existingAccount = $this->accountRepository->findByUserIdAndCurrency(
        $command->getUserId(),
        $command->getCurrency()
    );

    if ($existingAccount) {
        throw AccountAlreadyExistsException::forUserAndCurrency(
            $command->getUserId(),
            $command->getCurrency()
        );
    }

    // ... створення account
}
```

**Перевірка:** Спробуй створити 2 accounts з однаковим user+currency

---

## 🟡 Завдання 5: Додати новий Value Object - TransactionId

**Мета:** Практика створення Value Objects

**Що зробити:**
1. Створи `src/Shared/Domain/ValueObject/TransactionId.php`
2. Має генерувати UUID v4
3. Має валідувати що це дійсно UUID
4. Додай метод `toString()`

**Підказка:**
```php
class TransactionId
{
    private string $value;

    private function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid Transaction ID');
        }

        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
```

**Бонус:** Напиши Unit тест для цього Value Object

---

## 🔴 Завдання 6: Створити новий Bounded Context - Transaction

**Мета:** Практика створення повного Bounded Context

**Що зробити:**
1. Створи структуру:
```
src/Transaction/
├── Domain/
│   ├── Entity/Transaction.php
│   ├── ValueObject/TransactionStatus.php
│   ├── Repository/TransactionRepositoryInterface.php
│   └── Event/TransactionCreatedEvent.php
├── Application/
│   ├── Command/CreateTransactionCommand.php
│   └── Handler/CreateTransactionHandler.php
└── Infrastructure/
    └── Repository/DoctrineTransactionRepository.php
```

2. Transaction має містити:
   - `id` (string/UUID)
   - `fromAccountId` (string)
   - `toAccountId` (string)
   - `amount` (Money)
   - `status` (TransactionStatus enum: PENDING, COMPLETED, FAILED)
   - `createdAt` (DateTimeImmutable)

3. Створи enum `TransactionStatus`

4. Створи Doctrine entity mapping

**Підказка для Transaction Entity:**
```php
class Transaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $fromAccountId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $toAccountId;

    // ... інші поля

    public function __construct(
        string $id,
        string $fromAccountId,
        string $toAccountId,
        Money $amount
    ) {
        $this->id = $id;
        $this->fromAccountId = $fromAccountId;
        $this->toAccountId = $toAccountId;
        $this->amount = $amount;
        $this->status = TransactionStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== TransactionStatus::PENDING) {
            throw new \DomainException('Transaction is not pending');
        }

        $this->status = TransactionStatus::COMPLETED;
    }
}
```

**Перевірка:** Створи migration і перевір що таблиця створюється

---

## 🔴 Завдання 7: Додати Integration тест для Repository

**Мета:** Навчитися писати integration тести з реальною БД

**Що зробити:**
1. Створи `tests/Integration/Account/Infrastructure/Repository/DoctrineAccountRepositoryTest.php`
2. Використай `KernelTestCase` від Symfony
3. Напиши тести:
   - ✅ save() зберігає account в БД
   - ✅ findById() знаходить account
   - ✅ findByUserIdAndCurrency() працює коректно
   - ✅ findByUserId() повертає всі accounts користувача

**Підказка:**
```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineAccountRepositoryTest extends KernelTestCase
{
    private AccountRepositoryInterface $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->repository = $container->get(AccountRepositoryInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        // Clear database
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function testSaveAndFindById(): void
    {
        $account = new Account('test-id', 'user-id', Currency::UAH);

        $this->repository->save($account);

        $found = $this->repository->findById('test-id');

        $this->assertNotNull($found);
        $this->assertEquals('test-id', $found->getId());
    }
}
```

**Перевірка:** `make test-integration`

---

## 🔴 Завдання 8: Реалізувати Read Model для CQRS

**Мета:** Практика створення denormalized read models

**Що зробити:**
1. Створи таблицю `account_balance_read_model`:
```sql
CREATE TABLE account_balance_read_model (
    account_id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    last_updated DATETIME NOT NULL,
    INDEX idx_user_id (user_id)
);
```

2. Створи `AccountBalanceReadModel` entity (без business logic)

3. Створи Doctrine Repository для read model

4. Оновлюй read model кожного разу коли deposit/withdraw

**Підказка:**
```php
class AccountBalanceReadModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $accountId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $userId;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $balance;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastUpdated;

    // Getters only, no business logic!
}
```

**Перевірка:** Порівняй швидкість запитів на write model vs read model

---

## 🏆 Бонусне завдання: Event-Driven Notification

**Мета:** Практика event listeners

**Що зробити:**
1. Створи Event Listener який слухає `MoneyDepositedEvent`
2. Коли хтось робить deposit > 1000 - надішли email (через Mailpit)
3. Використай Symfony Event Dispatcher

**Підказка:**
```php
#[AsEventListener(event: MoneyDepositedEvent::class)]
class LargeDepositNotificationListener
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(MoneyDepositedEvent $event): void
    {
        if (bccomp($event->getAmount(), '1000.00', 2) <= 0) {
            return; // Skip small deposits
        }

        $user = $this->userRepository->findById($event->getUserId());

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Large Deposit Detected')
            ->text(sprintf(
                'You deposited %s %s',
                $event->getAmount(),
                $event->getCurrency()
            ));

        $this->mailer->send($email);
    }
}
```

**Перевірка:** Зроби deposit > 1000 і перевір Mailpit (http://localhost:8025)

---

## 📚 Корисні команди

```bash
# Запустити тести
make test
make test-unit
make test-integration

# Створити migration
docker compose exec php bin/console make:migration

# Запустити migrations
make migrate

# Очистити cache
make cache-clear

# Перевірити coding standards
docker compose exec php vendor/bin/phpcs

# Fix coding standards
docker compose exec php vendor/bin/phpcbf
```

---

## 🎯 Критерії успіху

Після виконання завдань ти зможеш:

✅ Писати Unit тести для Value Objects і Entities
✅ Створювати Domain Exceptions з factory methods
✅ Додавати валідацію на різних рівнях
✅ Створювати нові Bounded Contexts
✅ Писати Integration тести
✅ Реалізовувати CQRS Read Models
✅ Використовувати Domain Events для side effects

---

**Успіхів! Якщо застряг - дивись на існуючий код як на приклад.** 🚀
