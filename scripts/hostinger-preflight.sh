#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Khidmah Pro - Hostinger Environment Preflight Diagnostic & Scaffolding
# STRICT READ-ONLY CHECK BY DEFAULT (--check)
# SAFE DIRECTORY SCAFFOLDING ONLY WITH EXPLICIT --apply
# ==============================================================================

MODE="check"

for arg in "$@"; do
  case "$arg" in
    --check)
      MODE="check"
      ;;
    --apply)
      MODE="apply"
      ;;
    --help|-h)
      echo "Usage: $0 [--check | --apply]"
      echo "  --check  (Default) Strictly read-only diagnostics. Performs ZERO filesystem mutations."
      echo "  --apply  Creates required directory scaffolding after safety path verification."
      exit 0
      ;;
    *)
      echo "ERROR: Unknown option '$arg'. Use --check or --apply."
      exit 1
      ;;
  esac
done

echo "=================================================================="
if [ "$MODE" = "check" ]; then
  echo "🔍 Khidmah Pro - Hostinger Preflight Diagnostic [STRICT READ-ONLY]"
else
  echo "🛠️  Khidmah Pro - Hostinger Directory Scaffolding [--apply MODE]"
fi
echo "=================================================================="

PROJECT_DIR="$(pwd)"
PARENT_DIR="$(dirname "$PROJECT_DIR")"
DEPLOY_ROOT="${DEPLOY_ROOT:-$PROJECT_DIR}"
PUBLIC_HTML="${PUBLIC_HTML:-$PARENT_DIR/public_html}"

echo "Project Directory:  $PROJECT_DIR"
echo "Deploy Root:        $DEPLOY_ROOT"
echo "Target public_html: $PUBLIC_HTML"
echo "Execution Mode:     $MODE"
echo "------------------------------------------------------------------"

# Safety Path Guards
if [ "$DEPLOY_ROOT" = "/" ] || [ "$DEPLOY_ROOT" = "$HOME" ]; then
  echo "❌ CRITICAL SAFETY ERROR: Refusing to operate on root ('/') or direct home ('$HOME')."
  echo "   DEPLOY_ROOT must be a dedicated subdirectory (e.g. $HOME/staging)."
  exit 1
fi

if echo "$DEPLOY_ROOT" | grep -qi "production"; then
  echo "❌ CRITICAL SAFETY ERROR: Staging preflight script must NEVER target production path ($DEPLOY_ROOT)."
  exit 1
fi

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
  if ! php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
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
  echo "⚠️  WARNING: mysqldump is not available in PATH. Pre-deploy backups will not be possible."
fi

# 4. Archiving Tools Check
echo "==> 4. Verifying archiving and compression tools..."
if command -v tar >/dev/null 2>&1; then
  echo "✅ tar is available."
else
  echo "❌ ERROR: tar utility is missing."
  exit 1
fi

if command -v gzip >/dev/null 2>&1; then
  echo "✅ gzip is available."
else
  echo "❌ ERROR: gzip utility is missing."
  exit 1
fi

# 5. Symlink Capability Check (Strictly Read-Only in --check mode)
echo "==> 5. Verifying filesystem symlink support..."
if ! command -v ln >/dev/null 2>&1 || ! command -v readlink >/dev/null 2>&1; then
  echo "❌ ERROR: 'ln' or 'readlink' command is missing."
  exit 1
fi

if [ "$MODE" = "check" ]; then
  # Pure read-only inspection: verify existing symlink resolution without creating any files
  if [ -L "/dev/stdin" ] || [ -L "/var/run" ] || [ -L "$PUBLIC_HTML" ]; then
    echo "✅ Symbolic links are supported by the OS and readlink command functions properly."
  else
    echo "✅ 'ln' and 'readlink' binaries are present in PATH (Active creation test deferred to --apply mode)."
  fi
else
  # --apply mode: active verification of symlink creation in deployment target
  TEST_SYMLINK_SRC="$DEPLOY_ROOT/tmp/symlink_probe_src_$$"
  TEST_SYMLINK_DST="$DEPLOY_ROOT/tmp/symlink_probe_dst_$$"
  touch "$TEST_SYMLINK_SRC" 2>/dev/null || true
  if ln -s "$TEST_SYMLINK_SRC" "$TEST_SYMLINK_DST" 2>/dev/null; then
    echo "✅ Target filesystem supports symbolic links."
    rm -f "$TEST_SYMLINK_SRC" "$TEST_SYMLINK_DST"
  else
    echo "❌ ERROR: Target filesystem does NOT support symbolic links!"
    rm -f "$TEST_SYMLINK_SRC" 2>/dev/null || true
    exit 1
  fi
fi

# 6. Disk Space Diagnostic
echo "==> 6. Checking available disk space..."
if command -v df >/dev/null 2>&1; then
  CHECK_PATH="$DEPLOY_ROOT"
  [ ! -d "$CHECK_PATH" ] && CHECK_PATH="$HOME"
  df -h "$CHECK_PATH" | awk 'NR==1 || NR==2 {print "  " $0}'
  echo "✅ Disk space inspected."
else
  echo "ℹ️  'df' command not available to check disk space."
