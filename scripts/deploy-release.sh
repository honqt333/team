#!/usr/bin/env bash
set -euo pipefail

DEPLOY_ROOT="${DEPLOY_ROOT:-$HOME/domains/khidmh.pro/deploy}"
RELEASES_DIR="$DEPLOY_ROOT/releases"
SHARED_DIR="$DEPLOY_ROOT/shared"
BACKUPS_DIR="${BACKUPS_DIR:-$HOME/domains/khidmh.pro/backups/db}"
LOCK_FILE="/tmp/khidmh_deploy.lock"

RELEASE_ID="${RELEASE_ID:?RELEASE_ID is required}"
COMMIT_SHA="${COMMIT_SHA:-unknown}"
COMMIT_EPOCH="${COMMIT_EPOCH:-0}"
ARCHIVE_PATH="${ARCHIVE_PATH:?ARCHIVE_PATH is required}"
ALLOW_OLDER="${ALLOW_OLDER:-false}"
SKIP_DB_BACKUP="${SKIP_DB_BACKUP:-false}"
SKIP_MIGRATIONS="${SKIP_MIGRATIONS:-false}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

exec 200>"$LOCK_FILE"
if ! flock -n 200; then
  echo "ERROR: Another deployment is currently in progress."
  exit 1
fi

mkdir -p "$RELEASES_DIR" "$SHARED_DIR" "$BACKUPS_DIR" "$DEPLOY_ROOT/tmp"

# Downgrade protection
META_FILE="$DEPLOY_ROOT/.current_meta"
if [ -f "$META_FILE" ] && [ "$ALLOW_OLDER" != "true" ]; then
  # shellcheck disable=SC1090
  source "$META_FILE"
  CURRENT_EPOCH="${CURRENT_EPOCH:-0}"
  if [ "$COMMIT_EPOCH" -gt 0 ] && [ "$CURRENT_EPOCH" -gt 0 ] && [ "$COMMIT_EPOCH" -lt "$CURRENT_EPOCH" ]; then
    echo "ERROR: Refusing to deploy older commit ($COMMIT_SHA, epoch=$COMMIT_EPOCH) over newer active commit (${CURRENT_SHA:-unknown}, epoch=$CURRENT_EPOCH)."
    echo "Set ALLOW_OLDER=true or use rollback.yml if intentional."
    exit 1
  fi
fi

# Verify shared resources exist
test -f "$SHARED_DIR/.env" || { echo "ERROR: Missing $SHARED_DIR/.env"; exit 1; }
test -d "$SHARED_DIR/storage" || { echo "ERROR: Missing $SHARED_DIR/storage"; exit 1; }

RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"
if [ -d "$RELEASE_DIR" ]; then
  echo "ERROR: Release directory already exists: $RELEASE_DIR"
  exit 1
fi

echo "==> Extracting release $RELEASE_ID"
mkdir -p "$RELEASE_DIR"
tar -xzf "$ARCHIVE_PATH" -C "$RELEASE_DIR"

echo "==> Linking shared .env and storage"
rm -f "$RELEASE_DIR/.env"
ln -sfn "$SHARED_DIR/.env" "$RELEASE_DIR/.env"

rm -rf "$RELEASE_DIR/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_DIR/storage"

rm -rf "$RELEASE_DIR/public/storage"
ln -sfn "$SHARED_DIR/storage/app/public" "$RELEASE_DIR/public/storage"

# Verify symlinks resolve cleanly
for link in "$RELEASE_DIR/.env" "$RELEASE_DIR/storage" "$RELEASE_DIR/public/storage"; do
  if [ ! -e "$link" ]; then
    echo "ERROR: Broken symlink detected: $link"
    rm -rf "$RELEASE_DIR"
    exit 1
  fi
done

