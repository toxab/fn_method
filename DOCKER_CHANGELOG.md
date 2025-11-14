# Docker Configuration Updates

## Нові файли

### Основні конфігурації:
- **compose.yaml** - Нова основна конфігурація (замість docker-compose.yaml)
  - Підтримка багатьох платформ (Linux, macOS Intel, macOS Apple Silicon)
  - Health checks для всіх сервісів
  - Оптимізовані volumes
  - Додано Redis та Adminer

- **compose.override.yaml** - Development оверрайди (автоматично)
  - Xdebug налаштування
  - Development-friendly ports
  - Verbose logging

- **compose.macos.yaml** - Спеціальні оптимізації для macOS
  - Delegated/cached mount options для кращої I/O
  - Named volumes для vendor/var
  - Platform-specific settings для Apple Silicon

- **compose.prod.yaml** - Production конфігурація
  - Вимкнено development tools
  - Оптимізовано для продуктивності
  - Посилена безпека

### Конфігураційні файли:
- **.env.docker** - Template для environment variables
- **docker/mysql/my.cnf** - MySQL оптимізації
- **DOCKER.md** - Повна документація
- **Makefile** - Зручні команди для роботи

## Ключові покращення

### 1. Підтримка платформ
```yaml
# Автоматичне визначення платформи
platform: ${MYSQL_PLATFORM:-linux/amd64}

# Mount options для різних OS
volumes:
  - .:/var/www/html${MOUNT_OPTIONS:-}
```

### 2. Health Checks
Всі сервіси тепер мають health checks:
- PHP-FPM
- Nginx
- MySQL
- Redis
- Mailpit

### 3. Нові сервіси
- **Redis** - для кешування та сесій
- **Adminer** - GUI для управління БД
- **Mailpit** - тестування email (вже було, оновлено)

### 4. Performance оптимізації

#### macOS:
- Named volumes для vendor та var
- Delegated/cached mount options
- Platform-specific MySQL образи

#### Загальні:
- Оптимізовані MySQL settings
- OPcache в PHP
- Redis для швидкого кешу

### 5. Developer Experience

#### Makefile команди:
```bash
make setup          # Повне налаштування
make setup-macos    # Налаштування для macOS
make up             # Запустити
make down           # Зупинити
make logs           # Дивитись логи
make bash           # Увійти в PHP контейнер
make migrate        # Запустити міграції
make test           # Запустити тести
make es-test        # Тестувати Event Sourcing
```

#### Автоматичне визначення платформи:
Makefile автоматично визначає вашу платформу та підключає потрібні compose файли.

## Як користуватись

### Linux / WSL2:
```bash
make setup
```

### macOS Intel:
```bash
make setup-macos
# Або вручну:
docker compose -f compose.yaml -f compose.macos.yaml up -d
```

### macOS Apple Silicon (M1/M2/M3):
```bash
make setup-macos
# Або вручну:
docker compose -f compose.yaml -f compose.macos.yaml up -d
```

### Production:
```bash
make prod-up
# Або вручну:
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

## Міграція зі старої конфігурації

### Якщо у вас працює старий docker-compose.yaml:

1. **Зупинити старі контейнери:**
   ```bash
   docker compose down
   ```

2. **Backup даних (опціонально):**
   ```bash
   docker compose exec mysql mysqldump -u root -proot fintech_db > backup.sql
   ```

3. **Використати нову конфігурацію:**
   ```bash
   # Для macOS:
   make setup-macos

   # Для Linux:
   make setup
   ```

4. **Відновити дані (якщо потрібно):**
   ```bash
   make db-restore
   ```

### Volumes зберігаються!
Названі volumes (mysql_data) залишаться, тому дані не втратяться.

## Environment Variables

Створіть `.env.docker.local` з вашими налаштуваннями:

```bash
# Для macOS Intel:
MYSQL_PLATFORM=linux/amd64
MOUNT_OPTIONS=:cached

# Для macOS Apple Silicon:
MYSQL_PLATFORM=linux/arm64
MOUNT_OPTIONS=:delegated

# Для Linux:
MYSQL_PLATFORM=linux/amd64
MOUNT_OPTIONS=

# Development:
XDEBUG_MODE=develop,debug

# Production:
XDEBUG_MODE=off
```

## Troubleshooting

### "Port already in use"
Змініть порти в `.env.docker.local`:
```bash
NGINX_PORT=8029
MYSQL_PORT=3328
```

### Повільна робота на macOS
```bash
# Переконайтесь що використовуєте macOS файл:
docker compose -f compose.yaml -f compose.macos.yaml up -d

# Або через Makefile (автоматично):
make up
```

### Permission errors
```bash
docker compose exec php chown -R www-data:www-data /var/www/html/var
```

## Що далі?

1. Прочитайте [DOCKER.md](DOCKER.md) для повної документації
2. Використовуйте `make help` для списку всіх команд
3. Налаштуйте Xdebug у вашій IDE (інструкції в DOCKER.md)

## Питання?

Дивіться:
- [DOCKER.md](DOCKER.md) - повна документація
- `make help` - список команд
- `make info` - інформація про платформу
