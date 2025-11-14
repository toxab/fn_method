# Docker Setup Guide

Повний гайд по запуску проекту через Docker на різних платформах.

## Швидкий старт

### Linux / Windows WSL2
```bash
# 1. Склонувати репозиторій
git clone <your-repo-url>
cd fn_method

# 2. Скопіювати env файл
cp .env.docker .env.docker.local

# 3. Запустити контейнери
docker compose up -d

# 4. Встановити залежності
docker compose exec php composer install

# 5. Запустити міграції
docker compose exec php bin/console doctrine:migrations:migrate -n

# 6. Готово!
# API: http://localhost:8028
# Adminer: http://localhost:8080
# Mailpit: http://localhost:8025
```

### macOS (Intel)
```bash
# 1-2. Те саме що вище

# 3. Відредагувати .env.docker.local
# Розкоментувати для macOS Intel:
MYSQL_PLATFORM=linux/amd64
MOUNT_OPTIONS=:cached

# 4. Запустити з macOS оптимізаціями
docker compose -f compose.yaml -f compose.macos.yaml up -d

# 5-6. Те саме що вище
```

### macOS (Apple Silicon M1/M2/M3)
```bash
# 1-2. Те саме

# 3. Відредагувати .env.docker.local
# Розкоментувати для Apple Silicon:
MYSQL_PLATFORM=linux/arm64
MOUNT_OPTIONS=:delegated

# 4. Запустити з macOS оптимізаціями
docker compose -f compose.yaml -f compose.macos.yaml up -d

# 5-6. Те саме
```

## Структура файлів

```
├── compose.yaml              # Основний файл (базова конфігурація)
├── compose.override.yaml     # Development overrides (автоматично підключається)
├── compose.macos.yaml        # macOS оптимізації
├── compose.prod.yaml         # Production конфігурація
├── .env.docker               # Змінні середовища (template)
├── .env.docker.local         # Ваші локальні налаштування (git ignored)
└── docker/
    ├── nginx/
    │   └── default.conf      # Nginx конфігурація
    ├── mysql/
    │   └── my.cnf            # MySQL конфігурація
    └── php/
        └── php.ini           # PHP конфігурація
```

## Доступні сервіси

| Сервіс   | Порт  | URL                        | Опис                          |
|----------|-------|----------------------------|-------------------------------|
| Nginx    | 8028  | http://localhost:8028      | Веб-сервер                    |
| MySQL    | 3327  | localhost:3327             | База даних                    |
| Redis    | 6379  | localhost:6379             | Кеш/сесії                     |
| Adminer  | 8080  | http://localhost:8080      | Управління БД                 |
| Mailpit  | 8025  | http://localhost:8025      | Email testing                 |
| Xdebug   | 9003  | localhost:9003             | Debugging                     |

## Корисні команди

### Управління контейнерами
```bash
# Запустити всі сервіси
docker compose up -d

# Зупинити всі сервіси
docker compose down

# Перезапустити конкретний сервіс
docker compose restart php

# Подивитись логи
docker compose logs -f php

# Подивитись статус
docker compose ps

# Повністю очистити (включно з volumes)
docker compose down -v
```

### Робота з PHP
```bash
# Виконати команду в PHP контейнері
docker compose exec php bash

# Composer
docker compose exec php composer install
docker compose exec php composer update
docker compose exec php composer require vendor/package

# Symfony Console
docker compose exec php bin/console list
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console cache:clear

# Тести
docker compose exec php bin/phpunit
```

### Робота з БД
```bash
# Підключитись до MySQL
docker compose exec mysql mysql -u fintech_user -pfintech_pass fintech_db

# Експорт БД
docker compose exec mysql mysqldump -u root -proot fintech_db > backup.sql

# Імпорт БД
docker compose exec -T mysql mysql -u root -proot fintech_db < backup.sql

# Через Adminer
# Відкрити http://localhost:8080
# Server: mysql
# Username: fintech_user
# Password: fintech_pass
# Database: fintech_db
```

### Event Sourcing команди
```bash
# Тестування Event Sourcing
docker compose exec php bin/console app:test-event-sourcing

# Створити користувача
docker compose exec php bin/console app:create-user user@example.com password

# Операції з рахунком
docker compose exec php bin/console app:deposit-money <account-id> 100.00 USD
docker compose exec php bin/console app:withdraw-money <account-id> 50.00 USD
docker compose exec php bin/console app:get-account-balance <account-id>
```

