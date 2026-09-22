#!/bin/sh
set -eu
: "${APP_KEY:?APP_KEY must be configured}"
# Never migrate on startup: deployments run migrations once, before rollout.
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec "$@"
