#!/bin/sh
set -e

cd /var/www/html

php artisan storage:link || true

php artisan migrate --force
php artisan db:seed --force
php artisan tenants:migrate --force
php artisan tenants:seed --class="TenantDatabaseSeeder" --force

# Opt-in only: DemoDataSeeder appends more sales/purchase orders every time
# it runs, so it must NOT run unconditionally on every boot (this container
# restarts on every Render free-tier wake-up). Set SEED_DEMO_DATA=true as a
# Render env var to run it once, then unset it again before the next deploy.
if [ "$SEED_DEMO_DATA" = "true" ]; then
    php artisan tenants:seed --class="DemoDataSeeder" --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
