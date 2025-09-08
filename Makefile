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

test:
	docker compose exec php sh -c 'vendor/bin/phpunit'

cs:
	docker compose exec php sh -c 'vendor/bin/php-cs-fixer fix --diff'

php:
	docker compose exec php bash
