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

# Rendered to /tmp (not /etc/nginx) since the container runs as www-data,
# which can't write into /etc/nginx. nginx.conf is a template because Render
# only tells us the port to listen on at runtime, via $PORT — envsubst is
# scoped to just this one variable so it doesn't touch nginx's own
# $uri/$query_string/etc. syntax.
PORT="${PORT:-10000}" envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /tmp/nginx.conf

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
