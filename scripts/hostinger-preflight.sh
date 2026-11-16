#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Khidmah Pro - Hostinger Preflight & Directory Setup Script
# STRICT NON-DESTRUCTIVE DIAGNOSTIC & SCAFFOLDING ONLY
# ==============================================================================

echo "=================================================================="
echo "🔍 Khidmah Pro - Hostinger Environment Preflight Diagnostic"
echo "=================================================================="

PROJECT_DIR="$(pwd)"
PARENT_DIR="$(dirname "$PROJECT_DIR")"
DEPLOY_ROOT="${DEPLOY_ROOT:-$PROJECT_DIR}"
PUBLIC_HTML="${PUBLIC_HTML:-$PARENT_DIR/public_html}"

echo "Project Directory: $PROJECT_DIR"
echo "Deploy Root:       $DEPLOY_ROOT"
echo "Target public_html: $PUBLIC_HTML"
echo "------------------------------------------------------------------"

# 1. PHP Version & CLI Check
echo "==> 1. Checking PHP CLI version..."
PHP_VER=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "not_found")
echo "Installed PHP CLI: $PHP_VER"

if [ "$PHP_VER" = "not_found" ]; then
  echo "❌ ERROR: PHP CLI is not installed or not in PATH."
  exit 1
fi

PHP_MAJOR_MINOR=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
if php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'; then
  echo "✅ PHP version ($PHP_MAJOR_MINOR) satisfies Laravel 12 requirement (>= 8.2)."
else
  echo "❌ ERROR: PHP version $PHP_VER is below required 8.2+."
  exit 1
fi

# 2. PHP Extensions Check
echo "==> 2. Verifying required PHP extensions..."
REQUIRED_EXTS=("pdo_mysql" "bcmath" "intl" "mbstring" "gd" "zip" "xml" "curl")
MISSING_EXTS=()

for ext in "${REQUIRED_EXTS[@]}"; do
  if ! php -m | grep -qi "^$ext$"; then
    MISSING_EXTS+=("$ext")
  fi
done

if [ ${#MISSING_EXTS[@]} -eq 0 ]; then
  echo "✅ All required PHP extensions are loaded: ${REQUIRED_EXTS[*]}"
else
  echo "❌ ERROR: Missing required PHP extensions: ${MISSING_EXTS[*]}"
  exit 1
fi

# 3. Database Tools Check
echo "==> 3. Verifying database client tools..."
if command -v mysqldump >/dev/null 2>&1; then
  echo "✅ mysqldump is available for pre-deploy backups."
else
  echo "⚠️  WARNING: mysqldump is not available in PATH. Pre-deploy backups will be skipped."
fi

# 4. Document Root & public_html Diagnostic (Strictly Non-Destructive)
echo "==> 4. Inspecting Document Root ($PUBLIC_HTML)..."

if [ -L "$PUBLIC_HTML" ]; then
  CURRENT_TARGET=$(readlink -f "$PUBLIC_HTML" || true)
  echo "ℹ️  public_html is currently a SYMLINK pointing to: $CURRENT_TARGET"
  if [ "$CURRENT_TARGET" = "$DEPLOY_ROOT/current/public" ]; then
    echo "✅ public_html is already correctly linked to $DEPLOY_ROOT/current/public"
  else
    echo "⚠️  NOTE: public_html points to an alternate target. Update link to $DEPLOY_ROOT/current/public when ready."
  fi
elif [ -d "$PUBLIC_HTML" ]; then
  echo "⚠️  public_html is a REGULAR DIRECTORY (not a symlink)."
  echo "   [Hostinger Policy Guard]: This script will NOT delete or overwrite your public_html folder."
  echo "   Options for linking web traffic to Khidmah Pro releases:"
  echo "     Option A (Recommended): In Hostinger hPanel -> Advanced -> Document Root, set the root to:"
  echo "       $DEPLOY_ROOT/current/public"
  echo "     Option B: If Document Root cannot be changed in hPanel, back up public_html manually, then link:"
  echo "       mv $PUBLIC_HTML ${PUBLIC_HTML}_backup"
  echo "       ln -s $DEPLOY_ROOT/current/public $PUBLIC_HTML"
else
  echo "ℹ️  public_html does not exist yet at $PUBLIC_HTML."
  echo "   You can create the symlink once the first release is deployed:"
  echo "     ln -s $DEPLOY_ROOT/current/public $PUBLIC_HTML"
fi

# 5. Directory Structure Scaffolding
echo "==> 5. Verifying and scaffolding deployment directories..."
mkdir -p "$DEPLOY_ROOT/releases"
mkdir -p "$DEPLOY_ROOT/tmp"
mkdir -p "$DEPLOY_ROOT/shared/backups"
mkdir -p "$DEPLOY_ROOT/shared/storage/app/public"
mkdir -p "$DEPLOY_ROOT/shared/storage/framework/cache/data"
mkdir -p "$DEPLOY_ROOT/shared/storage/framework/sessions"
mkdir -p "$DEPLOY_ROOT/shared/storage/framework/views"
mkdir -p "$DEPLOY_ROOT/shared/storage/logs"

chmod -R 775 "$DEPLOY_ROOT/shared/storage" "$DEPLOY_ROOT/shared/backups" "$DEPLOY_ROOT/tmp" 2>/dev/null || true
echo "✅ Directory structure verified under $DEPLOY_ROOT."

# 6. Shared Environment File Status
echo "==> 6. Checking shared environment configuration..."
if [ -f "$DEPLOY_ROOT/shared/.env" ]; then
  echo "✅ $DEPLOY_ROOT/shared/.env is present."
  # Quick permissions check
  chmod 600 "$DEPLOY_ROOT/shared/.env" 2>/dev/null || true
else
  echo "⚠️  ATTENTION: $DEPLOY_ROOT/shared/.env does NOT exist yet!"
  echo "   Before running the first deployment, you must create $DEPLOY_ROOT/shared/.env"
  echo "   with staging database credentials, APP_KEY, and environment settings."
fi

echo "=================================================================="
echo "🎉 Hostinger Preflight Diagnostic Completed."
echo "=================================================================="
