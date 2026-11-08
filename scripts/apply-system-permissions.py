#!/usr/bin/env python3
"""Attach the system-panel ability check to the /system routes.

The mapping below is the single place that says which ability a route
needs. It is re-runnable: a route that already carries a
`system.permission:` middleware is left alone.

    python3 scripts/apply-system-permissions.py

Every rewrite is scoped to the /system route group, so a route name
that also exists elsewhere (`index`, `home`, `show`, …) can never be
touched outside the panel.
"""

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
ROUTES = ROOT / "routes" / "web.php"

# ── The mapping ─────────────────────────────────────────────────────────────
# Write routes: route name -> ability.
WRITE = {
    # Tenants.
    "system.tenants.suspend": "system.tenants.manage",
    "system.tenants.activate": "system.tenants.manage",
    "system.tenants.extend-trial": "system.tenants.manage",
    "system.tenants.dunning-opt-out": "system.tenants.manage",
    "system.tenants.lift-auto-suspend": "system.tenants.manage",
    "system.tenants.dunning.update": "system.tenants.manage",
    "system.tenants.dunning.clear": "system.tenants.manage",
    "system.tenants.security.update": "system.tenants.manage",
    "system.tenants.backups.store": "system.tenants.view",
    "system.tenants.destroy": "system.tenants.delete",
    "system.impersonate.start": "system.tenants.impersonate",
    # Billing.
    "system.subscriptions.store": "system.subscriptions.manage",
    "system.subscriptions.cancel": "system.subscriptions.manage",
    "system.subscriptions.activate": "system.subscriptions.manage",
    "system.subscriptions.extend": "system.subscriptions.manage",
    "system.invoices.cancel": "system.invoices.manage",
    "system.invoices.mark-paid": "system.invoices.manage",
    "system.invoices.send": "system.invoices.manage",
    "system.invoices.regenerate-pdf": "system.invoices.manage",
    "system.payment.retry": "system.payment.retry",
    "system.plans.store": "system.plans.manage",
    "system.plans.update": "system.plans.manage",
    "system.plans.destroy": "system.plans.manage",
    "system.promo-codes.store": "system.promocodes.manage",
    "system.promo-codes.update": "system.promocodes.manage",
    "system.promo-codes.destroy": "system.promocodes.manage",
    # Settings.
    "system.settings.general.update": "system.settings.manage",
    "system.settings.company.update": "system.settings.manage",
    "system.settings.company.upload-logo": "system.settings.manage",
    "system.settings.company.remove-logo": "system.settings.manage",
    "system.settings.company.upload-stamp": "system.settings.manage",
    "system.settings.company.remove-stamp": "system.settings.manage",
    "system.settings.help.update": "system.settings.manage",
    "system.settings.help.reset": "system.settings.manage",
    "system.settings.help.fetch_video_meta": "system.settings.manage",
    "system.settings.dunning.update": "system.settings.manage",
    "system.settings.website.update": "system.settings.manage",
    "system.settings.website.upload-image": "system.settings.manage",
    "system.settings.payment.update": "system.payment.settings",
    "system.integrations.store": "system.integrations.manage",
    "system.integrations.update": "system.integrations.manage",
    "system.integrations.destroy": "system.integrations.manage",
    "system.integrations.test": "system.integrations.manage",
    "system.announcements.store": "system.announcements.manage",
    "system.announcements.publish": "system.announcements.manage",
    "system.announcements.unpublish": "system.announcements.manage",
    "system.announcements.destroy": "system.announcements.manage",
    "system.communication.templates.update": "system.templates.manage",
    # Support.
    "system.feedbacks.reply": "system.feedbacks.manage",
    "system.feedbacks.update-status": "system.feedbacks.manage",
    "system.feedbacks.destroy": "system.feedbacks.manage",
    "system.contact_messages.read": "system.contact_messages.manage",
    "system.contact_messages.destroy": "system.contact_messages.manage",
    # Credits.
    "system.sms.add-credits": "system.credits.manage",
    "system.sms.packages.store": "system.credits.manage",
    "system.sms.packages.update": "system.credits.manage",
    "system.sms.packages.destroy": "system.credits.manage",
    "system.whatsapp.add-credits": "system.credits.manage",
    "system.whatsapp.packages.store": "system.credits.manage",
    "system.whatsapp.packages.update": "system.credits.manage",
    "system.whatsapp.packages.destroy": "system.credits.manage",
    # Panel.
    "system.notifications.read": "system.notifications.manage",
    "system.notifications.read-all": "system.notifications.manage",
    "system.developer.audit": "system.developer.access",
    "system.developer.ai-advice": "system.developer.access",
    "system.admin-users.store": "system.admin_users.manage",
    "system.admin-users.update": "system.admin_users.manage",
    "system.admin-users.destroy": "system.admin_users.manage",
    "system.backups.destroy": "system.activity_log.view",
    # Self.
    "system.profile.update": "system.profile.manage",
    "system.profile.updatePassword": "system.profile.manage",
    "system.profile.password": "system.profile.manage",
    "system.2fa.enable": "system.security.2fa.manage",
    "system.2fa.disable": "system.security.2fa.manage",
    "system.2fa.regenerate": "system.security.2fa.manage",
    "system.2fa.send-code": "system.security.2fa.manage",
}

