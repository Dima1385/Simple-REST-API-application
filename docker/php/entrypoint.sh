#!/usr/bin/env bash
# Container entrypoint: installs deps, applies migrations and loads fixtures
# on the very first start, then hands off to PHP-FPM.

set -euo pipefail

APP_HOME="${APP_HOME:-/var/www/app}"
cd "${APP_HOME}"

# Install Composer dependencies the first time (or whenever vendor is missing).
if [[ ! -d "vendor" || ! -f "vendor/autoload.php" ]]; then
  echo "[entrypoint] Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader --no-security-blocking
fi

# Parse the DATABASE_URL once into PostgreSQL connection parameters - we use
# the standard `psql` client (installed in the image) rather than PHP/PDO so
# the readiness probe doesn't depend on the application's PHP environment.
DB_URL="${DATABASE_URL:-}"
if [[ -n "${DB_URL}" && "${DB_URL}" == postgres* ]]; then
  DB_USER=$(echo "${DB_URL}" | sed -E 's|^postgres(ql)?://([^:]+):.*|\2|')
  DB_PASS=$(echo "${DB_URL}" | sed -E 's|^postgres(ql)?://[^:]+:([^@]+)@.*|\2|')
  DB_HOST=$(echo "${DB_URL}" | sed -E 's|^postgres(ql)?://[^@]+@([^:/?]+).*|\2|')
  DB_PORT=$(echo "${DB_URL}" | sed -E 's|^postgres(ql)?://[^@]+@[^:/?]+:([0-9]+).*|\2|')
  DB_NAME=$(echo "${DB_URL}" | sed -E 's|^postgres(ql)?://[^@]+@[^/]+/([^?]+).*|\2|')

  echo "[entrypoint] Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
  ATTEMPTS=0
  until PGPASSWORD="${DB_PASS}" pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" >/dev/null 2>&1; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [[ ${ATTEMPTS} -ge 60 ]]; then
      echo "[entrypoint] PostgreSQL still not reachable after 60 attempts, giving up." >&2
      exit 1
    fi
    sleep 1
  done
fi

echo "[entrypoint] Running database migrations..."
php bin/console doctrine:database:create --if-not-exists --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [[ "${LOAD_FIXTURES:-0}" == "1" ]]; then
  # Only load fixtures when the books table is empty - we don't want to wipe
  # user-created data on every container restart.
  HAS_BOOKS="0"
  if [[ -n "${DB_HOST:-}" ]]; then
    HAS_BOOKS=$(PGPASSWORD="${DB_PASS}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" \
      -tAc 'SELECT COUNT(*) FROM books' 2>/dev/null || echo "0")
    HAS_BOOKS="${HAS_BOOKS// /}"
  fi

  if [[ "${HAS_BOOKS}" == "0" ]]; then
    echo "[entrypoint] Loading fixtures (books table is empty)..."
    php bin/console doctrine:fixtures:load --no-interaction --append
  else
    echo "[entrypoint] Skipping fixtures - books table already has ${HAS_BOOKS} rows."
  fi
fi

echo "[entrypoint] Warming up Symfony cache..."
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

echo "[entrypoint] Starting: $*"
exec "$@"
