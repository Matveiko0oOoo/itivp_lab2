#!/bin/bash
set -euo pipefail

# Wait for DB
if [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for database ${DB_HOST}:${DB_PORT:-3306}..."
  for i in {1..60}; do
    if php -r 'try{$dbh=new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_NAME"), getenv("DB_USER"), getenv("DB_PASS"));echo "ok";}catch(Exception $e){echo "no";}' 2>/dev/null | grep -q ok; then
      echo "Database is up"; break
    fi
    sleep 1
  done
fi

# Run API key seed (idempotent)
php /var/www/html/scripts/seed_api_key.php || true

# Start apache
exec apache2-foreground
