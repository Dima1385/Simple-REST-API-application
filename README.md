# Book Library — REST API

A small, well-structured REST API for tracking books in a library.

Built with **PHP 8.5** (latest), **Symfony 7.2**, **Doctrine ORM 3**, and **PostgreSQL 16**, fully containerised with **Docker Compose** and documented via a built‑in **Swagger UI**.

The project deliberately keeps a clean layered architecture so the code is easy to read and extend:

```
src/
├── Controller/        HTTP layer — thin, only HTTP concerns
├── Service/           Application/business logic (BookService)
├── Repository/        Data access (Doctrine ORM)
├── Entity/            Domain model (Book)
├── Dto/               Request/response DTOs (validated automatically)
├── EventListener/     Global JSON error normaliser
├── Exception/         Domain exceptions
└── DataFixtures/      Seed data for dev & test
```

---

## Requirements

You only need **Docker** and **Docker Compose** on the host machine. Everything else (PHP, Composer, PostgreSQL, etc.) runs inside containers.

- Docker 24+
- Docker Compose v2

> The project also runs natively if you have PHP 8.5+ and Composer 2.6+ installed locally, but the recommended way is via Docker.

---

## Quick start (Docker)

```bash
# 1. Clone
git clone <repo-url> book-library
cd book-library

# 2. (optional) tweak settings — defaults are fine for local use
cp .env.example .env.local

# 3. Build and start the stack
docker compose up --build -d

# 4. Wait until the entrypoint finishes its first run.
#    It installs Composer deps, applies migrations and loads fixtures.
docker compose logs -f php
```

When you see `Starting: php-fpm` in the logs, the API is ready.

| URL                                   | What                       |
| ------------------------------------- | -------------------------- |
| <http://localhost:8080/api/health>    | Health probe (`{"status":"ok"}`) |
| <http://localhost:8080/api/books>     | Books endpoint             |
| <http://localhost:8080/api/doc>       | **Swagger UI**             |
| <http://localhost:8080/api/doc.json>  | Raw OpenAPI 3 spec         |

### What the entrypoint does automatically

`docker/php/entrypoint.sh` runs on every container start. On the **first** start it:

1. Runs `composer install` (only if `vendor/` is missing).
2. Waits for PostgreSQL to be reachable.
3. Creates the database if it doesn't exist.
4. Runs all Doctrine migrations.
5. Loads fixtures (5 sample books) — but only if the `books` table is empty,
   so user data is never wiped on restart. Disable with `LOAD_FIXTURES=0`.
6. Warms up the Symfony cache.

You can stop and start the stack with `docker compose stop` / `docker compose start` without losing data — PostgreSQL is backed by a named volume.

---

## Endpoints

All endpoints live under `/api`. Bodies are JSON; responses are JSON.

| Method | Path                | Purpose                          |
| ------ | ------------------- | -------------------------------- |
| GET    | `/api/books`        | List books, supports pagination  |
| GET    | `/api/books/{id}`   | Fetch a single book              |
| POST   | `/api/books`        | Create a book                    |
| PATCH  | `/api/books/{id}`   | Partial update                   |
| DELETE | `/api/books/{id}`   | Delete a book                    |
| GET    | `/api/health`       | Health check                     |

### Book payload

| Field             | Type    | Example                       | Notes                              |
| ----------------- | ------- | ----------------------------- | ---------------------------------- |
| `title`           | string  | `"Refactoring"`               | required                           |
| `publisher`       | string  | `"Addison-Wesley"`            | required                           |
| `author`          | string  | `"Martin Fowler"`             | required                           |
| `genre`           | string  | `"Software Engineering"`      | required                           |
| `publicationDate` | date    | `"2018-11-19"`                | ISO 8601 (YYYY-MM-DD), required    |
| `wordCount`       | int     | `140000`                      | ≥ 0, required                      |
| `priceUsd`        | number  | `47.99`                       | ≥ 0, required, US Dollars          |

### Example: create