# Read routes: route name -> ability.
READ = {
    "system.dashboard": "system.tenants.view",
    "system.dashboard.index": "system.tenants.view",
    "system.profile.index": "system.profile.manage",
    "system.2fa.setup": "system.security.2fa.manage",
    "system.tenants.index": "system.tenants.view",
    "system.tenants.show": "system.tenants.view",
    "system.tenants.dunning": "system.tenants.view",
    "system.admin-users.index": "system.admin_users.view",
    "system.admin-users.create": "system.admin_users.view",
    "system.admin-users.edit": "system.admin_users.view",
    "system.activity-log": "system.activity_log.view",
    "system.analytics.kpis": "system.invoices.view",
    "system.analytics.top-plans": "system.plans.manage",
    "system.analytics.payment-mix": "system.invoices.view",
    "system.analytics.cohort-retention": "system.subscriptions.view",
    "system.analytics.tenant-health-trend": "system.tenants.view",
    "system.analytics.reports": "system.invoices.view",
    "system.reports.receipts": "system.invoices.view",
    "system.reports.tenants": "system.tenants.view",
    "system.reports.aging": "system.invoices.view",
    "system.subscriptions.index": "system.subscriptions.view",
    "system.subscriptions.show": "system.subscriptions.view",
    "system.subscriptions.create": "system.subscriptions.manage",
    "system.payment.checkout": "system.subscriptions.view",
    "system.payment.success": "system.subscriptions.view",
    "system.payment.failed": "system.subscriptions.view",
    "system.payment.callback": "system.payment.retry",
    "system.bank-transfer-receipts.index": "system.invoices.view",
    "system.bank-transfer-receipts.show": "system.invoices.view",
    "system.bank-transfer-receipts.download": "system.invoices.view",
    "system.invoices.index": "system.invoices.view",
    "system.invoices.show": "system.invoices.view",
    "system.invoices.download": "system.invoices.view",
    "system.plans.index": "system.plans.manage",
    "system.promo-codes.index": "system.promocodes.manage",
    "system.settings.payment": "system.payment.settings",
    "system.settings.general": "system.settings.manage",
    "system.settings.company": "system.settings.manage",
    "system.settings.website": "system.settings.manage",
    "system.settings.dunning": "system.settings.manage",
    "system.settings.help": "system.settings.manage",
    "system.help": "system.settings.manage",
    "system.billing.dunning-log": "system.settings.manage",
    "system.integrations.index": "system.integrations.manage",
    "system.integrations.show": "system.integrations.manage",
    "system.integrations.balance": "system.integrations.manage",
    "system.announcements.index": "system.announcements.manage",
    "system.announcements.create": "system.announcements.manage",
    "system.announcements.show": "system.announcements.manage",
    "system.communication.templates.index": "system.templates.manage",
    "system.communication.templates.edit": "system.templates.manage",
    "system.feedbacks.index": "system.feedbacks.manage",
    "system.feedback.index": "system.feedbacks.manage",
    "system.feedback.redirect": "system.feedbacks.manage",
    "system.feedback.shortcut": "system.feedbacks.manage",
    "system.settings.feedbacks": "system.feedbacks.manage",
    "system.contact_messages.index": "system.contact_messages.manage",
    "system.settings.contact-messages": "system.contact_messages.manage",
    "system.sms.balances": "system.credits.manage",
    "system.sms.packages": "system.credits.manage",
    "system.sms.purchases": "system.credits.manage",
    "system.sms.usage": "system.credits.manage",
    "system.whatsapp.balances": "system.credits.manage",
    "system.whatsapp.packages": "system.credits.manage",
    "system.whatsapp.purchases": "system.credits.manage",
    "system.whatsapp.usage": "system.credits.manage",
    "system.developer.index": "system.developer.access",
    "system.developer.graph": "system.developer.access",
    "system.backups.index": "system.activity_log.view",
    "system.backups.download": "system.activity_log.view",
    "system.notifications.index": "system.notifications.manage",
    "system.notifications.recent": "system.notifications.manage",
    "system.notifications.unread-count": "system.notifications.manage",
}

