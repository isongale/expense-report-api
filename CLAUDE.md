# expense-report-api

REST API for a multi-company expense management app, built with Laravel.
The frontend lives in a separate repository: `expense-report-web` (Nuxt).

## Domain

Employees of multiple companies upload expenses with a receipt; managers approve them; administration reimburses them.

**Main entities (first draft, open to discussion):**
Company, User, ExpenseCategory, Expense, Receipt, Approval.

**Expense lifecycle:** `draft` → `submitted` → `approved` / `rejected` → `reimbursed`.

**Roles:**
- `employee`: creates and submits their own expenses
- `manager`: approves or rejects the expenses of their team
- `admin`: administration, manages reimbursements, categories and company policies
- `auditor`: read-only, with access to the audit log

**Business rules:**
- Multi-tenancy: each user only sees the data of their own company. The `company_id` never comes from the client,
  it is derived from the authenticated user.
- Separation of duties: whoever creates an expense cannot approve it, even with the `manager` role.
- An expense above the policy limit of its category requires a second approval.
- Every status change is recorded in the audit log (who, what, when, previous and next status).

## Architecture

- **Laravel** latest stable version, API only (no Blade views), endpoints under `/api/v1`.
- **MySQL**: relational data (companies, users, expenses, approvals).
- **MongoDB**: append-only audit log and raw data extracted from receipts, via `mongodb/laravel-mongodb`.
- **Redis**: queues and cache.
- **Authentication**: Sanctum in SPA mode (cookie) for the Nuxt frontend. Later on, OAuth2 Client Credentials
  for an external client (payroll system): the library is an open decision.
  - Why Sanctum and not Passport for the SPA: the frontend is a first-party app, so there is no access
    delegation to manage and OAuth2 would add complexity with no benefit. The browser only holds an HttpOnly
    session cookie (no token readable by JavaScript), and the server-side session can be revoked immediately.
  - Trade-offs accepted: frontend and API must share the same top-level domain, CORS/CSRF configuration is
    stricter, and the API is stateful (sessions stored in Redis).
  - Sanctum tokens are not OAuth2, so they do not cover the Client Credentials requirement: candidates for
    that are Passport (alongside Sanctum, on a separate guard) or an external identity provider.
- **Docker Compose** in this repository for the development services: PHP, web server, MySQL, MongoDB, Redis,
  Mailpit, queue worker. Prefer an explicit compose file, where every service is readable and explained,
  over solutions that hide the configuration. If you propose Sail, discuss its trade-offs.

## Conventions

- Validation with Form Requests, output with API Resources, authorization with Policies.
- Consistent JSON error format across the whole API; pagination on every list.
- Business logic outside controllers (action or service classes: open decision).
- Code style with Laravel Pint; static analysis with Larastan.
- Tests: feature tests for every endpoint and every policy. Framework (PHPUnit or Pest): open decision.
- Code, names, code comments, commit messages and technical documentation in English.
- Small commits in Conventional Commits format (`feat:`, `fix:`, `chore:`, `test:`, `docs:`).

## Git workflow

The repository follows **Gitflow**:

- `main`: released code only; every commit on `main` is a release with a SemVer tag (`v0.1.0`).
- `develop`: integration branch, base for all work in progress.
- `feature/<short-description>`: branches off `develop` and merges back into `develop`.
- `release/<version>`: branches off `develop`, only receives fixes and version bumps, then merges into `main`
  (tagged) and into `develop`.
- `hotfix/<version>`: branches off `main`, then merges into `main` (tagged) and into `develop`.

Rules:

- No direct commits on `main` or `develop`: always work in a `feature/*`, `release/*` or `hotfix/*` branch.
- Merge with `--no-ff`, to keep the scope of each branch visible in the history.
- Before starting any work, check that you are on the right branch and, if needed, create it from `develop`.

## Security

- No secrets in the repository: only `.env.example` with dummy values.
- Sensitive data encrypted at rest (for example the employee's IBAN, with the `encrypted` cast).
- Every endpoint protected by authentication and by a Policy; no direct access by ID without a tenant check.
- Structured JSON logs with a request ID propagated to jobs and downstream calls.

## Roadmap

The project proceeds in levels. Each item becomes one or more issues in the GitHub Project.

- **Level 1 – API and integration:** Docker setup, models and migrations, expense CRUD, Sanctum with Nuxt,
  Policies and multi-tenant scoping, separation of duties.
- **Level 2 – Async and MongoDB:** queued receipt upload and processing, monthly export, email notifications,
  audit log on MongoDB, policy threshold rule.
- **Level 3 – Security and observability:** OAuth2 Client Credentials, OIDC/JWT (optional, e.g. Keycloak),
  encryption and secrets management, structured logs.
- **Level 4 – Quality:** test coverage, Pint and Larastan in CI, optional receipt data extraction via LLM.

## Don'ts

- Do not add Composer packages without first discussing the need and the alternatives.
- Do not modify the `expense-report-web` repository from this session, unless explicitly asked.
- Do not run commits, pushes or destructive commands (e.g. `migrate:fresh`) without confirmation.

## Useful commands

PHP and Composer are not installed on the host: every command runs inside the containers.
See `README.md` for the first-time setup.

```bash
# Stack
docker compose up -d                 # start every service
docker compose ps                    # state and health
docker compose logs -f app queue     # follow the logs
docker compose down                  # stop (add -v to also delete the data volumes)
docker compose build app             # rebuild the PHP image after changing docker/php/Dockerfile

# Application (inside the "app" container)
docker compose exec app php artisan <command>
docker compose exec app composer <command>
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint

# Queue worker: it keeps code and configuration in memory
docker compose restart queue         # after changing a job or .env

# Data stores
docker compose exec mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
docker compose exec redis redis-cli
docker compose exec mongo sh -c 'mongosh -u "$MONGO_INITDB_ROOT_USERNAME" -p "$MONGO_INITDB_ROOT_PASSWORD" --authenticationDatabase admin'
```

Notes:

- Compose reads the Laravel `.env` to interpolate `compose.yaml`: avoid `$` in values, or wrap them in single quotes.
- MySQL and MongoDB credentials are applied only when their volume is first initialised: changing them in
  `.env` afterwards has no effect until the volume is recreated (`docker compose down -v`, which deletes the data).
- Larastan is not installed yet (Level 4).
