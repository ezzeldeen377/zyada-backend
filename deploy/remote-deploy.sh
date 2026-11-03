#!/usr/bin/env bash
# Release deploy for Hostinger shared hosting.
# Usage: remote-deploy.sh APP_ROOT RELEASE_PATH [backup 0|1] [DOCROOT]
#
# hPanel cron, once per environment, every minute:
# * * * * * cd /home/u445234025/apps/zyada-prod/current && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
# * * * * * cd /home/u445234025/apps/zyada-prod/current && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
# Repeat both lines with zyada-staging.
#
# config:cache is intentionally not run. This app calls env() from
# controllers and Blade, and a cached config would make those calls return null.
set -euo pipefail

APP_ROOT="${1:?APP_ROOT required}"
RELEASE_PATH="${2:?RELEASE_PATH required}"
RUN_BACKUP="${3:-0}"
DOCROOT="${4:-}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
SHARED="$APP_ROOT/shared"
KEEP_RELEASES=5

if [[ ! -d "$RELEASE_PATH" ]]; then
  echo "Release path does not exist: $RELEASE_PATH" >&2
  exit 1
fi

case "$RELEASE_PATH" in
  "$APP_ROOT/releases"/*) ;;
  *)
    echo "Release must live under $APP_ROOT/releases" >&2
    exit 1
    ;;
esac

if [[ ! -f "$SHARED/.env" ]]; then
  echo "Missing $SHARED/.env. Create it before deploying. Do not point staging at the production database." >&2
  exit 1
fi

mkdir -p \
  "$SHARED/storage/app/public" \
  "$SHARED/storage/framework/cache/data" \
  "$SHARED/storage/framework/sessions" \
  "$SHARED/storage/framework/views" \
  "$SHARED/storage/logs" \
  "$HOME/backups"

ln -sfn "$SHARED/.env" "$RELEASE_PATH/.env"
rm -rf "$RELEASE_PATH/storage"
ln -sfn "$SHARED/storage" "$RELEASE_PATH/storage"
chmod -R u+rwX "$SHARED/storage" "$RELEASE_PATH/bootstrap/cache"

if [[ "$RUN_BACKUP" == "1" ]]; then
  cnf="$(mktemp)"
  chmod 600 "$cnf"
  "$PHP_BIN" -r '
    $file = $argv[1];
    $out = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
      $line = trim($line);
      if ($line === "" || $line[0] === "#") continue;
      $pos = strpos($line, "=");
      if ($pos === false) continue;
      $key = substr($line, 0, $pos);
      $value = trim(substr($line, $pos + 1));
      if (strlen($value) >= 2 && (($value[0] === "\"" && substr($value, -1) === "\"") || ($value[0] === "'"'"'" && substr($value, -1) === "'"'"'"))) {
        $value = substr($value, 1, -1);
      }
      if (in_array($key, ["DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD"], true)) {
        $out[$key] = $value;
      }
    }
    $quote = function ($value) {
      return "\"" . str_replace(["\\", "\"", "\n", "\r"], ["\\\\", "\\\"", "", ""], $value) . "\"";
    };
    $port = $out["DB_PORT"] ?? "3306";
    $ini = "[client]\n"
      . "host=" . $quote($out["DB_HOST"] ?? "localhost") . "\n"
      . "port=" . $quote($port) . "\n"
      . "user=" . $quote($out["DB_USERNAME"] ?? "") . "\n"
      . "password=" . $quote($out["DB_PASSWORD"] ?? "") . "\n";
    file_put_contents($argv[2], $ini);
    file_put_contents($argv[2].".name", $out["DB_DATABASE"] ?? "");
  ' "$SHARED/.env" "$cnf"
  db_name="$(cat "$cnf.name")"
  stamp="$(date +%Y%m%d%H%M%S)"
  dump="$HOME/backups/${db_name}-${stamp}.sql.gz"
  mysqldump --defaults-extra-file="$cnf" --single-transaction --no-tablespaces "$db_name" | gzip > "$dump"
  rm -f "$cnf" "$cnf.name"
  ls -1t "$HOME/backups/${db_name}-"*.sql.gz 2>/dev/null | tail -n +8 | xargs -r rm -f || true
  echo "Database backup: $dump"
fi

cd "$RELEASE_PATH"
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan optimize:clear
if ! "$PHP_BIN" artisan route:cache; then
  echo "route:cache failed; continuing with uncached routes"
  "$PHP_BIN" artisan route:clear
fi
"$PHP_BIN" artisan view:cache || echo "view:cache failed; views will compile on demand"

ln -sfn "$RELEASE_PATH" "$APP_ROOT/current"

if [[ -n "$DOCROOT" ]]; then
  if [[ -e "$DOCROOT" && ! -L "$DOCROOT" ]]; then
    echo "Refusing to replace a real directory at $DOCROOT" >&2
    echo "The release is active at $APP_ROOT/current. Point the domain at it only after moving the old folder aside." >&2
    exit 1
  fi
  ln -sfn "$APP_ROOT/current" "$DOCROOT"
fi

current_target="$(readlink -f "$APP_ROOT/current" || true)"
count=0
shopt -s nullglob
for release in $(ls -1dt "$APP_ROOT"/releases/*/); do
  count=$((count + 1))
  if [[ "$count" -le "$KEEP_RELEASES" ]]; then
    continue
  fi
  if [[ "$(readlink -f "$release")" == "$current_target" ]]; then
    continue
  fi
  rm -rf "$release"
done

echo "Deployed $RELEASE_PATH"