# Safe DB Backup using temporary defaults-extra-file (never exposes password on CLI)
if [ "$SKIP_DB_BACKUP" != "true" ]; then
  echo "==> Taking pre-deploy database backup"
  TMP_CNF=$(mktemp)
  chmod 600 "$TMP_CNF"
  trap 'rm -f "$TMP_CNF"' EXIT

  DB_HOST=$(grep -E '^DB_HOST=' "$SHARED_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
  DB_PORT=$(grep -E '^DB_PORT=' "$SHARED_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
  DB_DATABASE=$(grep -E '^DB_DATABASE=' "$SHARED_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
  DB_USERNAME=$(grep -E '^DB_USERNAME=' "$SHARED_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
  DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$SHARED_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")

  {
    echo "[client]"
    echo "host=${DB_HOST:-127.0.0.1}"
    echo "port=${DB_PORT:-3306}"
    echo "user=${DB_USERNAME}"
    echo "password=${DB_PASSWORD}"
  } > "$TMP_CNF"

  BACKUP_FILE="$BACKUPS_DIR/pre_deploy_${RELEASE_ID}.sql.gz"
  mysqldump --defaults-extra-file="$TMP_CNF" --single-transaction --quick "$DB_DATABASE" | gzip > "$BACKUP_FILE"
  rm -f "$TMP_CNF"
  echo "Database backup saved: $BACKUP_FILE"

  # Rotate DB backups (keep latest 7)
  ls -1t "$BACKUPS_DIR"/pre_deploy_*.sql.gz 2>/dev/null | tail -n +8 | xargs -r rm -f
else
  echo "==> Skipping DB backup (SKIP_DB_BACKUP=true)"
fi

if [ "$SKIP_MIGRATIONS" != "true" ]; then
  echo "==> Running migrations"
  php "$RELEASE_DIR/artisan" migrate --force
else
  echo "==> Skipping migrations (SKIP_MIGRATIONS=true)"
fi

echo "==> Building Laravel caches in new release"
php "$RELEASE_DIR/artisan" config:clear
php "$RELEASE_DIR/artisan" route:clear
php "$RELEASE_DIR/artisan" view:clear
php "$RELEASE_DIR/artisan" event:clear
php "$RELEASE_DIR/artisan" optimize

# Save previous release before atomic switch
if [ -L "$DEPLOY_ROOT/current" ]; then
  PREV_TARGET=$(readlink -f "$DEPLOY_ROOT/current" || true)
  if [ -n "$PREV_TARGET" ] && [ -d "$PREV_TARGET" ]; then
    basename "$PREV_TARGET" > "$DEPLOY_ROOT/.previous_release"
  fi
fi

echo "==> Atomically switching current symlink -> $RELEASE_ID"
ln -sfn "$RELEASE_DIR" "$DEPLOY_ROOT/current_tmp"
mv -Tf "$DEPLOY_ROOT/current_tmp" "$DEPLOY_ROOT/current"

# Restart queue workers if running
php "$DEPLOY_ROOT/current/artisan" queue:restart >/dev/null 2>&1 || true

echo "==> Verifying active release"
ACTIVE_TARGET=$(readlink -f "$DEPLOY_ROOT/current")
test "$ACTIVE_TARGET" = "$RELEASE_DIR" || { echo "ERROR: current symlink mismatch"; exit 1; }
test -f "$DEPLOY_ROOT/current/public/build/manifest.json" || { echo "ERROR: Missing Vite manifest.json"; exit 1; }
test -L "$DEPLOY_ROOT/current/public/storage" && test -d "$DEPLOY_ROOT/current/public/storage" || { echo "ERROR: Invalid public/storage symlink"; exit 1; }
php "$DEPLOY_ROOT/current/artisan" about --only=environment

# Record current metadata
cat > "$META_FILE" <<EOF
CURRENT_RELEASE=$RELEASE_ID
CURRENT_SHA=$COMMIT_SHA
CURRENT_EPOCH=$COMMIT_EPOCH
DEPLOYED_AT=$(date -u +%Y-%m-%dT%H:%M:%SZ)
EOF

# Cleanup archive in tmp
rm -f "$ARCHIVE_PATH"

# Prune old releases (keep latest KEEP_RELEASES, never delete active or previous)
PREV_NAME=""
[ -f "$DEPLOY_ROOT/.previous_release" ] && PREV_NAME=$(cat "$DEPLOY_ROOT/.previous_release")
COUNT=0
for dir in $(ls -1dt "$RELEASES_DIR"/*/ 2>/dev/null); do
  dir_name=$(basename "$dir")
  COUNT=$((COUNT + 1))
  if [ "$COUNT" -gt "$KEEP_RELEASES" ] && [ "$dir_name" != "$RELEASE_ID" ] && [ "$dir_name" != "$PREV_NAME" ]; then
    rm -rf "$dir"
  fi
done

echo "==> Deployment of $RELEASE_ID completed successfully."
