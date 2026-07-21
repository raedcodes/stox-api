# Business Suite API

Multi-tenant inventory management system. Built on Laravel + Filament + `stancl/tenancy` — each business (tenant) gets its own isolated database, identified by subdomain.

## Setup

```
composer install
php artisan storage:link
php artisan migrate:fresh --seed
```

That last command does everything: sets up the central database, seeds roles, creates the SuperAdmin, and creates a demo business ("Demo Business", subdomain `demo`) with its own provisioned database, tax/barcode settings, units of measure, and a default location — all in one go.

## Accessing the app

There are two separate panels, for two different kinds of users.

### Admin panel — super admin only

- **URL:** `http://localhost:8000/admin` (central domain — not any tenant subdomain)
- **Login:** `admin@business-suite.com` / value of `ADMIN_PASSWORD` in `.env`
- **Purpose:** the super admin's *only* job here is creating business accounts (the "Businesses" wizard: business name, subdomain, business type, currency, timezone, owner account). Businesses manage everything else about their own operation themselves, through their own panel below.

### Business panel — self-service, per business

- **URL:** `http://{subdomain}.localhost:8000/app/login` — each business is reached at its own subdomain
- **Example:** the seeded demo business → `http://demo.localhost:8000/app/login`
- **Login:** `owner@business-suite.com` / value of `OWNER_PASSWORD` in `.env` (for the demo business)
- **Purpose:** the business owner manages their own business profile, tax rate, barcode settings, locations, categories, and brands — end to end, without the super admin's involvement.

**Important:** `localhost`/`127.0.0.1` are registered as *central* domains (see `config/tenancy.php`) — the business panel is deliberately blocked there (404). You must use the business's actual subdomain to reach `/app`.

### Local subdomain routing

- `*.localhost` subdomains (e.g. `demo.localhost`) resolve to `127.0.0.1` automatically in modern browsers and on Linux — no `/etc/hosts` changes needed when running via `php artisan serve`.
- If serving through Apache/XAMPP instead, you'll need a VirtualHost with a wildcard `ServerAlias` (e.g. `*.business-suite.test`) pointing at `public/` — there currently isn't one configured for this project, so a bare XAMPP setup without one **cannot** reach tenant subdomains at all.

## Tenant management commands

- `php artisan tenants:list` — list all tenants
- `php artisan tenants:migrate` — run any pending tenant-database migrations across **all** tenants (needed after adding a new migration under `database/migrations/tenant/`, since existing tenants aren't migrated automatically — only newly-created ones are)
- `php artisan tenants:seed` — re-run `TenantDatabaseSeeder` across all tenants (for backfilling new defaults into existing tenants)
- `php artisan tenants:migrate-fresh` — ⚠️ drops and rebuilds every tenant database; destructive

Note: `migrate:fresh` only resets the **central** database. It does not drop physical tenant databases from previous runs — those accumulate in MySQL until a tenant is properly deleted through the app (or removed by hand).

## Environment notes

- `SESSION_CONNECTION`, `DB_CACHE_CONNECTION`, and `DB_QUEUE_CONNECTION` in `.env` are pinned to the central database connection (`mysql`). This is required — without it, sessions/cache/queue would silently follow whichever database connection is currently active, and break the moment a request switches into tenant context. These three lines live in `.env` (gitignored), so they need to be set again on every environment this project runs in.
- `config/tenancy.php`'s `central_domains` list needs your production domain(s) added before deploying.
