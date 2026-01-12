# Reservations Land Gorilla

Example project for managing inventory reservations with PHP, MySQL, database migrations using Phinx, and tests with PHPUnit.

## Quick Start

Follow these steps to get the project running quickly:

1. **Build and start the containers:**

```bash
docker compose up -d --build
```

2. **Run database migrations and seeds:**

```bash
docker compose exec app vendor/bin/phinx migrate -c db/phinx.php
docker compose exec app vendor/bin/phinx seed:run -c db/phinx.php
```

3. **Run PHPUnit tests:**

```bash
docker compose exec app vendor/bin/phpunit tests
```

Now the backend is available at `http://localhost:8080` and the MySQL database is on port `3306`.

---

## Requirements

- Docker and Docker Compose (>= v3.9)
- PHP >= 8.1
- Composer
- MySQL 8.0

## Environment Variables

Copy the `.env.example` file to `.env` and make sure the following variables are set:

```env
APP_ENV=local

DB_HOST=db
DB_NAME=app
DB_USER=app
DB_PASSWORD=secret
DB_PORT=3306

PAYMENT_BASE_URI=https://55b73bf4-c233-4ab2-b2fa-b8a67d60e2c8.mock.pstmn.io
NOTIFICATION_BASE_URI=https://55b73bf4-c233-4ab2-b2fa-b8a67d60e2c8.mock.pstmn.io
```

> Note: You can modify these variables according to your environment.

## Starting the Project

1. Build and start the containers:

```bash
docker compose up -d --build
```

2. Check that the containers are running:

```bash
docker compose ps
```

- The backend will be available at: `http://localhost:8080`
- The MySQL database will be available on port `3306`.

## Database Migrations

1. Run migrations:

```bash
docker compose exec app vendor/bin/phinx migrate -c db/phinx.php
```

2. Run seeds (initial data):

```bash
docker compose exec app vendor/bin/phinx seed:run -c db/phinx.php
```

3. Check migration status:

```bash
docker compose exec app vendor/bin/phinx status -c db/phinx.php
```

## Running Tests

Run PHPUnit tests:

```bash
docker compose exec app vendor/bin/phpunit tests
```

Or using a standalone container (without starting the full stack):

```bash
docker run --rm -v $(pwd):/var/www/html php:8.1-cli ./vendor/bin/phpunit tests
```
## Volumes

- `vendor_data`: To persist Composer dependencies.
- `db_data`: To persist MySQL data.

## Postman

API documentation, environments, and the mock server are available in Postman:

[Postman Project - Prueba2025 Senior](https://www.postman.com/docking-module-geologist-68150856/prueba2025-senior)

> Here you can explore endpoints, test requests, and use the mock server to simulate payment and notification integrations.
