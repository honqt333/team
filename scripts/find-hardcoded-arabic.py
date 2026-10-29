#!/usr/bin/env python3
"""
Find the Arabic literals that are hardcoded in the Vue/JS sources.

The literals that already sit inside a translation call are skipped —
they are done. What is left is the inventory of what still needs a key.

Usage:
    python3 scripts/find-hardcoded-arabic.py [path ...]
    python3 scripts/find-hardcoded-arabic.py --json resources/js/Pages
"""

from __future__ import annotations

import json
import os
import re
import sys

ARABIC = re.compile(r'[\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF]')

# A translation call: t('...') / $t('...') / translate('...') / te('...')
TRANSLATION_CALL = re.compile(
    r"""(?:\$|\b)(?:t|te|translate|trans)\(\s*(['"`])(?:\\.|(?!\1).)*\1""",
    re.S,
)

# Any quoted literal (single, double, backtick)
LITERAL = re.compile(
    r"""(?P<q>['"`])(?P<body>(?:\\.|(?!(?P=q)).)*)(?P=q)""",
    re.S,
)

# A template text node: text between > and < that is not inside a tag
TEXT_NODE = re.compile(r">([^<>{}]*[^\s<>{}][^<>{}]*)<")

SKIP_DIRS = {"i18n", "node_modules", "dist", "build"}


def strip_translation_calls(source: str) -> str:
    """Blank out the translation calls so their keys are not scanned."""
    return TRANSLATION_CALL.sub(lambda m: "'" + " " * (len(m.group(0)) - 2) + "'", source)


def strip_comments(source: str) -> str:
    """Remove /* */ and // comments, keeping the length roughly stable."""
    def block(m: re.Match) -> str:
        return re.sub(r"[^\n]", " ", m.group(0))

    source = re.sub(r"/\*.*?\*/", block, source, flags=re.S)
    source = re.sub(r"//[^\n]*", lambda m: " " * len(m.group(0)), source)
    return source


def scan_file(path: str) -> list[dict]:
    raw = open(path, encoding="utf-8").read()
    source = strip_comments(strip_translation_calls(raw))

    found: list[dict] = []

    for match in LITERAL.finditer(source):
        body = match.group("body")
        if not ARABIC.search(body):
            continue
        # Skip import paths and obvious non-display strings
        line = source.count("\n", 0, match.start()) + 1
        found.append(
            {
                "kind": "literal",
                "line": line,
                "quote": match.group("q"),
                "value": body,
            }
        )

    for match in TEXT_NODE.finditer(source):
        body = match.group(1)
        if not ARABIC.search(body):
            continue
        line = source.count("\n", 0, match.start()) + 1
        found.append({"kind": "text", "line": line, "value": body.strip()})

    return sorted(found, key=lambda item: item["line"])


def walk(targets: list[str]):
    for target in targets:
        if os.path.isfile(target):
            yield target
            continue
        for dirpath, dirnames, filenames in os.walk(target):
            dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
            for name in filenames:
                if name.endswith((".vue", ".js", ".ts")):
                    yield os.path.join(dirpath, name)


def main() -> int:
    args = sys.argv[1:]
    as_json = "--json" in args
    targets = [a for a in args if a != "--json"] or ["resources/js"]

    total = 0
    per_file = []
    inventory = {}

    for path in walk(targets):
        found = scan_file(path)
        if not found:
            continue
        inventory[path] = found
        total += len(found)
        per_file.append((len(found), path))

    if as_json:
        print(json.dumps(inventory, ensure_ascii=False, indent=2))
        return 0

    per_file.sort(reverse=True)
    print(f"{total} hardcoded Arabic literals in {len(per_file)} files\n")
    for count, path in per_file:
        print(f"{count:5d}  {path}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
