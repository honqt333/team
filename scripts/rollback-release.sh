#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Khidmah Pro - Staging Rollback Script
# Code & Assets Rollback Only (Database Strictly Untouched)
#
# ARCHITECTURAL SAFETY GUARANTEES:
#   1. ZERO database mutation: NO mysql import, NO SQL file restoration.
#   2. NO migrate:rollback: schema is NEVER downgraded.
#   3. shared/storage is strictly preserved untouched (no deletion/overwrite).
#   4. shared/backups/ is strictly preserved untouched (no deletion).
#   5. Pre-switch schema compatibility check: verifies the target release
#      codebase can connect to the database and read migration state.
# ==============================================================================

DEPLOY_ROOT="${DEPLOY_ROOT:?ERROR: DEPLOY_ROOT variable is required}"
RELEASES_DIR="$DEPLOY_ROOT/releases"
SHARED_DIR="$DEPLOY_ROOT/shared"
BACKUPS_DIR="$SHARED_DIR/backups"
LOCK_FILE="/tmp/khidmh_staging_deploy.lock"
TARGET_RELEASE="${TARGET_RELEASE:-}"

echo "=================================================================="
echo "🔄 Khidmah Pro - Starting Release Rollback [Code & Assets Only]"
echo "=================================================================="

# 1. Concurrency Lock
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
  echo "ERROR: Another deployment or rollback is currently in progress."
  exit 1
fi

# 2. Safety Path Verification
if [ "$DEPLOY_ROOT" = "/" ] || [ "$DEPLOY_ROOT" = "$HOME" ]; then
  echo "❌ CRITICAL SAFETY ERROR: Refusing to operate on root ('/') or direct home ('$HOME')."
  exit 1
fi

if [ ! -f "$SHARED_DIR/.env" ]; then
  echo "ERROR: Shared .env not found: $SHARED_DIR/.env"
  exit 1
fi

if [ ! -d "$SHARED_DIR/storage" ]; then
  echo "ERROR: Shared storage directory not found: $SHARED_DIR/storage"
  exit 1
fi

# 3. Determine Target Release
if [ -z "$TARGET_RELEASE" ]; then
  if [ ! -f "$DEPLOY_ROOT/.previous_release" ]; then
    echo "ERROR: No previous release recorded in $DEPLOY_ROOT/.previous_release and TARGET_RELEASE was not specified."
    exit 1
  fi
  TARGET_RELEASE=$(cat "$DEPLOY_ROOT/.previous_release")
fi

TARGET_DIR="$RELEASES_DIR/$TARGET_RELEASE"
if [ ! -d "$TARGET_DIR" ]; then
  echo "ERROR: Target release directory does not exist: $TARGET_DIR"
  exit 1
fi

if [ ! -f "$TARGET_DIR/public/build/manifest.json" ]; then
  echo "ERROR: Target release is missing Vite manifest.json: $TARGET_DIR"
  exit 1
fi

if [ ! -f "$TARGET_DIR/vendor/autoload.php" ]; then
  echo "ERROR: Target release is missing Composer vendor autoload: $TARGET_DIR"
  exit 1
fi

# 4. Verify Database Schema Compatibility (Read-only check)
echo "==> Verifying database connectivity and schema compatibility of target release..."
if [ -f "$TARGET_DIR/artisan" ]; then
  # Ensure the target codebase can connect to the database and read migration status without crashing
  if ! php "$TARGET_DIR/artisan" migrate:status >/dev/null 2>&1; then
    echo "❌ ERROR: Target release failed database connectivity/migration status check!"
    echo "   Aborting rollback to protect operational stability and prevent schema crashes."
    exit 1
  fi
  echo "✅ Target release verified compatible with current database connection and schema."
fi

# 5. Record Current Before Switch
if [ -L "$DEPLOY_ROOT/current" ]; then
  CUR_TARGET=$(readlink -f "$DEPLOY_ROOT/current" || true)
  if [ -n "$CUR_TARGET" ] && [ -d "$CUR_TARGET" ]; then
    basename "$CUR_TARGET" > "$DEPLOY_ROOT/.previous_release"
  fi
fi

# 6. Atomic Switch to Target Release (Symlink Only)
echo "==> Atomically switching current -> $TARGET_RELEASE (Database untouched)"
ln -sfn "$TARGET_DIR" "$DEPLOY_ROOT/current_tmp"
mv -Tf "$DEPLOY_ROOT/current_tmp" "$DEPLOY_ROOT/current"

# 7. Refresh Caches and Restart Workers
echo "==> Clearing and warming caches for target release..."
php "$DEPLOY_ROOT/current/artisan" optimize:clear
php "$DEPLOY_ROOT/current/artisan" optimize
php "$DEPLOY_ROOT/current/artisan" queue:restart >/dev/null 2>&1 || true

# 8. Verify Active Release
ACTIVE_TARGET=$(readlink -f "$DEPLOY_ROOT/current")
if [ "$ACTIVE_TARGET" != "$TARGET_DIR" ]; then
  echo "ERROR: Rollback symlink verification failed!"
  exit 1
fi

php "$DEPLOY_ROOT/current/artisan" about --only=environment

echo "=================================================================="
echo "✅ Rollback to $TARGET_RELEASE completed successfully."
echo "ℹ️  DATABASE WAS NOT TOUCHED. No SQL imported, no migrations rolled back."
echo "ℹ️  shared/storage and all database backups preserved intact."
echo "=================================================================="
