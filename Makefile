DC = docker compose

.PHONY: init up down restart build install migrate fresh test test-front lint shell

init:
	cp -n .env.example .env || true
	$(DC) build app
	$(DC) run --rm app composer install
	$(DC) --profile dev run --rm node npm install
	$(DC) up -d postgres redis
	$(DC) run --rm app php artisan key:generate
	$(DC) run --rm app php artisan storage:link
	$(DC) run --rm app php artisan migrate --seed
	$(DC) --profile dev up -d

up:
	$(DC) --profile dev up -d

down:
	$(DC) down

restart:
	$(DC) restart

build:
	$(DC) build

install:
	$(DC) run --rm app composer install
	$(DC) --profile dev run --rm node npm install

migrate:
	$(DC) run --rm app php artisan migrate

fresh:
	$(DC) run --rm app php artisan migrate:fresh --seed

test:
	$(DC) run --rm app php artisan test

test-front:
	npm run test

lint:
	$(DC) run --rm app ./vendor/bin/pint --test

shell:
	$(DC) exec app sh