## Різні середовища

### Development (за замовчуванням)
```bash
docker compose up -d
# Включено: Xdebug, Adminer, Mailpit, verbose logging
```

### Production
```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
# Виключено: Xdebug, development tools
# Оптимізовано для продуктивності
```

### macOS оптимізації
```bash
docker compose -f compose.yaml -f compose.macos.yaml up -d
# Використовує delegated/cached mount options
# Named volumes для vendor та var
```

## Налаштування Xdebug

### PhpStorm / IntelliJ IDEA

1. **Settings → PHP → Servers**
   - Name: `fintech`
   - Host: `localhost`
   - Port: `8028`
   - Debugger: `Xdebug`
   - Path mappings: `/path/to/project` → `/var/www/html`

2. **Settings → PHP → Debug**
   - Xdebug port: `9003`

3. **Enable listening**
   - Клікнути на іконку телефону в тулбарі

4. **Запустити Xdebug**
```bash
# В .env.docker.local встановити:
XDEBUG_MODE=develop,debug

# Перезапустити контейнер
docker compose restart php
```

### VS Code

Додати в `.vscode/launch.json`:
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

## Performance Tips

### macOS
1. Використовуйте `compose.macos.yaml`
2. Vendor та var в named volumes (вже налаштовано)
3. Розгляньте VirtioFS в Docker Desktop settings

### Загальні
1. Виключайте Xdebug коли не користуєтесь: `XDEBUG_MODE=off`
2. Обмежте ресурси в Docker Desktop (4GB RAM, 2 CPU зазвичай достатньо)
3. Періодично чистіть: `docker system prune -a`

## Troubleshooting

### Порти зайняті
```bash
# Змініть порти в .env.docker.local
NGINX_PORT=8029
MYSQL_PORT=3328
```

### Permission denied (macOS/Linux)
```bash
# Додайте свого user в www-data group (Linux)
sudo usermod -aG www-data $USER

# Або змініть права
docker compose exec php chown -R www-data:www-data /var/www/html/var
```

### MySQL connection refused
```bash
# Дочекайтеся health check
docker compose ps

# Або подивіться логи
docker compose logs mysql
```

### Повільна робота на macOS
```bash
# Переконайтесь що використовуєте compose.macos.yaml
docker compose -f compose.yaml -f compose.macos.yaml up -d

# Перевірте що vendor в named volume
docker compose exec php ls -la /var/www/html/vendor
```

### Очистити все і почати спочатку
```bash
docker compose down -v
docker volume prune
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate -n
```

## API Testing

### Через curl
```bash
# Health check
curl http://localhost:8028/health

# API Docs
curl http://localhost:8028/api/docs

# Створити рахунок (потрібна автентифікація)
curl -X POST http://localhost:8028/api/accounts \
  -H "Content-Type: application/json" \
  -d '{"userId": "user-123", "currency": "USD"}'
```

### Через API Platform UI
Відкрити в браузері: http://localhost:8028/api

## Health Checks

Всі сервіси мають health checks:
```bash
# Перевірити статус
docker compose ps

# Детальна інформація
docker inspect fintech_php | grep -A 10 Health
```

## Backup & Restore

### Backup
```bash
# MySQL
docker compose exec mysql mysqldump -u root -proot fintech_db > backup.sql

# Event Store
docker compose exec mysql mysqldump -u root -proot fintech_db event_store > events_backup.sql

# Volumes
docker run --rm -v fintech_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_data.tar.gz /data
```

### Restore
```bash
# MySQL
docker compose exec -T mysql mysql -u root -proot fintech_db < backup.sql

# Volume
docker run --rm -v fintech_mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/mysql_data.tar.gz -C /
```

## Production Checklist

- [ ] Змінити паролі в `.env.docker.local`
- [ ] Використовувати `compose.prod.yaml`
- [ ] Вимкнути Xdebug: `XDEBUG_MODE=off`
- [ ] Налаштувати HTTPS (reverse proxy)
- [ ] Закрити порти БД ззовні
- [ ] Налаштувати backups
- [ ] Налаштувати моніторинг
- [ ] Налаштувати логи (ELK stack)
- [ ] Перевірити security headers
- [ ] Налаштувати rate limiting

## Ресурси

- [Docker Compose Docs](https://docs.docker.com/compose/)
- [Symfony Docker Best Practices](https://symfony.com/doc/current/setup/docker.html)
- [macOS Docker Performance](https://docs.docker.com/desktop/mac/)
