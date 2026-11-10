#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

# Only exempt true root models (no tenant_id, no center_id, no @bypass)
EXEMPTIONS="User.php|Role.php|Permission.php|Tenant.php|Center.php"

[ -d app/Models ] || { echo "Model directory missing" >&2; exit 2; }

FAILED=0
TOTAL_CHECKED=0
TOTAL_EXEMPT=0

echo "=== Tenant Scope Audit ==="
echo ""

while IFS= read -r -d '' f; do
    [ -f "$f" ] || continue
    BASENAME=$(basename "$f")
    TOTAL_CHECKED=$((TOTAL_CHECKED+1))

    # Check if exempted
    if echo "$EXEMPTIONS" | grep -qE "^$BASENAME$"; then
        TOTAL_EXEMPT=$((TOTAL_EXEMPT+1))
        echo "  ⊘ $BASENAME (root model - exempt)"
        continue
    fi

    # Check if has @bypass-tenancy-scanner
    if grep -q "@bypass-tenancy-scanner" "$f"; then
        TOTAL_EXEMPT=$((TOTAL_EXEMPT+1))
        echo "  ⊘ $BASENAME (explicit bypass)"
        continue
    fi

    # Check if model has tenant_id or center_id
    HAS_TENANT=$(grep -c "tenant_id" "$f" || true)
    HAS_CENTER=$(grep -c "center_id" "$f" || true)

    if [ "$HAS_TENANT" -gt 0 ] || [ "$HAS_CENTER" -gt 0 ]; then
        # Must use TenantScoped or CenterScoped trait
        if ! grep -qE "TenantScoped|CenterScoped" "$f"; then
            echo "  ❌ $BASENAME (has tenant/center_id but no scope trait)"
            FAILED=$((FAILED+1))
        else
            echo "  ✅ $BASENAME (scoped)"
        fi
    else
        echo "  · $BASENAME (no tenant/center - OK)"
    fi
done < <(find app/Models -name "*.php" -not -path "*/Concerns/*" -print0)

echo ""
echo "=== Tenant Scope Audit Completed ==="

printf "Checked: %s; exempt: %s; violations: %s\n" "$TOTAL_CHECKED" "$TOTAL_EXEMPT" "$FAILED"
if [ "$TOTAL_CHECKED" -eq 0 ]; then
    echo "No models checked" >&2
    exit 2
fi
if [ "$FAILED" -gt 0 ]; then
    exit 1
fi
