#!/usr/bin/env python3
"""
Build the rewrite map for the hardcoded Arabic strings.

For every string the extractor found, this script decides:

  * reuse an existing key, when the same Arabic text already exists as a
    value in ar.json — the English is already written, so nothing is lost;
  * or propose a new key, derived from the file the string lives in.

The proposed key is a starting point: the English text and the key name
are reviewed by hand before the rewrite runs.

Usage:
    python3 scripts/i18n-build-map.py [--inventory <file>] [--out <file>]
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

# The namespaces the project already uses. A file that belongs to one of
# them keeps its keys inside that namespace.
NAMESPACE_BY_PATH = [
    (re.compile(r"^resources/js/Pages/Help/"), "help"),
    (re.compile(r"^resources/js/Pages/System/Help/"), "system_help"),
    (re.compile(r"^resources/js/Pages/Settings/Zatca/"), "zatca"),
    (re.compile(r"^resources/js/Pages/Settings/Print/"), "print_settings"),
    (re.compile(r"^resources/js/Pages/Settings/Centers/"), "center_settings"),
    (re.compile(r"^resources/js/Pages/Settings/Company/"), "company_profile"),
    (re.compile(r"^resources/js/Pages/Settings/"), "settings"),
    (re.compile(r"^resources/js/Pages/System/Website/"), "tenant_site"),
    (re.compile(r"^resources/js/Pages/System/Profile/"), "profile"),
    (re.compile(r"^resources/js/Pages/System/Security/"), "security"),
    (re.compile(r"^resources/js/Pages/System/Integrations/"), "integrations"),
    (re.compile(r"^resources/js/Pages/System/Credits/"), "credits"),
    (re.compile(r"^resources/js/Pages/System/"), "system"),
    (re.compile(r"^resources/js/Pages/App/Profile/"), "profile"),
    (re.compile(r"^resources/js/Pages/App/Security/"), "security"),
    (re.compile(r"^resources/js/Pages/WorkOrders/"), "work_orders"),
    (re.compile(r"^resources/js/Pages/Appointments/"), "appointments"),
    (re.compile(r"^resources/js/Pages/Quotes/"), "quotes"),
    (re.compile(r"^resources/js/Pages/Invoices/"), "invoices"),
    (re.compile(r"^resources/js/Pages/Customers/"), "customers"),
    (re.compile(r"^resources/js/Pages/Vehicles/"), "vehicles"),
    (re.compile(r"^resources/js/Pages/Inventory/"), "inventory"),
    (re.compile(r"^resources/js/Pages/Purchasing/"), "purchasing"),
    (re.compile(r"^resources/js/Pages/Suppliers/"), "suppliers"),
    (re.compile(r"^resources/js/Pages/Reports/"), "reports"),
    (re.compile(r"^resources/js/Pages/Dashboard/"), "dashboard"),
    (re.compile(r"^resources/js/Pages/Auth/"), "auth"),
    (re.compile(r"^resources/js/Pages/Public/"), "landing"),
    (re.compile(r"^resources/js/Components/Print/"), "print"),
    (re.compile(r"^resources/js/Components/Settings/Company/CompanySubscriptionTab"), "subscription"),
    (re.compile(r"^resources/js/Components/Settings/Company/"), "company_profile"),
    (re.compile(r"^resources/js/Components/Settings/"), "settings"),
    (re.compile(r"^resources/js/Components/Subscription/"), "subscription"),
    (re.compile(r"^resources/js/Components/Customers/"), "customers"),
    (re.compile(r"^resources/js/Components/Vehicles/"), "vehicles"),
    (re.compile(r"^resources/js/Components/WorkOrders/"), "work_orders"),
    (re.compile(r"^resources/js/Components/Services/"), "services"),
    (re.compile(r"^resources/js/Components/HR/"), "hr"),
    (re.compile(r"^resources/js/Components/"), "common"),
    (re.compile(r"^resources/js/Layouts/"), "nav"),
    (re.compile(r"^resources/js/Composables/"), "common"),
]


def namespace_for(path: str) -> str:
    for pattern, namespace in NAMESPACE_BY_PATH:
        if pattern.match(path):
            return namespace
    return "common"


def slug(text: str) -> str:
    """A readable key fragment out of the Arabic text.

    The Arabic characters are dropped and the words are replaced by
    numbered markers, so the key stays short and stable. The English
    text that a human writes next to it is what names the key for real.
    """
    words = re.findall(r"[\w\u0600-\u06FF]+", text, re.UNICODE)
    return "_".join(f"w{i + 1}" for i, _ in enumerate(words[:4]))


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--inventory", default="/tmp/extract.json")
    parser.add_argument("--out", default="/tmp/map.json")
    parser.add_argument("--lang-dir", default=os.path.join(ROOT, "resources/js/i18n/lang"))
    args = parser.parse_args()

    if not os.path.exists(args.inventory):
        print(f"ERROR: inventory not found: {args.inventory}", file=sys.stderr)
        return 1

    with open(args.inventory, encoding="utf-8") as handle:
        inventory = json.load(handle)

    def flatten(obj, prefix=""):
        flat = {}
        for key, value in obj.items():
            path = f"{prefix}.{key}" if prefix else key
            if isinstance(value, dict):
                flat.update(flatten(value, path))
            else:
                flat[path] = value
        return flat

    value_to_key = {}
    for locale in ("ar", "en"):
        path = os.path.join(args.lang_dir, f"{locale}.json")
        if not os.path.exists(path):
            continue
        with open(path, encoding="utf-8") as handle:
            flat = flatten(json.load(handle))
        if locale == "ar":
            for key, value in flat.items():
                if isinstance(value, str):
                    value_to_key.setdefault(value.strip(), key)

    mapping = {}
    stats = {"reused": 0, "new": 0}

    for path, hits in sorted(inventory.items()):
        namespace = namespace_for(path)
        entries = {}
        used = set()

        for hit in hits:
            kind = hit["kind"]
            if kind in ("data_field", "data_value"):
                continue

            value = hit["value"].strip()
            if kind == "interpolation":
                match = re.search(r"\?\s*'([^']*)'\s*:", hit["value"])
                if not match:
                    continue
                value = match.group(1).strip()
                if not value:
                    continue

            if not value:
                continue

            if value in value_to_key:
                entries[value] = {"key": value_to_key[value], "reused": True}
                stats["reused"] += 1
                continue

            base = f"{namespace}.{slug(value)}"
            candidate = base
            counter = 2
            while candidate in used:
                candidate = f"{base}_{counter}"
                counter += 1
            used.add(candidate)

            entries[value] = {"key": candidate, "en": None}
            stats["new"] += 1

        if entries:
            mapping[path] = entries

    with open(args.out, "w", encoding="utf-8") as handle:
        json.dump(mapping, handle, ensure_ascii=False, indent=1)

    print(f"files           : {len(mapping)}")
    print(f"reused entries  : {stats['reused']}")
    print(f"new keys needed : {stats['new']}")
    print(f"map written to  : {args.out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