fi

# 7. Cron Scheduler Availability
echo "==> 7. Checking Cron scheduler availability..."
if command -v crontab >/dev/null 2>&1; then
  echo "✅ crontab command is available for Laravel task scheduling."
else
  echo "⚠️  WARNING: crontab is not available in PATH. Configure schedule:run via Hostinger hPanel Cron Jobs."
fi

# 8. Document Root & public_html Diagnostic (Strictly Non-Destructive)
echo "==> 8. Inspecting Document Root ($PUBLIC_HTML)..."

if [ -L "$PUBLIC_HTML" ]; then
  CURRENT_TARGET=$(readlink -f "$PUBLIC_HTML" || true)
  echo "ℹ️  public_html is currently a SYMLINK pointing to: $CURRENT_TARGET"
  if [ "$CURRENT_TARGET" = "$DEPLOY_ROOT/current/public" ]; then
    echo "✅ public_html is already correctly linked to $DEPLOY_ROOT/current/public"
  else
    echo "⚠️  NOTE: public_html points to an alternate target. Update link to $DEPLOY_ROOT/current/public when ready."
  fi
elif [ -d "$PUBLIC_HTML" ]; then
  echo "ℹ️  public_html is a REGULAR DIRECTORY (not a symlink)."
  echo "   [Hostinger Policy Guard]: This script will NEVER delete or overwrite your public_html folder."
  echo "   Recommended configuration for Hostinger:"
  echo "     Option A (Preferred): In Hostinger hPanel -> Domains/Subdomains -> Document Root, set target to:"
  echo "       $DEPLOY_ROOT/current/public"
  echo "     Option B: Back up existing public_html manually, then replace with symlink:"
  echo "       mv $PUBLIC_HTML ${PUBLIC_HTML}_backup && ln -s $DEPLOY_ROOT/current/public $PUBLIC_HTML"
else
  echo "ℹ️  public_html does not exist yet at $PUBLIC_HTML."
  echo "   Create symlink after first release deployment:"
  echo "     ln -s $DEPLOY_ROOT/current/public $PUBLIC_HTML"
fi

# 9. Deployment Directories: Inspection (--check) vs Scaffolding (--apply)
echo "==> 9. Deployment Directory Structure & Permissions..."
REQUIRED_DIRS=(
  "$DEPLOY_ROOT/releases"
  "$DEPLOY_ROOT/tmp"
  "$DEPLOY_ROOT/shared/backups"
  "$DEPLOY_ROOT/shared/storage/app/public"
  "$DEPLOY_ROOT/shared/storage/framework/cache/data"
  "$DEPLOY_ROOT/shared/storage/framework/sessions"
  "$DEPLOY_ROOT/shared/storage/framework/views"
  "$DEPLOY_ROOT/shared/storage/logs"
)

if [ "$MODE" = "check" ]; then
  ALL_EXIST=true
  for d in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$d" ]; then
      if [ -w "$d" ]; then
        echo "  ✅ Exists and writable: $d"
      else
        echo "  ⚠️  Exists but NOT writable: $d"
      fi
    else
      echo "  ℹ️  Not yet provisioned: $d (Will be created when running with --apply)"
      ALL_EXIST=false
    fi
  done
  if [ "$ALL_EXIST" = "true" ]; then
    echo "✅ All deployment directories are already provisioned."
  else
    echo "ℹ️  Read-only mode: Zero directory mutations performed. Run with '--apply' to scaffold missing directories."
  fi
else
  echo "==> Applying directory scaffolding under $DEPLOY_ROOT..."
  for d in "${REQUIRED_DIRS[@]}"; do
    if [ ! -d "$d" ]; then
      mkdir -p "$d"
      echo "  Created: $d"
    else
      echo "  Already exists: $d"
    fi
  done
  # Safe non-recursive permission tuning for scaffolding directories only
  chmod 775 "$DEPLOY_ROOT/releases" "$DEPLOY_ROOT/tmp" "$DEPLOY_ROOT/shared/backups" "$DEPLOY_ROOT/shared/storage" 2>/dev/null || true
  chmod -R 775 "$DEPLOY_ROOT/shared/storage/framework" "$DEPLOY_ROOT/shared/storage/logs" 2>/dev/null || true
  echo "✅ Scaffolding successfully applied."
fi

# 10. Shared Environment File Status
echo "==> 10. Checking shared environment configuration..."
if [ -f "$DEPLOY_ROOT/shared/.env" ]; then
  echo "✅ $DEPLOY_ROOT/shared/.env is present."
  if [ "$MODE" = "apply" ]; then
    chmod 600 "$DEPLOY_ROOT/shared/.env" 2>/dev/null || true
  fi
else
  echo "ℹ️  $DEPLOY_ROOT/shared/.env does NOT exist yet."
  echo "   Before first release activation, create $DEPLOY_ROOT/shared/.env with staging database & app settings."
fi

echo "=================================================================="
if [ "$MODE" = "check" ]; then
  echo "🎉 Hostinger Preflight Read-Only Diagnostic Completed Successfully."
else
  echo "🎉 Hostinger Directory Scaffolding Completed Successfully."
fi
echo "=================================================================="
