# Backup & Disaster Recovery Runbook — Carag V2

> **Track C — Observability** · Day 24 · owner: Platform
> Last reviewed: 2026-07-09

This document is the source of truth for **how** Carag V2 production data is
backed up, **how long** we keep it, and **exactly what an on-call engineer
must do** when they have to restore. Every "we'll figure it out in the
moment" gap has historically cost us hours (see *Lessons learned* at the
bottom).

---

## 1. What we back up

| Dataset | Source | Frequency | Retention | Storage |
|---|---|---|---|---|
| MySQL logical dump | `mysqldump --single-transaction --routines --triggers` | Daily 02:00 (Riyadh) | 30 daily + 12 monthly | `s3://carag-backups/db/` |
| MySQL binlog | Streamed via RDS into S3 | Continuous (5-min chunks) | 7 days | `s3://carag-backups/binlog/` |
| Spatie media + uploads | `app/Models/Media::*`, `app/Models/Attachment`, WorkOrder photos | Daily 03:00 | 30 daily + 12 monthly | `s3://carag-backups/storage/` |
| `.env`, encryption keys, OAuth secrets | Manual export via Vault + `aws s3 sync` from the app server | Weekly + on-change | Indefinite | `s3://carag-backups/secrets/` (KMS-encrypted) |
| ZATCA submission log | `app/Models/ZatcaInvoice` + response archive | Daily 03:30 | 7 years (regulatory) | `s3://carag-backups/zatca/` (separate bucket, object-lock) |

> Multi-tenant note: every MySQL dump is taken with `--single-transaction`
> and `--databases carag_v2` so a restore to a single tenant is still
> possible (see §4). Tenant data isolation must be honoured when sharing
> any dump for support (`tenant_id` filter at restore time).

---

## 2. Where it lives

* **Primary bucket:** `s3://carag-backups/` (us-east-1, SSE-KMS, versioning
  enabled, MFA-delete disabled because we use IAM roles not root keys).
* **Secondary region:** Cross-region replication to `eu-west-1` is **not**
  enabled by default to control cost — turn it on with
  `terraform/modules/s3-replication` for any bucket tagged
  `compliance=zatca` or `compliance=pci`.
* **Retention policy:** enforced via S3 Lifecycle rules:
  * `db/` → 30 d Standard → 365 d Standard-IA → expire
  * `storage/` → 30 d Standard → expire
  * `binlog/` → 7 d Standard → expire
  * `secrets/` → keep forever (manual pruning only)
  * `zatca/` → 7 years Object-Lock (Compliance mode, no delete).

Backup IAM policy is the principle of least privilege:
`arn:aws:iam::backup-runner` only has `s3:PutObject` on
`s3://carag-backups/*` and `s3:GetObject` from a single restore role.

---

## 3. How backups are taken

The whole pipeline runs under `app/Console/Commands/Backup/*` and is
scheduled by the cron in `deploy/roles/app/tasks/main.yml`:

```
0 2 * * *   cd /var/www/carag && php artisan backup:db --to=s3 >> /var/log/carag-backup.log 2>&1
0 3 * * *   cd /var/www/carag && php artisan backup:storage --to=s3 >> /var/log/carag-backup.log 2>&1
30 3 * * *  cd /var/www/carag && php artisan backup:zatca --to=s3 >> /var/log/carag-backup.log 2>&1
```

Each command:

1. Validates the previous run's report (`storage/app/backups/last-success.json`).
   If the previous run failed AND no human override file exists, it
   emails `oncall@carag.app` and pages PagerDuty (Track C's Sentry
   `error:backup-failed` alert feeds the same pipeline).
2. Streams the dump through `gpg --symmetric --batch --passphrase-file /etc/carag/backup.pass`
   before uploading (so a leaked S3 credential alone is not enough).
3. Uploads to `s3://carag-backups/<dataset>/<YYYY>/<MM>/<DD>/<file>`
   and writes a side-car `*.manifest.json` with size + sha256 + checksum.
4. Updates the success record so the next run can compare.

### 3.1 Verify daily

The `backup:verify` command (called by the same cron at 06:00) does a
**head-only** `aws s3 cp` of the manifest and re-validates the sha256 of
the first 1 MB of each artefact. Any mismatch pages oncall.

---

## 4. Restore runbook

> **Authorisation:** restoring production data ALWAYS needs two approvers
> from `platform-leads`. Open an internal incident first via
> `#incident-restore` and tag `@platform-oncall`. Then proceed.

### 4.1 Decision: full restore vs. point-in-time

* **Logical dump available in the last 24 h?** Use the latest dump +
  replay the binlog forward to the incident point (`mysqlbinlog
  --stop-datetime`).
* **No dump under 24 h, or catastrophic tenant corruption?** Restore
  from the most recent dump and accept the data loss window (write it into
  the post-mortem).
* **Affects one tenant only?** Restore the full dump into a temporary
  database, run `mysqldump --where="tenant_id=<X>"` to extract just that
  tenant, then re-apply on production. Do **NOT** try to surgically
  delete rows from production.

