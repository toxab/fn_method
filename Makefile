# Fintech DDD Project - Docker Management
# Usage: make <target>
#
# Platform detection
UNAME_S := $(shell uname -s)
UNAME_M := $(shell uname -m)

# Color output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Docker Compose files based on platform
ifeq ($(UNAME_S),Darwin)
	# macOS
	ifeq ($(UNAME_M),arm64)
		# Apple Silicon
		COMPOSE_FILES := -f compose.yaml -f compose.macos.yaml
		PLATFORM_MSG := "Apple Silicon (M1/M2/M3)"
	else
		# Intel Mac
		COMPOSE_FILES := -f compose.yaml -f compose.macos.yaml
		PLATFORM_MSG := "macOS Intel"
	endif
else
	# Linux/WSL
	COMPOSE_FILES := -f compose.yaml
	PLATFORM_MSG := "Linux/WSL"
endif

DOCKER_COMPOSE := docker compose $(COMPOSE_FILES)
DOCKER_PHP := $(DOCKER_COMPOSE) exec php
DOCKER_MYSQL := $(DOCKER_COMPOSE) exec mysql

.DEFAULT_GOAL := help

##@ General

.PHONY: help
help: ## Display this help message
	@echo "$(BLUE)Fintech DDD Project - Docker Management$(NC)"
	@echo "$(YELLOW)Platform: $(PLATFORM_MSG)$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make $(GREEN)<target>$(NC)\n"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(BLUE)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

.PHONY: info
info: ## Show platform and configuration info
	@echo "$(BLUE)Platform Information:$(NC)"
	@echo "  OS: $(UNAME_S)"
	@echo "  Architecture: $(UNAME_M)"
	@echo "  Platform: $(PLATFORM_MSG)"
	@echo "  Compose files: $(COMPOSE_FILES)"
	@echo ""
	@echo "$(BLUE)Docker Version:$(NC)"
	@docker --version
	@docker compose version

##@ Setup

.PHONY: setup
setup: ## Initial project setup
	@echo "$(BLUE)Setting up project...$(NC)"
	@if [ ! -f .env.docker.local ]; then \
		cp .env.docker .env.docker.local; \
		echo "$(GREEN)Created .env.docker.local$(NC)"; \
	fi
	@make up
	@make composer-install
	@make migrate
	@echo "$(GREEN)Setup complete!$(NC)"
	@make info-urls

.PHONY: setup-macos
setup-macos: ## Setup with macOS optimizations
	@echo "$(BLUE)Setting up for macOS...$(NC)"
	@if [ ! -f .env.docker.local ]; then \
		cp .env.docker .env.docker.local; \
		echo "MYSQL_PLATFORM=linux/arm64" >> .env.docker.local; \
		echo "MOUNT_OPTIONS=:delegated" >> .env.docker.local; \
		echo "$(GREEN)Created .env.docker.local with macOS settings$(NC)"; \
	fi
	@make setup

##@ Docker Control

.PHONY: up
up: ## Start all containers
	@echo "$(BLUE)Starting containers...$(NC)"
	@$(DOCKER_COMPOSE) up -d
	@echo "$(GREEN)Containers started!$(NC)"
	@make ps

.PHONY: down
down: ## Stop all containers
	@echo "$(YELLOW)Stopping containers...$(NC)"
	@$(DOCKER_COMPOSE) down
	@echo "$(GREEN)Containers stopped!$(NC)"

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: rebuild
rebuild: ## Rebuild and restart containers
	@echo "$(BLUE)Rebuilding containers...$(NC)"
	@$(DOCKER_COMPOSE) up -d --build
	@echo "$(GREEN)Rebuild complete!$(NC)"

.PHONY: ps
ps: ## Show container status
	@$(DOCKER_COMPOSE) ps

.PHONY: logs
logs: ## Show logs (use: make logs SERVICE=php)
	@$(DOCKER_COMPOSE) logs -f $(SERVICE)

.PHONY: clean
clean: ## Remove containers and volumes (WARNING: deletes data!)
	@echo "$(RED)This will remove all containers and data. Continue? [y/N]$(NC)" && read ans && [ $${ans:-N} = y ]
	@$(DOCKER_COMPOSE) down -v
	@echo "$(GREEN)Cleanup complete!$(NC)"

##@ PHP/Symfony

.PHONY: bash
bash: ## Enter PHP container bash
	@$(DOCKER_PHP) bash

.PHONY: composer-install
composer-install: ## Install composer dependencies
	@echo "$(BLUE)Installing composer dependencies...$(NC)"
	@$(DOCKER_PHP) composer install
	@echo "$(GREEN)Composer install complete!$(NC)"

.PHONY: composer-update
composer-update: ## Update composer dependencies
	@$(DOCKER_PHP) composer update

.PHONY: composer-require
composer-require: ## Install composer package (use: make composer-require PKG=vendor/package)
	@$(DOCKER_PHP) composer require $(PKG)

.PHONY: sf
sf: ## Run Symfony console command (use: make sf CMD="list")
	@$(DOCKER_PHP) bin/console $(CMD)

.PHONY: cache-clear
cache-clear: ## Clear Symfony cache
	@$(DOCKER_PHP) bin/console cache:clear
	@echo "$(GREEN)Cache cleared!$(NC)"

##@ Database

.PHONY: migrate
migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	@$(DOCKER_PHP) bin/console doctrine:migrations:migrate -n
	@echo "$(GREEN)Migrations complete!$(NC)"

.PHONY: migration-create
migration-create: ## Create new migration
	@$(DOCKER_PHP) bin/console doctrine:migrations:generate