```bash
curl -X POST http://localhost:8080/api/books \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Refactoring",
    "publisher": "Addison-Wesley",
    "author": "Martin Fowler",
    "genre": "Software Engineering",
    "publicationDate": "2018-11-19",
    "wordCount": 140000,
    "priceUsd": 47.99
  }'
```

### Example: partial update

```bash
curl -X PATCH http://localhost:8080/api/books/1 \
  -H "Content-Type: application/json" \
  -d '{ "priceUsd": 29.99 }'
```

### Error format

Validation failures (`422`) and other API errors share the same shape:

```json
{
  "status": 422,
  "title": "Validation failed",
  "detail": "One or more fields are invalid.",
  "violations": [
    { "property": "title",       "message": "Title is required." },
    { "property": "publicationDate", "message": "Publication date must be a valid date (YYYY-MM-DD)." }
  ]
}
```

---

## Running tests

Tests run against an **in-memory SQLite** database, so they don't need the
Postgres container to be up. Inside the running stack:

```bash
docker compose exec php composer install      # idempotent
docker compose exec php vendor/bin/phpunit
```

Or without Docker:

```bash
composer install
vendor/bin/phpunit
```

The suite covers:

- **Unit tests** for the `Book` entity and `BookService` (mocks the repo).
- **Functional tests** that boot the real Symfony kernel and hit every
  endpoint over HTTP, including validation errors, 404s and pagination.

---

## Useful Docker commands

```bash
# Tail PHP / Symfony logs
docker compose logs -f php

# Open a shell inside the PHP container
docker compose exec php sh

# Run any Symfony console command
docker compose exec php php bin/console list

# Re-run migrations manually
docker compose exec php php bin/console doctrine:migrations:migrate -n

# Re-load fixtures (DESTROYS existing data!)
docker compose exec php php bin/console doctrine:fixtures:load -n

# Stop everything (keeps DB data)
docker compose down

# Stop and DELETE the database volume
docker compose down -v
```

## Project layout

```
.
├── bin/console                 # Symfony CLI entrypoint
├── config/                     # Symfony bundles, routes, packages
├── docker/
│   ├── nginx/default.conf      # Nginx vhost
│   └── php/
│       ├── Dockerfile          # PHP 8.5-FPM image
│       ├── php.ini             # OPcache/APCu/realpath tuning
│       └── entrypoint.sh       # Auto-install / migrate / fixtures
├── docker-compose.yml          # nginx + php + postgres
├── migrations/                 # Doctrine migrations
├── public/index.php            # HTTP entrypoint
├── src/                        # Application code (see top of README)
├── tests/                      # PHPUnit suites (Unit + Functional)
├── composer.json
└── phpunit.xml.dist
```

---

## Design notes

- **Latest PHP** — image uses `php:8.5-fpm` (PHP 8.5 is the current stable release as of 2026). Composer `require` enforces `>=8.5` so the project actually runs on the latest interpreter end-to-end. The Debian-based variant is used rather than Alpine because Alpine's 8.5 image currently trips an upstream `docker-php-ext-install` bug related to the new extension-API directory.
- **Symfony 7.2** — current stable release with native PHP 8.5 support.
- **DTO-driven validation** — `BookCreateRequest`/`BookUpdateRequest` use `MapRequestPayload`, so invalid input never reaches the service layer.
- **Service layer** — `BookService` is the single source of business logic. Controllers stay tiny.
- **Doctrine decimals** — prices are stored as `NUMERIC(10,2)` and exposed as floats over the wire. The service normalises inputs to a 2-decimal string to avoid floating-point drift.
- **Portable migrations** — the initial migration emits different DDL for Postgres vs SQLite vs MySQL, so the same migration works in production and in tests.
- **Consistent JSON errors** — `ApiExceptionListener` converts every exception under `/api/*` to a uniform JSON envelope, including validation failures.
- **Swagger** — `NelmioApiDocBundle` introspects the controller attributes + DTOs, no extra YAML required.

---

## License

MIT — see `composer.json`.
