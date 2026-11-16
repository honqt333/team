#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Khidmah Pro - Staging Atomic Release Deployment Script
# Strict Data Preservation & Zero-Downtime Symlink Switch
# ==============================================================================

DEPLOY_ROOT="${DEPLOY_ROOT:?ERROR: DEPLOY_ROOT variable is required}"
RELEASES_DIR="$DEPLOY_ROOT/releases"
SHARED_DIR="$DEPLOY_ROOT/shared"
BACKUPS_DIR="$SHARED_DIR/backups"
LOCK_FILE="/tmp/khidmh_staging_deploy.lock"

RELEASE_ID="${RELEASE_ID:?ERROR: RELEASE_ID variable is required}"
COMMIT_SHA="${COMMIT_SHA:-unknown}"
COMMIT_EPOCH="${COMMIT_EPOCH:-0}"
ARCHIVE_PATH="${ARCHIVE_PATH:?ERROR: ARCHIVE_PATH variable is required}"
ALLOW_OLDER="${ALLOW_OLDER:-false}"
SKIP_DB_BACKUP="${SKIP_DB_BACKUP:-false}"
SKIP_MIGRATIONS="${SKIP_MIGRATIONS:-false}"

# Safety Path Verification: Prevent traversal or dangerous roots
if [ "$DEPLOY_ROOT" = "/" ] || [ "$DEPLOY_ROOT" = "$HOME" ]; then
  echo "❌ CRITICAL ERROR: DEPLOY_ROOT cannot be '/' or '$HOME'."
  exit 1
fi

if echo "$DEPLOY_ROOT" | grep -qE '(\.\./|/\.\.)'; then
  echo "❌ CRITICAL ERROR: DEPLOY_ROOT cannot contain path traversal sequences."
  exit 1
fi

echo "=================================================================="
echo "🚀 Khidmah Pro - Starting Deployment: $RELEASE_ID"
echo "=================================================================="

# 1. Concurrency Lock
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
  echo "ERROR: Another deployment or rollback is currently in progress."
  exit 1
fi

# 2. Directory Structure Verification
mkdir -p "$RELEASES_DIR" "$SHARED_DIR" "$BACKUPS_DIR" "$DEPLOY_ROOT/tmp"

# 3. Shared Environment & Storage Verification
if [ ! -f "$SHARED_DIR/.env" ]; then
  echo "ERROR: Missing required configuration file: $SHARED_DIR/.env"
  exit 1
fi

if [ ! -d "$SHARED_DIR/storage" ]; then
  echo "ERROR: Missing shared storage directory: $SHARED_DIR/storage"
  exit 1
fi

# Ensure required persistent storage subdirectories exist
mkdir -p "$SHARED_DIR/storage/app/public" \
         "$SHARED_DIR/storage/framework/cache/data" \
         "$SHARED_DIR/storage/framework/sessions" \
         "$SHARED_DIR/storage/framework/views" \
         "$SHARED_DIR/storage/logs"

# 4. Downgrade Protection Check
META_FILE="$DEPLOY_ROOT/.current_meta"
if [ -f "$META_FILE" ] && [ "$ALLOW_OLDER" != "true" ]; then
  # shellcheck disable=SC1090
  source "$META_FILE"
  CURRENT_EPOCH="${CURRENT_EPOCH:-0}"
  if [ "$COMMIT_EPOCH" -gt 0 ] && [ "$CURRENT_EPOCH" -gt 0 ] && [ "$COMMIT_EPOCH" -lt "$CURRENT_EPOCH" ]; then
    echo "ERROR: Refusing to deploy older commit ($COMMIT_SHA, epoch=$COMMIT_EPOCH) over newer active commit (${CURRENT_SHA:-unknown}, epoch=$CURRENT_EPOCH)."
    echo "Pass allow_older=true or use rollback workflow if intentional."
    exit 1
  fi
fi

# 5. Archive Validation & Path Traversal Protection
if [ ! -f "$ARCHIVE_PATH" ]; then
  echo "ERROR: Archive not found at: $ARCHIVE_PATH"
  exit 1
fi

echo "==> Verifying archive integrity and security"
if ! tar -tzf "$ARCHIVE_PATH" > /dev/null 2>&1; then
  echo "ERROR: Corrupted or invalid gzip archive: $ARCHIVE_PATH"
  exit 1
fi

# Check for Path Traversal in archive members
ARCHIVE_MEMBERS=$(tar -tzf "$ARCHIVE_PATH")
if echo "$ARCHIVE_MEMBERS" | grep -qE '^/'; then
  echo "ERROR: Security violation: Absolute paths detected inside archive!"
  exit 1
fi

if echo "$ARCHIVE_MEMBERS" | grep -qE '(\.\./|/\.\.)'; then
  echo "ERROR: Security violation: Path traversal (..) detected inside archive!"
  exit 1
fi

