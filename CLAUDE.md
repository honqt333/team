# CLAUDE.md — Khidmah Pro (خدمة برو)

> **Supreme Constitution:**
> This repository is governed by [AGENTS.md](AGENTS.md). Read `AGENTS.md` and the relevant specialist guides under `docs/` before inspecting or modifying any file.

## Environment & Operational Notes
- **Stack:** Laravel 12 (PHP 8.2+) · Vue 3 (`<script setup>`) · Inertia.js 2 · Tailwind CSS · MySQL 8.0+ (isolated `sqlite :memory:` in `phpunit.xml`).
- **Official Name:** **خدمة برو / Khidmah Pro** (never introduce `Carag` in code, UI, or docs).
- **Specialist References:**
  - Architecture & Single Source of Truth: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
  - Components Registry & Deprecated List: [docs/COMPONENTS.md](docs/COMPONENTS.md)
  - Design Tokens & Visual System: [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md)
  - Localization Rules: [docs/I18N_RULES.md](docs/I18N_RULES.md)
  - Security & Tenancy: [docs/SECURITY.md](docs/SECURITY.md)
  - Quality Gates & Testing: [docs/TESTING.md](docs/TESTING.md)

## Quick Verification Commands
```bash
php artisan test <TargetTest>
composer lint
composer stan
npm run lint
npm run type-check
npm run test
npm run build
php artisan optimize:clear
```
