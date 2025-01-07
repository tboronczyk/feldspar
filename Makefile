include .devcontainer/.env

vendor: composer.json
	composer install

fmt-php: vendor
	php vendor/bin/phpcbf  \
	  --extensions=php     \
	  --ignore=vendor,var  \
	  --parallel=4         \
	  --standard=PSR12     \
	  .

lint-php: vendor
	-php vendor/bin/phpcs  \
	  --extensions=php     \
	  --ignore=vendor,var  \
	  --parallel=4         \
	  --standard=PSR12     \
	  .
	-php vendor/bin/phpstan --memory-limit=256M analyse

db-init: db/schema.sql
	mysql -u root -p$(MYSQL_ROOT_PASSWORD) -h mysql -e \
	  "CREATE DATABASE IF NOT EXISTS $(COMPOSE_PROJECT_NAME); \
	   CREATE USER IF NOT EXISTS '$(COMPOSE_PROJECT_NAME)'@'%' IDENTIFIED BY 'password'; \
	   GRANT SELECT, INSERT, UPDATE, DELETE ON $(COMPOSE_PROJECT_NAME).* TO '$(COMPOSE_PROJECT_NAME)'@'%'"
	mysql -u root -p$(MYSQL_ROOT_PASSWORD) -h mysql $(COMPOSE_PROJECT_NAME) < db/schema.sql

db-dump:
	mysqldump -u root -p$(MYSQL_ROOT_PASSWORD) -h mysql -E $(COMPOSE_PROJECT_NAME) \
	  > db/schema-`date +%Y%m%d%H%M%S`.sql

test: db-init
	npm run cypress:run
run-bin:
	-php bin/$(c)
