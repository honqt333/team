#!/usr/bin/env bash
set -euo pipefail

DEPLOY_ROOT="${DEPLOY_ROOT:-$HOME/domains/khidmh.pro/deploy}"
RELEASES_DIR="$DEPLOY_ROOT/releases"
LOCK_FILE="/tmp/khidmh_deploy.lock"
TARGET_RELEASE="${TARGET_RELEASE:-}"

exec 200>"$LOCK_FILE"
if ! flock -n 200; then
  echo "ERROR: Another deployment or rollback is currently in progress."
  exit 1
fi

if [ -z "$TARGET_RELEASE" ]; then
  if [ ! -f "$DEPLOY_ROOT/.previous_release" ]; then
    echo "ERROR: No .previous_release recorded and TARGET_RELEASE was not provided."
    exit 1
  fi
  TARGET_RELEASE=$(cat "$DEPLOY_ROOT/.previous_release")
fi

TARGET_DIR="$RELEASES_DIR/$TARGET_RELEASE"
if [ ! -d "$TARGET_DIR" ]; then
  echo "ERROR: Target release directory does not exist: $TARGET_DIR"
  exit 1
fi

# Record current before rolling back
if [ -L "$DEPLOY_ROOT/current" ]; then
  CUR_TARGET=$(readlink -f "$DEPLOY_ROOT/current" || true)
  if [ -n "$CUR_TARGET" ] && [ -d "$CUR_TARGET" ]; then
    basename "$CUR_TARGET" > "$DEPLOY_ROOT/.previous_release"
  fi
fi

echo "==> Atomically rolling back current -> $TARGET_RELEASE (DB untouched)"
ln -sfn "$TARGET_DIR" "$DEPLOY_ROOT/current_tmp"
mv -Tf "$DEPLOY_ROOT/current_tmp" "$DEPLOY_ROOT/current"

php "$DEPLOY_ROOT/current/artisan" optimize:clear
php "$DEPLOY_ROOT/current/artisan" optimize
php "$DEPLOY_ROOT/current/artisan" queue:restart >/dev/null 2>&1 || true

ACTIVE_TARGET=$(readlink -f "$DEPLOY_ROOT/current")
test "$ACTIVE_TARGET" = "$TARGET_DIR" || { echo "ERROR: Rollback symlink verification failed"; exit 1; }
php "$DEPLOY_ROOT/current/artisan" about --only=environment

echo "==> Rollback to $TARGET_RELEASE completed successfully."
