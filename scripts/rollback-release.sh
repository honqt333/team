#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Khidmah Pro - Staging Rollback Script
# Code & Assets Rollback Only (Database Untouched)
# ==============================================================================

DEPLOY_ROOT="${DEPLOY_ROOT:?ERROR: DEPLOY_ROOT variable is required}"
RELEASES_DIR="$DEPLOY_ROOT/releases"
LOCK_FILE="/tmp/khidmh_staging_deploy.lock"
TARGET_RELEASE="${TARGET_RELEASE:-}"

echo "=================================================================="
echo "🔄 Khidmah Pro - Starting Release Rollback"
echo "=================================================================="

# 1. Concurrency Lock
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
  echo "ERROR: Another deployment or rollback is currently in progress."
  exit 1
fi

# 2. Determine Target Release
if [ -z "$TARGET_RELEASE" ]; then
  if [ ! -f "$DEPLOY_ROOT/.previous_release" ]; then
    echo "ERROR: No previous release recorded and TARGET_RELEASE was not specified."
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

# 3. Record Current Before Switch
if [ -L "$DEPLOY_ROOT/current" ]; then
  CUR_TARGET=$(readlink -f "$DEPLOY_ROOT/current" || true)
  if [ -n "$CUR_TARGET" ] && [ -d "$CUR_TARGET" ]; then
    basename "$CUR_TARGET" > "$DEPLOY_ROOT/.previous_release"
  fi
fi

# 4. Atomic Switch to Target Release
echo "==> Atomically switching current -> $TARGET_RELEASE (Database untouched)"
ln -sfn "$TARGET_DIR" "$DEPLOY_ROOT/current_tmp"
mv -Tf "$DEPLOY_ROOT/current_tmp" "$DEPLOY_ROOT/current"

# 5. Refresh Caches and Restart Workers
php "$DEPLOY_ROOT/current/artisan" optimize:clear
php "$DEPLOY_ROOT/current/artisan" optimize
php "$DEPLOY_ROOT/current/artisan" queue:restart >/dev/null 2>&1 || true

# 6. Verify Active Release
ACTIVE_TARGET=$(readlink -f "$DEPLOY_ROOT/current")
if [ "$ACTIVE_TARGET" != "$TARGET_DIR" ]; then
  echo "ERROR: Rollback symlink verification failed!"
  exit 1
fi

php "$DEPLOY_ROOT/current/artisan" about --only=environment

echo "=================================================================="
echo "✅ Rollback to $TARGET_RELEASE completed successfully."
echo "ℹ️  Database was NOT touched. All tenant data and templates preserved."
echo "=================================================================="