.PHONY: db-create
db-create: ## Create database
	@$(DOCKER_PHP) bin/console doctrine:database:create

.PHONY: db-drop
db-drop: ## Drop database (WARNING!)
	@$(DOCKER_PHP) bin/console doctrine:database:drop --force

.PHONY: db-reset
db-reset: db-drop db-create migrate ## Reset database

.PHONY: mysql
mysql: ## Enter MySQL CLI
	@$(DOCKER_MYSQL) mysql -u fintech_user -pfintech_pass fintech_db

.PHONY: mysql-root
mysql-root: ## Enter MySQL CLI as root
	@$(DOCKER_MYSQL) mysql -u root -proot

.PHONY: db-backup
db-backup: ## Backup database to backup.sql
	@echo "$(BLUE)Backing up database...$(NC)"
	@$(DOCKER_MYSQL) mysqldump -u root -proot fintech_db > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Backup complete!$(NC)"

.PHONY: db-restore
db-restore: ## Restore database from backup.sql
	@echo "$(YELLOW)Restoring database from backup.sql...$(NC)"
	@$(DOCKER_COMPOSE) exec -T mysql mysql -u root -proot fintech_db < backup.sql
	@echo "$(GREEN)Restore complete!$(NC)"

##@ Testing

.PHONY: test
test: ## Run all tests
	@echo "$(BLUE)Running tests...$(NC)"
	@$(DOCKER_PHP) bin/phpunit
	@echo "$(GREEN)Tests complete!$(NC)"

.PHONY: test-unit
test-unit: ## Run unit tests
	@$(DOCKER_PHP) bin/phpunit --testsuite=unit

.PHONY: test-integration
test-integration: ## Run integration tests
	@$(DOCKER_PHP) bin/phpunit --testsuite=integration

.PHONY: test-coverage
test-coverage: ## Run tests with coverage
	@$(DOCKER_PHP) bin/phpunit --coverage-html var/coverage

##@ Event Sourcing

.PHONY: es-test
es-test: ## Test Event Sourcing implementation
	@echo "$(BLUE)Testing Event Sourcing...$(NC)"
	@$(DOCKER_PHP) bin/console app:test-event-sourcing

.PHONY: user-create
user-create: ## Create user (use: make user-create EMAIL=test@example.com PASS=password)
	@$(DOCKER_PHP) bin/console app:create-user $(EMAIL) $(PASS)

.PHONY: account-balance
account-balance: ## Get account balance (use: make account-balance ID=account-id)
	@$(DOCKER_PHP) bin/console app:get-account-balance $(ID)

.PHONY: deposit
deposit: ## Deposit money (use: make deposit ID=account-id AMOUNT=100.00 CURRENCY=USD)
	@$(DOCKER_PHP) bin/console app:deposit-money $(ID) $(AMOUNT) $(CURRENCY)

.PHONY: withdraw
withdraw: ## Withdraw money (use: make withdraw ID=account-id AMOUNT=50.00 CURRENCY=USD)
	@$(DOCKER_PHP) bin/console app:withdraw-money $(ID) $(AMOUNT) $(CURRENCY)

##@ Code Quality

.PHONY: cs-check
cs-check: ## Check coding standards
	@$(DOCKER_PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: cs-fix
cs-fix: ## Fix coding standards
	@$(DOCKER_PHP) vendor/bin/php-cs-fixer fix

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	@$(DOCKER_PHP) vendor/bin/phpstan analyse

##@ Monitoring

.PHONY: health
health: ## Check services health
	@echo "$(BLUE)Services Health Status:$(NC)"
	@$(DOCKER_COMPOSE) ps

.PHONY: top
top: ## Show running processes in containers
	@$(DOCKER_COMPOSE) top

.PHONY: stats
stats: ## Show container resource usage
	@docker stats --no-stream $$(docker compose ps -q)

##@ Information

.PHONY: info-urls
info-urls: ## Show all service URLs
	@echo "$(BLUE)Service URLs:$(NC)"
	@echo "  $(GREEN)API:$(NC)          http://localhost:8028"
	@echo "  $(GREEN)API Docs:$(NC)     http://localhost:8028/api"
	@echo "  $(GREEN)Adminer:$(NC)      http://localhost:8080"
	@echo "  $(GREEN)Mailpit:$(NC)      http://localhost:8025"
	@echo ""
	@echo "$(BLUE)Database Connection:$(NC)"
	@echo "  Host:     localhost"
	@echo "  Port:     3327"
	@echo "  Database: fintech_db"
	@echo "  User:     fintech_user"
	@echo "  Password: fintech_pass"

.PHONY: info-platform
info-platform: ## Show platform-specific information
	@echo "$(BLUE)Platform: $(PLATFORM_MSG)$(NC)"
	@echo ""
	@echo "Compose files:"
	@echo "  $(COMPOSE_FILES)"
	@echo ""
	@echo "Recommendations:"
ifeq ($(UNAME_S),Darwin)
	@echo "  - Use 'make setup-macos' for initial setup"
	@echo "  - Vendor directory is in named volume for performance"
	@echo "  - Using delegated mount option for better I/O"
else
	@echo "  - Use 'make setup' for initial setup"
	@echo "  - Standard volume mounts for best performance"
endif

##@ Production

.PHONY: prod-up
prod-up: ## Start in production mode
	@echo "$(YELLOW)Starting in PRODUCTION mode...$(NC)"
	@docker compose -f compose.yaml -f compose.prod.yaml up -d
	@echo "$(GREEN)Production containers started!$(NC)"

.PHONY: prod-down
prod-down: ## Stop production containers
	@docker compose -f compose.yaml -f compose.prod.yaml down
