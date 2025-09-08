up:
	docker compose up -d

test:
	docker compose exec php sh -c 'vendor/bin/phpunit'

cs:
	docker compose exec php sh -c 'vendor/bin/php-cs-fixer fix --diff'

php:
	docker compose exec php bash