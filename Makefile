up:
	docker compose up -d
	@echo "*** Success! Your app is ready and available at http://localhost:$(NGINX_PORT)/docs. ***"

test:
	docker compose exec php sh -c 'php artisan test '

cs:
	docker compose exec php sh -c 'vendor/bin/php-cs-fixer fix --diff'

php:
	docker compose exec php bash

