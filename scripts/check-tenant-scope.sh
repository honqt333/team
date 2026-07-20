#!/bin/sh
set -e

cd "$(dirname "$0")/.."

# Only exempt true root models (no tenant_id, no center_id, no @bypass)
EXEMPTIONS="User.php|Role.php|Permission.php|Tenant.php|Center.php"

FAILED=0
TOTAL_CHECKED=0
TOTAL_EXEMPT=0

echo "=== Tenant Scope Audit ==="
echo ""

find app/Models -name "*.php" -not -path "*/Concerns/*" | while read -r f; do
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
done

echo ""
echo "=== Tenant Scope Audit Completed ==="