# 6. Extract Release
RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"
if [ -d "$RELEASE_DIR" ]; then
  echo "ERROR: Target release directory already exists: $RELEASE_DIR"
  exit 1
fi

echo "==> Extracting release to $RELEASE_DIR"
mkdir -p "$RELEASE_DIR"
tar -xzf "$ARCHIVE_PATH" -C "$RELEASE_DIR" --no-same-owner --no-same-permissions

# Clean up archive from tmp
rm -f "$ARCHIVE_PATH"

# Verify Essential Build Artifacts
if [ ! -f "$RELEASE_DIR/public/build/manifest.json" ]; then
  echo "ERROR: Missing Vite build manifest: $RELEASE_DIR/public/build/manifest.json"
  rm -rf "$RELEASE_DIR"
  exit 1
fi

if [ ! -f "$RELEASE_DIR/vendor/autoload.php" ]; then
  echo "ERROR: Missing Composer vendor autoload: $RELEASE_DIR/vendor/autoload.php"
  rm -rf "$RELEASE_DIR"
  exit 1
fi

# Verify no escaping symlinks existed in the extracted archive
find "$RELEASE_DIR" -type l 2>/dev/null | while read -r sym; do
  target=$(readlink "$sym" || true)
  if [[ "$target" == /* ]] || [[ "$target" == *..* ]]; then
    echo "ERROR: Escaping symlink detected in release: $sym -> $target"
    rm -rf "$RELEASE_DIR"
    exit 1
  fi
done

# 7. Symlink Shared .env and Persistent Storage
echo "==> Linking shared .env and persistent storage"
rm -f "$RELEASE_DIR/.env"
ln -sfn "$SHARED_DIR/.env" "$RELEASE_DIR/.env"

rm -rf "$RELEASE_DIR/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_DIR/storage"

mkdir -p "$RELEASE_DIR/public"
rm -rf "$RELEASE_DIR/public/storage"
ln -sfn "$SHARED_DIR/storage/app/public" "$RELEASE_DIR/public/storage"

# Verify symlink integrity
for link in "$RELEASE_DIR/.env" "$RELEASE_DIR/storage" "$RELEASE_DIR/public/storage"; do
  if [ ! -e "$link" ]; then
    echo "ERROR: Broken symlink detected: $link"
    rm -rf "$RELEASE_DIR"
    exit 1
  fi
done

# 8. Pre-deployment Database Backup (Strict Fail-Fast & Safe Client Config)
if [ "$SKIP_DB_BACKUP" != "true" ]; then
  echo "==> Executing pre-deploy database backup"
  TMP_CNF=$(mktemp)
  chmod 600 "$TMP_CNF"
  # Trap to guarantee deletion of temporary credentials file on any exit
  trap 'rm -f "$TMP_CNF"' EXIT INT TERM ERR

  DB_HOST=$(grep -E '^DB_HOST=' "$SHARED_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true)
  DB_PORT=$(grep -E '^DB_PORT=' "$SHARED_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true)
  DB_DATABASE=$(grep -E '^DB_DATABASE=' "$SHARED_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true)
  DB_USERNAME=$(grep -E '^DB_USERNAME=' "$SHARED_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true)
  DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$SHARED_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true)

  if [ -n "$DB_DATABASE" ]; then
    if ! command -v mysqldump >/dev/null 2>&1; then
      echo "ERROR: mysqldump command not found in PATH! Aborting deployment to preserve database state."
      rm -rf "$RELEASE_DIR"
      exit 1
    fi

    {
      echo "[client]"
      echo "host=${DB_HOST:-127.0.0.1}"
      echo "port=${DB_PORT:-3306}"
      echo "user=${DB_USERNAME}"
      echo "password=${DB_PASSWORD}"
    } > "$TMP_CNF"

    BACKUP_FILE="$BACKUPS_DIR/pre_deploy_${RELEASE_ID}.sql.gz"
    echo "Dumping database '$DB_DATABASE' to '$BACKUP_FILE'..."

    # Execute mysqldump with pipefail so failure in mysqldump or gzip fails the command
    if ! mysqldump --defaults-extra-file="$TMP_CNF" --single-transaction --quick "$DB_DATABASE" | gzip > "$BACKUP_FILE"; then
      echo "ERROR: Pre-deploy mysqldump failed! Aborting deployment without switching release."
      rm -f "$BACKUP_FILE"
      rm -rf "$RELEASE_DIR"
      exit 1
    fi

    # Verify backup exists and is non-empty
    if [ ! -s "$BACKUP_FILE" ]; then
      echo "ERROR: Pre-deploy backup file is empty (0 bytes)! Aborting deployment."
      rm -f "$BACKUP_FILE"
      rm -rf "$RELEASE_DIR"
      exit 1
    fi

    # Verify backup archive integrity
    if ! gzip -t "$BACKUP_FILE" >/dev/null 2>&1; then
      echo "ERROR: Pre-deploy backup archive is corrupted! Aborting deployment."
      rm -f "$BACKUP_FILE"
      rm -rf "$RELEASE_DIR"
      exit 1
    fi

    echo "✅ Database backup verified and saved: $BACKUP_FILE"

    # [Policy Guard]: Automatic backup rotation is DISABLED during the staging foundation phase.
    # All pre-deploy backups are preserved indefinitely until manual review.
    # ls -1t "$BACKUPS_DIR"/pre_deploy_*.sql.gz 2>/dev/null | tail -n +8 | xargs -r rm -f || true

    rm -f "$TMP_CNF"
    trap - EXIT INT TERM ERR
  else
    echo "==> Skipping DB backup (DB_DATABASE not configured in $SHARED_DIR/.env)"
  fi
fi

# 9. Database Migrations (Strict: migrate --force only, NEVER fresh/refresh/seed)
if [ "$SKIP_MIGRATIONS" != "true" ]; then
  echo "==> Executing safe database migrations (migrate --force)"
  if ! php "$RELEASE_DIR/artisan" migrate --force; then
    echo "ERROR: Migration failed! Aborting deployment without switching active release."
    rm -rf "$RELEASE_DIR"
    exit 1
  fi
else
  echo "==> Skipping migrations as requested (SKIP_MIGRATIONS=true)"
fi

# 10. Warm Up Caches in New Release Directory
echo "==> Warming up application caches in release directory"
php "$RELEASE_DIR/artisan" config:clear
php "$RELEASE_DIR/artisan" route:clear
php "$RELEASE_DIR/artisan" view:clear
php "$RELEASE_DIR/artisan" event:clear
php "$RELEASE_DIR/artisan" optimize

# 11. Pre-Switch Smoke Check
echo "==> Performing pre-switch health verification"
if ! php "$RELEASE_DIR/artisan" about --only=environment > /dev/null 2>&1; then
  echo "ERROR: Release health check failed! Aborting switch."
  rm -rf "$RELEASE_DIR"
  exit 1
fi

test -f "$RELEASE_DIR/public/build/manifest.json" || {
  echo "ERROR: Missing Vite manifest in release: $RELEASE_DIR/public/build/manifest.json"
  rm -rf "$RELEASE_DIR"
  exit 1
}

# 12. Record Previous Release Before Switch
if [ -L "$DEPLOY_ROOT/current" ]; then
  PREV_TARGET=$(readlink -f "$DEPLOY_ROOT/current" || true)
  if [ -n "$PREV_TARGET" ] && [ -d "$PREV_TARGET" ]; then
    basename "$PREV_TARGET" > "$DEPLOY_ROOT/.previous_release"
  fi
fi

# 13. Atomic Switch of 'current' Symlink
echo "==> Atomically activating release $RELEASE_ID"
ln -sfn "$RELEASE_DIR" "$DEPLOY_ROOT/current_tmp"
mv -Tf "$DEPLOY_ROOT/current_tmp" "$DEPLOY_ROOT/current"

# 14. Restart Queue Workers
php "$DEPLOY_ROOT/current/artisan" queue:restart >/dev/null 2>&1 || true

# 15. Verify Active Release
ACTIVE_TARGET=$(readlink -f "$DEPLOY_ROOT/current")
if [ "$ACTIVE_TARGET" != "$RELEASE_DIR" ]; then
  echo "ERROR: Active release verification failed: current points to $ACTIVE_TARGET instead of $RELEASE_DIR"
  exit 1
fi

# Record current metadata
cat > "$META_FILE" <<EOF
CURRENT_RELEASE=$RELEASE_ID
CURRENT_SHA=$COMMIT_SHA
CURRENT_EPOCH=$COMMIT_EPOCH
DEPLOYED_AT=$(date -u +%Y-%m-%dT%H:%M:%SZ)
EOF

echo "==> Deployment successfully activated!"
php "$DEPLOY_ROOT/current/artisan" about --only=environment

# 16. Release Pruning Policy
# [Policy Guard]: Automatic release pruning is DISABLED during the staging foundation phase.
# All deployed releases are preserved to allow instantaneous rollback to any previous release.
#
# PREV_NAME=""
# [ -f "$DEPLOY_ROOT/.previous_release" ] && PREV_NAME=$(cat "$DEPLOY_ROOT/.previous_release")
# COUNT=0
# for dir in $(ls -1dt "$RELEASES_DIR"/*/ 2>/dev/null || true); do
#   dir_name=$(basename "$dir")
#   COUNT=$((COUNT + 1))
#   if [ "$COUNT" -gt "$KEEP_RELEASES" ] && [ "$dir_name" != "$RELEASE_ID" ] && [ "$dir_name" != "$PREV_NAME" ]; then
#     echo "==> Pruning old release: $dir_name"
#     rm -rf "$dir"
#   fi
# done

echo "=================================================================="
echo "✅ Release $RELEASE_ID deployed and active."
echo "=================================================================="
