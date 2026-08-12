#!/bin/sh
cd /var/www || exit 1

if [ -f artisan ]; then
  if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi

  mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache storage/app/public
  chmod -R 777 storage bootstrap/cache 2>/dev/null || true

  if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force 2>/dev/null || true
  fi

  php artisan config:clear 2>/dev/null || true
  php artisan migrate --force --graceful 2>/dev/null || true

  rm -f /var/www/public/storage
  php artisan storage:link 2>/dev/null || true
fi

exec php artisan serve --host=0.0.0.0 --port=8000
