#!/bin/sh
set -euo pipefail
if ! [ -d /var/www/localhost/htdocs/vendor ]; then
  composer update
fi
if ! [ -d /var/www/localhost/htdocs/storage/framework/views ]; then
  mkdir -p /var/www/localhost/htdocs/storage/framework/views
fi
chown -R apache:apache /var/www

php artisan clear
php artisan cache:clear
php artisan view:cache
php artisan route:cache

if mysqlshow --host=${DB_HOST} --user=${DB_USERNAME} --password=${DB_PASSWORD} ${DB_DATABASE} users; then
  echo "database ready!"
else
  php artisan migrate:refresh
  php artisan passport:install
  php artisan telescope:install
  php artisan horizon:install
fi

php artisan horizon ＆
rm -f /run/apache2/httpd.pid
exec httpd -DFOREGROUND