# Literal paths inside a prefix group: path -> ability. The group's own
# name prefix is applied by Laravel, so the ability is attached to the
# line itself.
GROUP_PATHS = {
    # The bank-transfer-receipts group.
    "'/'": "system.invoices.view",
    "'/{receipt}'": "system.invoices.view",
    "'/{receipt}/download'": "system.invoices.view",
    "'/{receipt}/approve'": "system.invoices.manage",
    "'/{receipt}/reject'": "system.invoices.manage",
    # The notifications group.
    "'/recent'": "system.notifications.manage",
    "'/unread-count'": "system.notifications.manage",
    "'/{notification}/read'": "system.notifications.manage",
    "'/read-all'": "system.notifications.manage",
    # The feedback group.
    "'/{feedback}/reply'": "system.feedbacks.manage",
    "'/{feedback}/status'": "system.feedbacks.manage",
    "'/{feedback}'": "system.feedbacks.manage",
}


def split_system_group(src: str):
    """Return (head, body, tail) for the /system route group."""
    start = src.index("Route::prefix('system')")
    brace = src.index("{", start)
    depth = 0
    for i in range(brace, len(src)):
        if src[i] == "{":
            depth += 1
        elif src[i] == "}":
            depth -= 1
            if depth == 0:
                return src[:start], src[start:i + 1], src[i + 1:]
    raise SystemExit("Unbalanced braces around the /system group.")


def add_middleware(statement: str, ability: str) -> str:
    """Append the ability to a route statement that ends in `->name('x');`."""
    if "system.permission:" in statement:
        return statement

    existing = re.search(r"->middleware\((?P<mw>\[.*?\]|'[^']*')\)", statement)

    if not existing:
        return statement[:-1] + f"->middleware('system.permission:{ability}');"

    mw = existing.group("mw")

    if mw.startswith("["):
        # An array — split on top-level commas only, so an item like
        # 'throttle:60,1' stays in one piece.
        inner = mw[1:-1]
        items = []
        depth = 0
        current = ""
        for ch in inner:
            if ch == "[":
                depth += 1
            elif ch == "]":
                depth -= 1
            if ch == "," and depth == 0:
                items.append(current.strip())
                current = ""
            else:
                current += ch
        if current.strip():
            items.append(current.strip())
    else:
        items = [mw]

    items.append(f"'system.permission:{ability}'")
    rebuilt = "->middleware([" + ", ".join(items) + "])"

    return statement[: existing.start()] + rebuilt + statement[existing.end():]


def main() -> None:
    src = ROUTES.read_text(encoding="utf-8")
    head, body, tail = split_system_group(src)
    counter = [0]

    # 1) Routes identified by their name.
    for mapping in (WRITE, READ):
        for name, ability in mapping.items():
            pattern = re.compile(
                r"->name\('" + re.escape(name) + r"'\)(->middleware\([^)]*\))?;"
            )

            def replace(match, _ability=ability):
                new = add_middleware(match.group(0), _ability)
                if new != match.group(0):
                    counter[0] += 1
                return new

            body = pattern.sub(replace, body)

    # 2) Routes inside a prefix group, identified by their literal path.
    for literal, ability in GROUP_PATHS.items():
        pattern = re.compile(
            r"Route::(?:get|post|put|patch|delete)\(\s*"
            + re.escape(literal)
            + r"\s*,(?:(?!->name).)*?->name\('[^']+'\)(->middleware\([^)]*\))?;",
            re.S,
        )

        def replace(match, _ability=ability):
            global applied
            new = add_middleware(match.group(0), _ability)
            if new != match.group(0):
                counter[0] += 1
            return new

        body = pattern.sub(replace, body)

    # 3) The communication-templates resource.
    pattern = re.compile(
        r"(Route::resource\('communication/templates'.*?->names\('system\.communication\.templates'\))(\s*->middleware\([^)]*\))?;",
        re.S,
    )

    def replace_resource(match):
        if match.group(2):
            return match.group(0)
        counter[0] += 1
        return match.group(1) + "->middleware('system.permission:system.templates.manage');"

    body = pattern.sub(replace_resource, body)

    ROUTES.write_text(head + body + tail, encoding="utf-8")
    print(f"applied {counter[0]} ability declarations to routes/web.php")


if __name__ == "__main__":
    sys.exit(main())
