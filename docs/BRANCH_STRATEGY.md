# Branch Strategy

> **Audience:** every developer pushing to `carag-v2`.
> **Goal:** make the trunk shippable at any moment and avoid the
> 14+-feature-branch sprawl we had through 2025-2026.

## TL;DR

- `main` is **always shippable**. No direct pushes — only PRs.
- `develop` is the integration branch. Feature PRs target `develop`,
  not `main`. Once `develop` is green, fast-forward `main`.
- Feature branches are short-lived: open PR the same day you push.
  Branches older than 14 days with no PR are deleted.
- One branch = one feature or one bugfix. No "mega-refactor" branches.

## Why not trunk-based?

The codebase is monolithic (465 routes, 442 PHP files, 293 Vue files)
with multi-tenant + 2FA + ZATCA integration. A pure trunk model
breaks when two teams touch the same controller at the same time.
`develop` gives us a one-week integration window so the conflict
shows up at PR time, not at 3 AM during a deploy.

## The rules

### 1. Branch naming

```
feature/<short-kebab-name>     # new functionality
fix/<issue-number-or-bug-name>  # bug fix
chore/<what-cleanup>            # refactor, dependency bump, etc
release/<semver>                # cut only from main
hotfix/<critical-bug>           # cut from main, fast-merge
```

Forbidden names: `master`, `dev`, `wip`, `test`, your name, the
date, anything with spaces. A branch named `phase-2-goal-2` is
forbidden because it tells future archaeologists nothing.

### 2. Lifetime

- A feature branch lives at most **14 days** from first push.
- If it's not ready, rebase onto current `develop` and re-open the
  PR. A branch that old is a smell — break the work down.
- Merged branches are deleted automatically by the GitHub Action
  (see `.github/workflows/cleanup-stale-branches.yml`).

### 3. PR rules

- PR title starts with a verb: `Add`, `Fix`, `Refactor`, `Drop`.
  No `WIP:` prefixes — use a draft PR instead.
- PR description answers:
  - **What** changed (one sentence per file is fine).
  - **Why** (link an issue, a Slack thread, or a sentence).
  - **How to test** (curl example, Playwright snippet, or "click
    around the modal — drag a signature, see the order change").
- All CI checks must be green before review.
- At least one approving review. Self-merge is forbidden except for
  `hotfix/*` branches where the author is the on-call.

### 4. `main` is sacred

- Force-push to `main` is denied at the GitHub level.
- Direct pushes to `main` are denied — only the `Merge` button.
- Every PR that lands on `main` triggers a deploy to staging.

### 5. Release tags

- Tags follow SemVer: `vMAJOR.MINOR.PATCH`.
- `PATCH` = bugfix, no schema change.
- `MINOR` = new feature, no breaking change.
- `MAJOR` = breaking change OR schema migration OR anything that
  requires the customer to click "Update".

## What about the existing branches?

Run `git branch --list 'feature/*' | wc -l`. If the number is over
~10, it's time for a cleanup. The script in
`scripts/cleanup-branches.sh` deletes local + remote branches
whose upstream has not seen a commit in 60 days, with a dry-run
mode that prints the list first.

## When this fails

The smell is "I have 14 local branches and I don't know which one
is safe to delete". The cure is ruthless weekly cleanup + the
14-day lifetime rule. If a feature really takes 30 days, split it
into sub-features that each merge in their own PR.
