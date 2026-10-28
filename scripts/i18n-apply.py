#!/usr/bin/env python3
"""
Apply the reviewed translation map: write the new keys into ar.json and
en.json, and rewrite the Vue sources to call them.

The map is a JSON file:

    {
      "resources/js/Pages/X/Index.vue": {
        "the arabic text": { "key": "namespace.key", "en": "The English text" }
      }
    }

An entry whose "en" is null is skipped — it has no English yet, so the
literal stays in the source untouched.

Usage:
    python3 scripts/i18n-apply.py --map <file.json> [--lang-dir <dir>] [--dry-run]
"""

from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def flatten(obj, prefix=""):
    flat = {}
    for key, value in obj.items():
        path = f"{prefix}.{key}" if prefix else key
        if isinstance(value, dict):
            flat.update(flatten(value, path))
        else:
            flat[path] = value
    return flat


def set_path(tree, dotted_key, value):
    parts = dotted_key.split(".")
    node = tree
    for part in parts[:-1]:
        if part not in node or not isinstance(node[part], dict):
            node[part] = {}
        node = node[part]
    node[parts[-1]] = value


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--map", required=True)
    parser.add_argument("--lang-dir", default=os.path.join(ROOT, "resources/js/i18n/lang"))
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    with open(args.map, encoding="utf-8") as handle:
        mapping = json.load(handle)

    # ---- 1) collect the keys that need to exist -------------------------
    new_keys = {}
    for path, entries in mapping.items():
        for arabic, entry in entries.items():
            if entry.get("en") is None:
                continue
            key = entry["key"]
            # The Arabic copy keeps the same named placeholders as the
            # English one: `${deletingSig.name_en}` becomes `{name_en}`.
            arabic_value = re.sub(
                r"\$\{([^}]*)\}",
                lambda m: "{" + m.group(1).strip().split(".")[-1].split("||")[-1].strip() + "}",
                arabic,
            )
            english_value = entry["en"]
            # The placeholder names must match on both sides, so the
            # English text is rewritten to use the names the code passes.
            names = re.findall(r"\{([A-Za-z0-9_]+)\}", arabic_value)
            for name in names:
                english_value = re.sub(
                    r"\{([A-Za-z0-9_]+)\}",
                    "{" + name + "}",
                    english_value,
                    count=1,
                )
            new_keys[key] = {"ar": arabic_value, "en": english_value}

    if not args.dry_run:
        for locale in ("ar", "en"):
            path = os.path.join(args.lang_dir, f"{locale}.json")
            with open(path, encoding="utf-8") as handle:
                tree = json.load(handle)
            for key, values in new_keys.items():
                set_path(tree, key, values[locale])
            with open(path, "w", encoding="utf-8") as handle:
                json.dump(tree, handle, ensure_ascii=False, indent=4)
                handle.write("\n")

    print(f"{len(new_keys)} new keys written to ar.json / en.json")

    # ---- 2) rewrite the sources ----------------------------------------
    inventory_path = os.path.join(ROOT, "scripts/.i18n-inventory.json")
    result = subprocess.run(
        [
            "node",
            os.path.join(ROOT, "scripts/i18n-extract.mjs"),
            "--json",
            *sorted(mapping.keys()),
        ],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        print(result.stderr, file=sys.stderr)
        return 1

    with open(inventory_path, "w", encoding="utf-8") as handle:
        handle.write(result.stdout)

    rewrite = subprocess.run(
        [
            "node",
            os.path.join(ROOT, "scripts/i18n-rewrite.mjs"),
            "--map",
            args.map,
        ]
        + (["--dry-run"] if args.dry_run else []),
        capture_output=True,
        text=True,
    )
    sys.stderr.write(rewrite.stderr)
    print(rewrite.stdout.strip())
    return rewrite.returncode


if __name__ == "__main__":
    raise SystemExit(main())
