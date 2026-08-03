#!/bin/sh
set -e

cd /var/www/html

php artisan storage:link || true

php artisan migrate --force
php artisan db:seed --force
php artisan tenants:migrate --force
php artisan tenants:seed --class="TenantDatabaseSeeder" --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