### 4.2 The 12-step restore

```bash
# 1. Open an incident and freeze writes
./scripts/ops/maintenance-on.sh "DR-restore in progress"

# 2. Pull the artefact + manifest
MANIFEST=$(aws s3 ls s3://carag-backups/db/2026/07/09/ | grep manifest | tail -1)
aws s3 cp s3://carag-backups/db/2026/07/09/${MANIFEST} /tmp/
SHA=$(jq -r .sha256 /tmp/${MANIFEST})

# 3. Verify the checksum before decrypting
EXPECTED=$(jq -r .sha256 /tmp/${MANIFEST})
[ "$SHA" = "$EXPECTED" ] || { echo "BAD CHECKSUM"; exit 1; }

# 4. Decrypt
FILE=$(jq -r .filename /tmp/${MANIFEST})
aws s3 cp s3://carag-backups/db/2026/07/09/${FILE} /tmp/
gpg --batch --passphrase-file /etc/carag/backup.pass \
    --decrypt /tmp/${FILE} > /tmp/restore.sql

# 5. Bring up a fresh restore target (NEVER overwrite the live DB on the
#    first attempt).
mysql -h restore-db.carag.internal -u root -e "
  CREATE DATABASE carag_v2_restore_$(date +%s);
  GRANT ALL ON carag_v2_restore_$(date +%s).* TO 'carag_app'@'%';"

# 6. Apply
mysql -h restore-db.carag.internal carag_v2_restore_$(date +%s) < /tmp/restore.sql

# 7. Replay binlog up to incident time (if doing PITR)
mysqlbinlog --host=binlog-restore.carag.internal \
            --stop-datetime="2026-07-09 12:34:56" \
            s3://carag-backups/binlog/* \
  | mysql -h restore-db.carag.internal carag_v2_restore_$(date +%s)

# 8. Run schema compatibility check
php artisan migrate:status --database=mysql_restore
# (App uses Laravel migrations — should report "No new migrations" if dump
#  + replay produced a fully consistent schema.)

# 9. Application-level checks
php artisan tinker --execute='dump(\App\Models\Tenant::count());'
php artisan tinker --execute='dump(\App\Models\WorkOrder::latest()->first());'

# 10. Swap the DB connection
./scripts/ops/db-swap.sh carag_v2 carag_v2_restore_$(date +%s)
php artisan config:clear && php artisan cache:clear

# 11. Smoke test
./scripts/ops/smoke.sh  # expect: 200 on /healthz, /readyz, /login

# 12. Disable maintenance
./scripts/ops/maintenance-off.sh
```

### 4.3 Storage (S3 media) restore

```bash
aws s3 sync s3://carag-backups/storage/2026/07/09/ s3://carag-media/ \
    --dryrun       # ← always dry-run first; removes --dryrun to apply
```

---

## 5. Restore drills (quarterly)

Drills are scheduled **first Friday of every quarter** (Q1: Jan, Q2: Apr,
Q3: Jul, Q4: Oct). A drill is considered passed only if the table
below is fully filled in and reviewed by the platform lead within 7
days.

| Drill date | Scenario | RTO achieved | RPO achieved | Tester | Verifier |
|---|---|---|---|---|---|
| 2026-04-03 | Single-tenant restore from `db/` |  |  |  |  |
| 2026-07-03 | Storage bucket rollback to 2026-07-02 |  |  |  |  |

**Targets:**
* RPO (data loss) ≤ 15 min (we accept the 5-min binlog chunk size).
* RTO (downtime) ≤ 60 min for a single-tenant restore, ≤ 4 h for a
  full-cluster restore.

If a drill misses target, file a follow-up ticket. Do not push to next
quarter.

---

## 6. Monitoring & alerting

* `backup:db` / `backup:storage` exit non-zero → Sentry tag
  `area=backup,severity=high` → PagerDuty `carag-platform` rotation.
* Last successful backup older than 26 h → warning Slack `#platform-alerts`.
* Last successful backup older than 50 h → critical, page oncall.
* Restore drill overdue by > 14 days → critical, page oncall.

All of the above are wired through Sentry in Track C; the runbook for
responding to one of these alerts is **this document**, starting at §4.

---

## 7. Lessons learned

* **2026-02-14 — binlog gotcha.** A failed restore because no one
  remembered that the `mysql` user on the source RDS didn't have
  `RELOAD` privileges for `mysqldump --single-transaction`. Locked dump
  for 40 min instead of <1. Action: bake the user-grant check into
  `backup:db` startup.
* **2026-05-20 — S3 lifecycle fired too early.** A `db/2026-02-15`
  artefact vanished because someone had set the rule to 14 days during a
  drill, then forgot. Action: lifecycle rules are now Terraform-managed
  and can't be edited by hand.

---

## 8. Related documents

* `docs/operations/incident-response.md` — incident comms templates
* `docs/AI_WORKFLOW/09_DEPLOYMENT_RULES.md` — env strategy
* `app/Console/Commands/Backup/*` — the actual code
