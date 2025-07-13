# Fintech DDD Learning App

Educational skeleton demonstrating Domain-Driven Design (DDD), CQRS, Event Sourcing, and Hexagonal Architecture using Symfony 7.

## Quick Start

1. **Start Docker Environment**
   ```bash
   docker-compose up -d
   ```

2. **Install Dependencies**
   ```bash
   docker-compose exec php composer install
   ```

3. **Generate JWT Keys**
   ```bash
   docker-compose exec php mkdir -p config/jwt
   docker-compose exec php openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:your-passphrase
   docker-compose exec php openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your-passphrase
   ```

4. **Run Migrations**
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate
   ```

5. **Access Application**
   - API: http://localhost/api
   - API Documentation: http://localhost/api/docs

## Architecture

### Bounded Contexts
- **Account**: Account management, deposits, withdrawals, transfers
- **User**: Authentication, user management
- **Shared**: Common interfaces and domain events

### Technology Stack
- PHP 8.4 with Symfony 7
- API Platform for REST API
- MySQL 8.0 with composite indexes
- JWT Authentication
- Docker containerization

### Key Features
- Multi-currency support (UAH, USD)
- Event sourcing implementation
- CQRS command/query separation
- Hexagonal architecture
- Domain-driven design patterns

## Development Commands

```bash
# Start development environment
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Run migrations
docker-compose exec php php bin/console doctrine:migrations:migrate

# Create new migration
docker-compose exec php php bin/console doctrine:migrations:generate

# Load fixtures (when available)
docker-compose exec php php bin/console doctrine:fixtures:load
```