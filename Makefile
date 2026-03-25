# =============================================================================
# Bulletin - Developer Commands
# =============================================================================

COMPOSE_DEV  = docker compose -f docker-compose.yml -f docker-compose.dev.yml
COMPOSE_PROD = docker compose -f docker-compose.yml -f docker-compose.prod.yml

.DEFAULT_GOAL := help

# -----------------------------------------------------------------------------
# Environment
# -----------------------------------------------------------------------------

.PHONY: dev
dev: ## Start development environment
	$(COMPOSE_DEV) up -d
	@echo "\n  Bulletin dev environment is running:"
	@echo "  - App:     http://localhost"
	@echo "  - Mailpit: http://localhost:8025"
	@echo "  - DB:      localhost:5432\n"

.PHONY: prod
prod: ## Start production environment
	$(COMPOSE_PROD) up -d

.PHONY: down
down: ## Stop all containers
	$(COMPOSE_DEV) down
	$(COMPOSE_PROD) down 2>/dev/null || true

.PHONY: build
build: ## Build all containers (dev)
	$(COMPOSE_DEV) build

.PHONY: rebuild
rebuild: ## Rebuild all containers from scratch (no cache)
	$(COMPOSE_DEV) build --no-cache

.PHONY: restart
restart: ## Restart all services
	$(COMPOSE_DEV) restart

# -----------------------------------------------------------------------------
# Logs & Shell
# -----------------------------------------------------------------------------

.PHONY: logs
logs: ## Tail logs for all services
	$(COMPOSE_DEV) logs -f

.PHONY: logs-api
logs-api: ## Tail logs for Symfony API
	$(COMPOSE_DEV) logs -f symfony-api

.PHONY: logs-front
logs-front: ## Tail logs for React frontend
	$(COMPOSE_DEV) logs -f react-app

.PHONY: shell-api
shell-api: ## Open shell in Symfony API container
	$(COMPOSE_DEV) exec symfony-api sh

.PHONY: shell-front
shell-front: ## Open shell in React frontend container
	$(COMPOSE_DEV) exec react-app sh

# -----------------------------------------------------------------------------
# Database
# -----------------------------------------------------------------------------

.PHONY: db-migrate
db-migrate: ## Run database migrations
	$(COMPOSE_DEV) exec symfony-api php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: db-fixtures
db-fixtures: ## Load database fixtures
	$(COMPOSE_DEV) exec symfony-api php bin/console doctrine:fixtures:load --no-interaction

.PHONY: db-reset
db-reset: ## Drop, create and migrate database
	$(COMPOSE_DEV) exec symfony-api php bin/console doctrine:database:drop --force --if-exists
	$(COMPOSE_DEV) exec symfony-api php bin/console doctrine:database:create
	$(COMPOSE_DEV) exec symfony-api php bin/console doctrine:migrations:migrate --no-interaction

# -----------------------------------------------------------------------------
# Testing & Quality
# -----------------------------------------------------------------------------

.PHONY: test
test: test-api test-front ## Run all tests

.PHONY: test-api
test-api: ## Run PHPUnit tests
	$(COMPOSE_DEV) exec symfony-api php bin/phpunit

.PHONY: test-front
test-front: ## Run Vitest tests
	$(COMPOSE_DEV) exec react-app npm test -- --run

.PHONY: lint
lint: lint-api lint-front ## Run all linters

.PHONY: lint-api
lint-api: ## Run PHP CS Fixer and PHPStan
	$(COMPOSE_DEV) exec symfony-api vendor/bin/php-cs-fixer fix --dry-run --diff
	$(COMPOSE_DEV) exec symfony-api vendor/bin/phpstan analyse

.PHONY: lint-front
lint-front: ## Run ESLint and TypeScript check
	$(COMPOSE_DEV) exec react-app npm run lint
	$(COMPOSE_DEV) exec react-app npx tsc --noEmit

# -----------------------------------------------------------------------------
# Utilities
# -----------------------------------------------------------------------------

.PHONY: fresh
fresh: ## Clean install - remove volumes and rebuild everything
	$(COMPOSE_DEV) down -v --remove-orphans
	$(COMPOSE_DEV) build --no-cache
	$(COMPOSE_DEV) up -d
	@echo "Waiting for services to be ready..."
	@sleep 5
	$(COMPOSE_DEV) exec symfony-api php bin/console doctrine:migrations:migrate --no-interaction
	@echo "\n  Fresh environment ready!\n"

.PHONY: status
status: ## Show status of all services
	$(COMPOSE_DEV) ps

.PHONY: help
help: ## Show this help message
	@echo "Bulletin - Available Commands:\n"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
	@echo ""
