up:
	docker compose up -d

# Generate IDE helper files locally (runs migrations if needed)
ide:
	cd app && \
		php -r "file_exists('.env') || copy('.env.example', '.env');" && \
		php artisan key:generate --force && \
		mkdir -p database && \
		( grep -q "^DB_CONNECTION=sqlite" .env && touch database/database.sqlite || true ) && \
		php artisan migrate --force && \
		php artisan ide-helper:generate && \
		php artisan ide-helper:models --write --reset -N && \
		php artisan ide-helper:meta

# Generate IDE helper files inside Docker php container
ide-docker:
	docker compose up -d php && \
	docker compose exec php sh -lc '\\
		php -r "file_exists(\''\''.env\''\'') || copy(\''\''.env.example\''\'', \''\''.env\''\'');" && \\
		php artisan key:generate --force && \\
		mkdir -p database && \\
		if grep -q "^DB_CONNECTION=sqlite" .env; then touch database/database.sqlite; fi && \\
		php artisan migrate --force && \\
		php artisan ide-helper:generate && \\
		php artisan ide-helper:models --write --reset -N && \\
		php artisan ide-helper:meta'

# Run test suite in Docker against postgres_test
test-docker:
	docker compose up -d postgres_test php && \
	until docker compose exec -T postgres_test pg_isready -U dmcrm_test -d dmcrm_test > /dev/null 2>&1; do \
		echo 'waiting for postgres_test...'; sleep 1; \
	done && \
	docker compose exec php sh -lc 'composer install && php artisan test'

test:
	docker compose exec php sh -c 'php artisan test '

cs:
	docker compose exec php sh -c 'vendor/bin/php-cs-fixer fix --diff'

php:
	docker compose exec php bash
