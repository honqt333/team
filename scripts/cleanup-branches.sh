#!/usr/bin/env bash
#
# scripts/cleanup-branches.sh
#
# Delete merged + stale local and remote feature branches.
# - Local: any branch whose upstream no longer exists.
# - Remote: any branch in `origin/` that has not seen a commit in
#   N days (default 60) and is not `main` or `develop`.
#
# Usage:
#   scripts/cleanup-branches.sh          # dry-run, prints the list
#   scripts/cleanup-branches.sh --apply  # actually delete them
#   scripts/cleanup-branches.sh --days=30
#
# Always run dry-run first. There's no undo for `git push origin
# :branch-name`.

set -euo pipefail

APPLY=0
DAYS=60

for arg in "$@"; do
    case "$arg" in
        --apply) APPLY=1 ;;
        --days=*) DAYS="${arg#*=}" ;;
        --help|-h)
            echo "Usage: $0 [--apply] [--days=N]"
            echo "  --apply   actually delete (default: dry-run)"
            echo "  --days=N  how old before a remote branch is stale (default: 60)"
            exit 0
            ;;
        *)
            echo "Unknown arg: $arg" >&2
            exit 1
            ;;
    esac
done

# --- 1. Stale LOCAL branches (upstream gone) ---
echo "=== Local branches with no upstream ==="
LOCAL_STALE=$(git for-each-ref --format='%(refname:short) %(upstream:short)' refs/heads \
    | awk '$2 == "" { print $1 }' || true)

if [ -z "$LOCAL_STALE" ]; then
    echo "  (none)"
else
    echo "$LOCAL_STALE" | sed 's/^/  /'
fi

# --- 2. Stale REMOTE branches (no commit in N days, not main/develop) ---
echo ""
echo "=== Remote branches with no commit in $DAYS days (excluding main, develop) ==="
CUTOFF=$(date -v -"${DAYS}"d +%s 2>/dev/null || date -d "${DAYS} days ago" +%s)
REMOTE_STALE=()
while read -r ref; do
    branch="${ref#refs/remotes/origin/}"
    case "$branch" in
        main|develop|HEAD) continue ;;
    esac
    last_commit=$(git log -1 --format='%ct' "$ref" 2>/dev/null || echo 0)
    if [ "$last_commit" -lt "$CUTOFF" ]; then
        REMOTE_STALE+=("$branch")
    fi
done < <(git for-each-ref --format='%(refname)' refs/remotes/origin)

if [ ${#REMOTE_STALE[@]} -eq 0 ]; then
    echo "  (none)"
else
    printf '  %s\n' "${REMOTE_STALE[@]}"
fi

# --- 3. Apply or not ---
if [ "$APPLY" -eq 0 ]; then
    echo ""
    echo "Dry run. Re-run with --apply to actually delete."
    exit 0
fi

echo ""
echo "=== Deleting... ==="

# Local
if [ -n "$LOCAL_STALE" ]; then
    echo "$LOCAL_STALE" | xargs -n1 -I{} git branch -D {}
fi

# Remote
for b in "${REMOTE_STALE[@]}"; do
    git push origin ":${b}" --no-verify
    echo "  deleted origin/${b}"
done

echo ""
echo "Done."
