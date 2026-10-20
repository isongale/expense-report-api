# expense-report-api

REST API for a multi-company expense management app: employees upload expenses with a receipt,
managers approve them and administration reimburses them.

Built with Laravel 13 (API only). The frontend lives in a separate repository, `expense-report-web` (Nuxt).

## Requirements

- Docker with Docker Compose v2

PHP and Composer are not needed on the host: they run inside the containers.

## First-time setup

```bash
# 1. Environment file: replace the placeholder passwords before the first start
cp .env.example .env

# 2. Build the PHP image
docker compose build

# 3. Install the dependencies and generate the application key
docker compose run --rm --no-deps app composer install
docker compose run --rm --no-deps app php artisan key:generate

# 4. Start the stack and create the database tables
docker compose up -d
docker compose exec app php artisan migrate
```

The API answers on <http://localhost:8080>; `GET /up` is the health check.

Two things to know about `.env`:

- Compose reads it to interpolate `compose.yaml`, so avoid `$` in values or wrap them in single quotes.
- MySQL and MongoDB credentials are applied only when their volume is first initialised. To change them
  later, recreate the volumes with `docker compose down -v` (this deletes the data).

If your user ID is not 1000, set `HOST_UID` and `HOST_GID` in `.env` before building, so that files
written by the containers belong to you.

## Services

| Service   | Image                   | Purpose                                   | Host access                      |
|-----------|-------------------------|-------------------------------------------|----------------------------------|
| `app`     | custom (`php:8.5-fpm`)  | Laravel application on PHP-FPM            | –                                |
| `queue`   | same image as `app`     | Queue worker (`queue:work`)               | –                                |
| `web`     | `nginx:1.30-alpine`     | HTTP entry point, forwards to PHP-FPM     | <http://localhost:8080>          |
| `mysql`   | `mysql:9.7`             | Relational data                           | `127.0.0.1:3307`                 |
| `redis`   | `redis:8.10`            | Cache, sessions and queues                | –                                |
| `mongo`   | `mongo:7.0`             | Audit log and raw receipt data            | –                                |
| `mailpit` | `axllent/mailpit:v1.31` | Catches outgoing email                    | <http://localhost:8025>          |

Published ports are bound to `127.0.0.1` only. Services without host access are reachable from the other
containers by their service name (for example `redis:6379`).

## Everyday commands

```bash
docker compose up -d                           # start
docker compose ps                              # state and health
docker compose logs -f app queue               # follow the logs
docker compose down                            # stop

docker compose exec app php artisan <command>
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint

docker compose restart queue                   # after changing a job or .env
```

The queue worker keeps code and configuration in memory: restart it after changing a job or `.env`,
otherwise it keeps running the old version.

## Project conventions

Architecture, conventions, Git workflow (Gitflow) and roadmap are described in [`CLAUDE.md`](CLAUDE.md).
