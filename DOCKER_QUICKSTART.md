# Docker Quick Start 🚀

## Швидкий запуск за 3 хвилини

### 1️⃣ Вибрати вашу платформу:

#### 🐧 Linux / Windows WSL2:
```bash
make setup
```

#### 🍎 macOS (Intel або Apple Silicon):
```bash
make setup-macos
```

### 2️⃣ Дочекатись завершення:
```bash
# Встановляться залежності, запустяться міграції
# Зачекайте ~2-3 хвилини
```

### 3️⃣ Готово! Відкрити в браузері:
- **API**: http://localhost:8028/api
- **Adminer** (БД): http://localhost:8080
- **Mailpit** (Email): http://localhost:8025

---

## Корисні команди

```bash
make help          # Показати всі команди
make ps            # Статус контейнерів
make logs          # Дивитись логи
make bash          # Увійти в PHP контейнер
make es-test       # Тестувати Event Sourcing
```

## Тестування Event Sourcing

```bash
# Запустити демо Event Sourcing
make es-test

# Або вручну:
docker compose exec php bin/console app:test-event-sourcing
```

## Підключення до БД

### Через Adminer (в браузері):
1. Відкрити http://localhost:8080
2. Ввести:
   - **Server**: `mysql`
   - **Username**: `fintech_user`
   - **Password**: `fintech_pass`
   - **Database**: `fintech_db`

### Через CLI:
```bash
make mysql
# Або:
docker compose exec mysql mysql -u fintech_user -pfintech_pass fintech_db
```

### Через TablePlus/DBeaver/etc:
- **Host**: `localhost`
- **Port**: `3327`
- **User**: `fintech_user`
- **Password**: `fintech_pass`
- **Database**: `fintech_db`

## API Testing

### Через swagger UI:
Відкрити: http://localhost:8028/api

### Через curl:
```bash
# Health check
curl http://localhost:8028/health

# API documentation
curl http://localhost:8028/api/docs.json
```

## Проблеми?

### Порти зайняті?
```bash
# Змінити порти
echo "NGINX_PORT=8029" >> .env.docker.local
echo "MYSQL_PORT=3328" >> .env.docker.local
make restart
```

### Повільно на macOS?
```bash
# Переконайтесь що використовуєте macOS версію:
make setup-macos
```

### Почати спочатку?
```bash
make down
make clean  # WARNING: видаляє дані!
make setup
```

## Наступні кроки

📖 **Детальна документація**: [DOCKER.md](DOCKER.md)
🔄 **Що нового**: [DOCKER_CHANGELOG.md](DOCKER_CHANGELOG.md)
🏗️ **Архітектура**: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

## Команди для розробки

```bash
# Запустити тести
make test

# Очистити кеш
make cache-clear

# Створити міграцію
make migration-create

# Запустити міграції
make migrate

# Бекап БД
make db-backup

# Перевірити код
make cs-check

# Виправити код
make cs-fix
```

## macOS specific

Якщо ви на macOS, Makefile автоматично:
- Визначить вашу архітектуру (Intel vs Apple Silicon)
- Використає оптимальні mount options
- Створить named volumes для vendor/var
- Налаштує правильний MySQL образ

## Production

```bash
# Запустити в production режимі
make prod-up

# Або вручну:
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

---

**Все працює?** Чудово! Переходьте до розробки 🎉

**Є питання?** Дивіться [DOCKER.md](DOCKER.md) або `make help`
