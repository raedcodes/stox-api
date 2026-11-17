# Stox

Stox is a multi-tenant inventory management and point-of-sale SaaS. It is built on Laravel 12, Filament 5 and `stancl/tenancy`. Each business (tenant) gets its own isolated MySQL database and is reached at its own subdomain.

## Table of contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Local setup](#local-setup)
- [Environment variables](#environment-variables)
- [Accessing the app](#accessing-the-app)
- [Roles](#roles)
- [Localization](#localization)
- [Scheduled tasks and queues](#scheduled-tasks-and-queues)
- [Tenant management commands](#tenant-management-commands)
- [Testing and code style](#testing-and-code-style)
- [Deployment](#deployment)
- [Further documentation](#further-documentation)

## Features

- **Catalog:** products with variants, units of measure, pricing, images, stock thresholds, categories and brands. Products can be batch- or expiry-tracked.
- **Purchasing:** suppliers and supplier ledgers. Purchase orders go through an approval workflow (Draft → Pending Approval → Ordered / Rejected / Cancelled), with receiving, landed cost and purchase returns.
- **Sales / POS:** barcode-scanning point of sale, split payments across several payment methods, discounts, sale returns and refunds, PDF invoices and receipts. Customers have ledgers, and there is a walk-in customer.
- **Inventory:** a stock movement ledger, FEFO (first-expired, first-out) deduction, stock transfers between locations, stock adjustments, opening stock entry, and low-stock / expiry alerts with auto-reorder suggestions.
- **Cash management:** cash drawer sessions per location (opening float, expected cash, counted cash, variance) and expense tracking.
- **Reporting:** a KPI dashboard and role-specific widgets. Reports cover sales, purchases, tax, profit and loss, inventory valuation, accounts payable / receivable aging, and customer / supplier statements, with PDF and Excel export.
- **AI (Google Gemini):** AI summaries on key reports, and an "Ask Stox" assistant that answers questions over read-only business data.
- **Platform:** an activity log / audit trail, global search, soft deletes with restore, English and Arabic (RTL) UI, and per-tenant database backups.

## Tech stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+; the Docker image uses PHP 8.4) |
| Admin UI | Filament 5 (Livewire, Tailwind, Vite) |
| Multi-tenancy | `stancl/tenancy` (database per tenant, subdomain identification) |
| Auth / roles | Laravel Sanctum, `spatie/laravel-permission` |
| Audit | `spatie/laravel-activitylog` |
| Exports | `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (Excel) |
| Backups | `spatie/laravel-backup` plus a custom per-tenant backup command |
| AI | Google Gemini API |
| Tests | Pest |

## Architecture

- **Central database:** holds only `tenants`, `domains`, `users` (super admin and business owners), roles and permissions, and the activity log. Its migrations are in `database/migrations/`.
- **Tenant databases:** each business has its own database, named `tenant{id}`, holding all of its operational data. Their migrations are in `database/migrations/tenant/`.
- **Two Filament panels:**
  - `admin`: served on the central domain at `/admin`. Code is in `app/Filament/Resources`.
  - `business`: tenant-scoped, served at `/app` on the business's subdomain. Code is in `app/Filament/Business/{Resources,Pages,Widgets}`.
- **Domain logic:** services are in `app/Services` (accounting, AI assistant, Gemini client). Enums for statuses and types are in `app/Enums`. Tenant-only HTTP routes (invoice and PO PDFs) are in `routes/tenant.php`.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `intl`, `gd`, `zip`, `bcmath` and `mbstring`
- Composer 2
- Node.js 20+ and npm
- MySQL. The database user must be allowed to `CREATE DATABASE`, because a new database is provisioned for every tenant.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` before continuing. Set `DB_CONNECTION=mysql` and the `DB_*` credentials (the template defaults to SQLite, which does not support database-per-tenant here). Also set `ADMIN_PASSWORD` and `OWNER_PASSWORD` (see [Environment variables](#environment-variables)). Then run:

```bash
npm install
npm run build

php artisan storage:link
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoBusinessSeeder

composer dev   # serve + queue worker + log tail + Vite, in one terminal
```

What these commands do:

- `migrate:fresh --seed` sets up the central database, seeds the roles and creates the SuperAdmin. Production uses the same seeding.
- `DemoBusinessSeeder` is local-only. It creates a demo business ("Demo Business", subdomain `demo`) with its own provisioned database, tax and barcode settings, units of measure and a default location, so you have a tenant to log into straight away.
- To fill the demo tenant with a realistic catalog, stock, ledgers and 60 days of sales history, also run the command below. It needs dev dependencies (Faker).

  ```bash
  php artisan tenants:seed --class="DemoDataSeeder" --tenants=<tenant-id>
  ```

Run `npm run build` again whenever you add a Blade view or use Tailwind utility classes that aren't used anywhere else yet. Otherwise the new markup renders unstyled.

## Environment variables

These are the project-specific variables on top of the standard Laravel ones:

| Variable | Purpose |
| --- | --- |
| `ADMIN_PASSWORD` | Password for the seeded SuperAdmin (`admin@business-suite.com`). |
| `OWNER_PASSWORD` | Password for the demo tenant owner (`owner@business-suite.com`). Local only; not in `.env.example`. |
| `DEMO_TENANT_DOMAIN` | Subdomain for the demo tenant. Default: `demo`. |
| `TENANCY_EXTRA_CENTRAL_DOMAINS` | Comma-separated extra central domains, such as the production apex domain. `localhost` and `127.0.0.1` are always central. |
| `GEMINI_API_KEY` | Google Gemini key for AI summaries and Ask Stox. |
| `GEMINI_MODEL` | Gemini model ID. Default: `gemini-3.5-flash-lite`. |
| `QUEUE_CONNECTION` | Queue driver. Notifications are queued (default `database`). |
| `MAIL_*` | Mail transport for notifications and the daily digest. |
| `RESET_ALL_TENANTS` | Docker only. Set it to `true` to wipe and re-seed **every** tenant database on container start. ⚠️ Destructive. |
| `INCLUDE_DEV_DEPS` | Docker build argument. Set it to `true` to install dev dependencies (needed for `DemoDataSeeder`). |

Never commit `.env` or real credentials.

## Accessing the app

There are two separate panels, for two different kinds of users.

### Admin panel (super admin only)

- **URL:** `http://localhost:8000/admin`. This is the central domain, not a tenant subdomain.
- **Login:** `admin@business-suite.com`, with the value of `ADMIN_PASSWORD` in `.env`.
- **Purpose:** the super admin only creates business accounts here. The "Businesses" wizard collects the business name, subdomain, business type, currency, timezone and owner account. Businesses manage everything else themselves, through their own panel.

### Business panel (self-service, one per business)

- **URL:** `http://{subdomain}.localhost:8000/app/login`. Each business is reached at its own subdomain.
- **Example:** the seeded demo business is at `http://demo.localhost:8000/app/login`.
- **Login:** `owner@business-suite.com`, with the value of `OWNER_PASSWORD` in `.env` (for the demo business).

**Important:** `localhost` and `127.0.0.1` are registered as *central* domains (see `config/tenancy.php`), so the business panel is deliberately blocked there with a 404. You must use the business's own subdomain to reach `/app`.

### Local subdomain routing

- Modern browsers and Linux resolve `*.localhost` subdomains (for example `demo.localhost`) to `127.0.0.1` automatically. You don't need to edit `/etc/hosts` when running with `php artisan serve`.
- If you serve the app through Apache/XAMPP instead, you need a VirtualHost with a wildcard `ServerAlias` (for example `*.stox.test`) pointing at `public/`. Without one, tenant subdomains cannot be reached.

## Roles

Roles are seeded by `RolesAndPermissionsSeeder`:

| Role | Scope |
| --- | --- |
| SuperAdmin | Admin panel. Creates and manages businesses. |
| Owner | Full access to their own business. |
| Manager | Day-to-day operations, approvals and reports. |
| Cashier | POS, and their own register sales. |
| StoreKeeper | Purchasing, receiving and stock transfers. |

## Localization

The UI ships in English and Arabic (RTL), with translations in `lang/en` and `lang/ar`. Each user's preferred locale is applied per request by `App\Http\Middleware\SetLocale`. Vocabulary shared across screens is in `lang/*/common.php`. When you add a screen, add its strings to both locales.

## Scheduled tasks and queues

These are defined in `routes/console.php`:

| Time | Command | Purpose |
| --- | --- | --- |
| 01:00 | `backup:clean` | Prune old backups |
| 01:30 | `backup:run --only-db --db-name=mysql` | Back up the central database |
| 02:00 | `backup:tenants` | Back up each tenant database individually |
| 07:00 | `digest:send` | Email each tenant's Owners and Managers a low-stock and expiring-batch digest |

- The scheduler needs `php artisan schedule:run` to run every minute. Use cron on Forge; the Docker image runs it under supervisord.
- Notifications (PO approval requests, the daily digest) are queued, so a worker must be running: `php artisan queue:work`.

## Tenant management commands

- `php artisan tenants:list` lists all tenants.
- `php artisan tenants:migrate` runs pending tenant-database migrations across **all** tenants. Run it after adding a migration under `database/migrations/tenant/`: existing tenants are not migrated automatically, only newly created ones.
- `php artisan tenants:seed` re-runs `TenantDatabaseSeeder` across all tenants, to backfill new defaults into existing tenants.
- `php artisan tenants:migrate-fresh` drops and rebuilds every tenant database. ⚠️ Destructive.

`migrate:fresh` only resets the **central** database. It does not drop the physical tenant databases left by earlier runs. Those stay in MySQL until the tenant is deleted through the app or removed by hand.

## Testing and code style

```bash
composer test          # clears config cache, runs the Pest suite
./vendor/bin/pint      # format code (Laravel Pint)
```

## Deployment

### Seeding policy

Seeding is deliberately split so that production never gets local or demo data:

- **`DatabaseSeeder`** is the default; plain `db:seed` runs it. It runs only `RolesAndPermissionsSeeder` and `SuperAdminSeeder`, which create the 5 roles and the SuperAdmin. It is safe to run in production.
- **`DemoBusinessSeeder`** and **`DemoDataSeeder`** create demo data. Only run them explicitly by name. **Never run them in production.**

Once a migration has run against production (central or any tenant), don't edit that migration file. Ship schema changes as new migrations from then on.

### Laravel Forge

Forge's default deploy script only runs `php artisan migrate --force`, which only touches the **central** database. Add `tenants:migrate` as its own step. Without it, new tenant-table columns never reach existing businesses after a deploy.

Deploy script:

```bash
cd /home/forge/your-site.com
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan tenants:migrate --force
fi
```

- `migrate --force` runs first (central database). `tenants:migrate --force` runs second: it loops over every tenant and applies pending `database/migrations/tenant/` migrations. Both need `--force`, because Forge runs in production and both commands refuse destructive-looking operations without it.
- Never put `migrate:fresh` or `tenants:migrate-fresh` in this script. Both drop and rebuild databases and are for local or manual use only.
- In Forge, also set up a scheduler (cron) entry and a queue worker daemon (see [Scheduled tasks and queues](#scheduled-tasks-and-queues)).

After the **first** deploy only, run these by hand through Forge's SSH / "Run command". Don't add them to the deploy script, because neither needs to run on every deploy:

```bash
php artisan storage:link
php artisan db:seed --force
```

- `storage:link` creates the `public/storage` symlink so uploaded files (product images, tenant logos) can be served.
- `db:seed` is needed because a fresh production database has no roles and no SuperAdmin. `migrate` and `tenants:migrate` don't create either, so `/admin` can't be reached until this has run once.

### Docker (Render)

The `Dockerfile` builds a single image that runs nginx, PHP-FPM and the scheduler under supervisord, listening on `$PORT` (default `10000`). On every container start, `docker-entrypoint.sh` runs these steps:

1. `storage:link`
2. `migrate --force` and `db:seed --force` (central database)
3. `tenants:migrate --force` and `tenants:seed --class=TenantDatabaseSeeder --force`
4. If `RESET_ALL_TENANTS=true`: `tenants:migrate-fresh` and a re-seed with demo data. ⚠️ This wipes every tenant. Unset it again right after use.

## Further documentation

The full system documentation is in `docs/stox-system-documentation.html` (PDF: `docs/Stox-System-Documentation.pdf`).
