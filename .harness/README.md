# Carag V2 - Agent Harness

## Overview

This directory contains the agent configuration for the Carag V2 project.

## Full-Stack Team (2026-06-06 rollout)

التشكيلة المتوازنة — 4 متخصصين فول-ستاك مربوطين بسياق Carag V2 (Laravel 12 + Vue 3 + Inertia + MySQL multi-tenant + ZATCA).

| Agent | Role | Description | Owns |
|-------|------|-------------|-------|
| `backend-dev` | Laravel Developer | Controllers, Services, Actions, Form Requests, Migrations, multi-tenant, ZATCA | `app/Http/Controllers/**`, `app/Models/**`, `app/Services/**`, `app/Actions/**`, `database/**` |
| `frontend-dev` | Vue/Inertia Developer | Pages, Components, Layouts, RTL, Inertia Link/useForm | `resources/js/**`, `routes/web.php` |
| `code-reviewer-carag` | Code Reviewer | Pattern review, multi-tenant audit, security, performance — يقرأ diff، يكتب verdict | `.harness/reviews/**` (لا production code) |
| `tester-carag` | PHPUnit Tester | Feature/Unit/Integration tests مع tenant isolation و auth coverage و factories | `tests/**`, `database/factories/**` |

### Built-in (global) — يستخدم للـ fallback

| Agent | Role | Description |
|-------|------|-------------|
| `coder` | Generalist | Hands-on software engineer — fallback لما لا يوجد specialist |
| `verifier` | Adversarial Verifier | يفحص المخرج النهائي بشكل عدواني — يكمل `code-reviewer` |
| `general` | Generalist | باحث ومحلل — استكشاف، توثيق، دعم |
| `mavis` | Orchestrator | أنا (Mavis) — أنسّق بين الفريق |

### كيف يتفاعلون

```
mavis (orchestrator)
  ├─→ backend-dev ──→ tester-carag ──→ code-reviewer-carag ──→ verifier (final)
  ├─→ frontend-dev ─→ tester-carag ──→ code-reviewer-carag ──→ verifier (final)
  └─→ code-reviewer-carag (مستقل — لأي PR/diff)
```

**Workflow المعتاد:**
1. `mavis` يستلم المهمة ويوزعها
2. `backend-dev` و/أو `frontend-dev` يكتبون الكود
3. `tester-carag` يكتب الـ tests ويشغّلها
4. `code-reviewer-carag` يراجع الكود ويعطي verdict
5. `verifier` يفحص المخرج النهائي
6. `mavis` يقرّر: merge أو retry

## Skills (لكل agent)

| Agent | Skill | Path |
|-------|-------|------|
| `backend-dev` | `laravel-backend.md` | `.harness/reins/backend-dev/skills/` |
| `frontend-dev` | `vue-inertia-frontend.md` | `.harness/reins/frontend-dev/skills/` |
| `code-reviewer-carag` | `code-review-checklist.md` | `.harness/reins/code-reviewer/skills/` |
| `tester-carag` | `phpunit-testing.md` | `.harness/reins/tester/skills/` |

> **ملاحظة:** الـ reins (في `.harness/reins/`) هي **documentation + skill source** — الـ agents الفعليين مسجّلين كـ global agents في `~/.mavis/agents/<name>/agent.md` لأن الـ daemon يدعم global agents مباشرة. الـ reins يبقيان كمرجع بشري ومن أجل الـ portability.

## Project Context

- **Location:** `/Users/ahmad/Herd/carag-v2`
- **Type:** Laravel ERP for auto repair shops
- **Stack:** Laravel 12 + Vue 3 + Inertia + MySQL
- **Multi-tenant:** كل query لازم يحوي `tenant_id` و `center_id`
- **2FA:** المصادقة الثنائية إلزامية
- **ZATCA:** تكامل مع نظام الفوترة السعودي
- **RTL:** دعم العربية (RTL) أساسي

## Documentation

- `AGENTS.md` — الوصف الكامل للمشروع والأنماط
- `REINS/<agent>/AGENT.md` — وصف تفصيلي لكل agent في التشكيلة
- `REINS/<agent>/skills/<name>.md` — القواعد والـ patterns

## Usage

التشغيلة الكاملة موجودة في `~/.mavis/agents/` كـ global agents. عند تخطيط مهمة:

```bash
# مثال: خطة لإضافة feature
mavis team plan run plan.yaml
# حيث plan.yaml يحدد:
#   - assigned_to: backend-dev  (لـ backend)
#   - assigned_to: frontend-dev (لـ frontend)
#   - verified_by: code-reviewer-carag
#   - verified_by: tester-carag
```

---

*Last updated: 2026-06-06 — full-stack team rollout (4 specialists)*
