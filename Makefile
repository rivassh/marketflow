.PHONY: install up down test lint format migrate seed worker scheduler

install:
	cd backend && composer install
	cd frontend && npm install

up:
	docker compose up -d --build

down:
	docker compose down -v

test:
	cd backend && php artisan test

lint:
	cd backend && php vendor/bin/phpstan analyse --no-progress
	cd frontend && npm run lint

format:
	cd backend && php vendor/bin/pint
	cd frontend && npm run format

migrate:
	cd backend && php artisan migrate --force

seed:
	cd backend && php artisan db:seed

worker:
	cd backend && php artisan queue:work database --timeout=60

scheduler:
	cd backend && php artisan schedule:work