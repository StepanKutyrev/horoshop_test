.PHONY: up down build test migrate dump

up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose build

test:
	docker-compose exec db mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS horoshop_test_test;"
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker-compose exec php ./vendor/bin/phpunit

migrate:
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

dump:
	docker-compose exec db mysqldump -u root -ppassword horoshop_test > database_dump.sql

bash:
	docker-compose exec php bash
