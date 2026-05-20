#!/usr/bin/env python3
"""Generate bundled gettext catalogs for Conversion Agent Discovery."""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "languages"
DOMAIN = "conversion-agent-discovery"
VERSION = "0.1.10"

LOCALES = {
    "pt_BR": {"language": "pt", "team": "Português do Brasil", "plural": "nplurals=2; plural=(n > 1);"},
    "pt_PT": {"language": "pt-PT", "team": "Português", "plural": "nplurals=2; plural=(n != 1);"},
    "es_ES": {"language": "es", "team": "Español", "plural": "nplurals=2; plural=(n != 1);"},
    "fr_FR": {"language": "fr", "team": "Français", "plural": "nplurals=2; plural=(n > 1);"},
    "de_DE": {"language": "de", "team": "Deutsch", "plural": "nplurals=2; plural=(n != 1);"},
    "it_IT": {"language": "it", "team": "Italiano", "plural": "nplurals=2; plural=(n != 1);"},
    "nl_NL": {"language": "nl", "team": "Nederlands", "plural": "nplurals=2; plural=(n != 1);"},
    "ru_RU": {"language": "ru", "team": "Русский", "plural": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2);"},
    "ja": {"language": "ja", "team": "日本語", "plural": "nplurals=1; plural=0;"},
    "zh_CN": {"language": "zh-CN", "team": "简体中文", "plural": "nplurals=1; plural=0;"},
}

PROTECTED_TERMS = [
	"Conversion Agent Discovery",
	"Conversion",
    "Content-Signal",
    "Content Signals",
    "MCP Server Card",
    "Markdown Negotiation",
    "Agent Skills Discovery 0.2",
    "Agent Skills v0.2",
    "Agent Skills",
    "API Catalog",
    "WPGraphQL",
    "WebMCP",
    "WordPress REST API",
    "REST API",
    "OAuth",
    "A2A",
    "SKILL.md",
    "llms.txt",
    "robots.txt",
    "text/markdown",
    "navigator.modelContext",
    "read-only",
	"v1",
	"%s",
]

MANUAL = {
    "pt_BR": {
        "Agent discovery for WordPress": "Descoberta para agentes no WordPress",
        "Global Kill Switch": "Interruptor global de segurança",
        "Rollback and operational safety.": "Rollback e segurança operacional.",
        "by Conversion": "por Conversion",
        "yes": "sim",
        "no": "não",
    },
    "pt_PT": {
        "Agent discovery for WordPress": "Descoberta para agentes no WordPress",
        "Global Kill Switch": "Interruptor global de segurança",
        "Rollback and operational safety.": "Rollback e segurança operacional.",
        "by Conversion": "por Conversion",
        "yes": "sim",
        "no": "não",
    },
}


def run(command: list[str]) -> None:
    subprocess.run(command, cwd=ROOT, check=True)


def make_pot() -> Path:
    LANG_DIR.mkdir(exist_ok=True)
    pot = LANG_DIR / f"{DOMAIN}.pot"
    run(
        [
            "xgettext",
            "--from-code=UTF-8",
            "--language=PHP",
            "--keyword=__",
            "--keyword=_e",
            "--keyword=esc_html__",
            "--keyword=esc_html_e",
            "--keyword=esc_attr__",
            "--keyword=esc_attr_e",
            "--keyword=_x:1,2c",
            "--keyword=esc_html_x:1,2c",
            "--keyword=esc_attr_x:1,2c",
            "--keyword=_n:1,2",
            "--keyword=_nx:1,2,4c",
            "--add-comments=translators:",
            "--package-name=Conversion Agent Discovery",
            f"--package-version={VERSION}",
            f"--default-domain={DOMAIN}",
            f"--output={pot}",
            "conversion-agent-discovery.php",
            "admin/class-conversion-agent-discovery-admin.php",
            "includes/class-conversion-agent-discovery-markdown.php",
            "includes/class-conversion-agent-discovery-rest.php",
            "includes/class-conversion-agent-discovery-routes.php",
            "includes/class-conversion-agent-discovery-settings.php",
            "includes/class-conversion-agent-discovery-webmcp.php",
            "includes/class-conversion-agent-discovery.php",
        ]
    )
    text = pot.read_text(encoding="utf-8")
    text = text.replace("# SOME DESCRIPTIVE TITLE.", "# Conversion Agent Discovery translations.")
    text = text.replace("PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE", "PO-Revision-Date: 2026-05-20 00:00-0300")
    text = text.replace("Last-Translator: FULL NAME <EMAIL@ADDRESS>", "Last-Translator: Conversion <contato@conversion.com.br>")
    text = text.replace("Language-Team: LANGUAGE <LL@li.org>", "Language-Team: Conversion <contato@conversion.com.br>")
    text = text.replace("Content-Type: text/plain; charset=CHARSET", "Content-Type: text/plain; charset=UTF-8")
    text = text.replace("#, fuzzy\nmsgid \"\"\n", "msgid \"\"\n", 1)
    pot.write_text(text, encoding="utf-8")
    return pot


def unescape_po(value: str) -> str:
    return bytes(value, "utf-8").decode("unicode_escape")


def escape_po(value: str) -> str:
    return value.replace("\\", "\\\\").replace('"', '\\"').replace("\t", "\\t")


def parse_pot(path: Path) -> list[dict[str, object]]:
    entries: list[dict[str, object]] = []
    comments: list[str] = []
    msgid_lines: list[str] | None = None
    current: str | None = None

    def finish() -> None:
        nonlocal comments, msgid_lines, current
        if msgid_lines is not None:
            entries.append({"comments": comments, "msgid": "".join(msgid_lines)})
        comments = []
        msgid_lines = None
        current = None

    for line in path.read_text(encoding="utf-8").splitlines():
        if line.startswith("#"):
            comments.append(line)
            continue
        if line.startswith("msgid "):
            finish()
            msgid_lines = [unescape_po(line[7:-1])]
            current = "msgid"
            continue
        if line.startswith("msgstr "):
            current = "msgstr"
            continue
        if line.startswith('"') and line.endswith('"') and current == "msgid" and msgid_lines is not None:
            msgid_lines.append(unescape_po(line[1:-1]))
            continue
        if not line.strip():
            finish()

    finish()
    return entries


def protect(text: str) -> tuple[str, dict[str, str]]:
    replacements: dict[str, str] = {}
    protected = text
    for index, term in enumerate(sorted(PROTECTED_TERMS, key=len, reverse=True)):
        token = f"ZXQTERM{index}ZXQ"
        if term in protected:
            protected = protected.replace(term, token)
            replacements[token] = term

    def protect_code(match: re.Match[str]) -> str:
        token = f"ZXQCODE{len(replacements)}ZXQ"
        replacements[token] = match.group(0)
        return token

    protected = re.sub(r"`([^`]+)`", protect_code, protected)
    return protected, replacements


def restore(text: str, replacements: dict[str, str]) -> str:
    changed = True
    while changed:
        changed = False
        for token, term in replacements.items():
            next_text = text.replace(token, term).replace(token.lower(), term)
            changed = changed or next_text != text
            text = next_text
    text = text.replace(" %s", " %s").replace("% S", "%s").replace("% s", "%s")
    return text


def translate_google(text: str, target: str) -> str:
    if not text:
        return ""
    protected, replacements = protect(text)
    params = urllib.parse.urlencode(
        {
            "client": "gtx",
            "sl": "en",
            "tl": target,
            "dt": "t",
            "q": protected,
        }
    )
    url = f"https://translate.googleapis.com/translate_a/single?{params}"
    with urllib.request.urlopen(url, timeout=20) as response:
        payload = json.loads(response.read().decode("utf-8"))
    translated = "".join(part[0] for part in payload[0] if part and part[0])
    return restore(translated, replacements)


def translate_batch(texts: list[str], locale: str, target: str, cache: dict[str, str]) -> None:
    missing = [text for text in texts if text and text not in MANUAL.get(locale, {}) and f"{locale}\0{text}" not in cache]
    if not missing:
        return

    separator = "ZXQSEPZXQ"
    chunk: list[str] = []
    chunk_size = 0

    def flush() -> None:
        nonlocal chunk, chunk_size
        if not chunk:
            return
        protected_items = []
        replacement_sets = []
        for item in chunk:
            protected, replacements = protect(item)
            protected_items.append(protected)
            replacement_sets.append(replacements)

        query = f"\n{separator}\n".join(protected_items)
        params = urllib.parse.urlencode(
            {
                "client": "gtx",
                "sl": "en",
                "tl": target,
                "dt": "t",
                "q": query,
            }
        )
        url = f"https://translate.googleapis.com/translate_a/single?{params}"

        for attempt in range(3):
            try:
                with urllib.request.urlopen(url, timeout=30) as response:
                    payload = json.loads(response.read().decode("utf-8"))
                translated = "".join(part[0] for part in payload[0] if part and part[0])
                translated_items = [part.strip() for part in translated.split(separator)]
                if len(translated_items) != len(chunk):
                    raise RuntimeError(f"Expected {len(chunk)} translations, got {len(translated_items)}")
                for source, output, replacements in zip(chunk, translated_items, replacement_sets):
                    cache[f"{locale}\0{source}"] = restore(output, replacements)
                break
            except Exception:
                if attempt == 2:
                    raise
                time.sleep(1 + attempt)

        chunk = []
        chunk_size = 0

    for text in missing:
        projected = chunk_size + len(text)
        if chunk and projected > 2800:
            flush()
        chunk.append(text)
        chunk_size = projected
    flush()


def translate(text: str, locale: str, cache: dict[str, str]) -> str:
    if text == "":
        return ""
    if text in MANUAL.get(locale, {}):
        return MANUAL[locale][text]
    return cache[f"{locale}\0{text}"]


def write_po(locale: str, meta: dict[str, str], entries: list[dict[str, object]], cache: dict[str, str]) -> Path:
    po = LANG_DIR / f"{DOMAIN}-{locale}.po"
    translate_batch([str(entry["msgid"]) for entry in entries], locale, meta["language"], cache)

    lines = [
        "# Conversion Agent Discovery translations.",
        "# Copyright (C) 2026 Conversion",
        "# This file is distributed under the same license as the Conversion Agent Discovery package.",
        "#",
        'msgid ""',
        'msgstr ""',
        f'"Project-Id-Version: Conversion Agent Discovery {VERSION}\\n"',
        '"Report-Msgid-Bugs-To: https://github.com/agencia-conversion/conversion-agent-discovery/issues\\n"',
        '"POT-Creation-Date: 2026-05-20 00:00-0300\\n"',
        '"PO-Revision-Date: 2026-05-20 00:00-0300\\n"',
        '"Last-Translator: Conversion <contato@conversion.com.br>\\n"',
        f'"Language-Team: {meta["team"]} <contato@conversion.com.br>\\n"',
        f'"Language: {locale}\\n"',
        '"MIME-Version: 1.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"',
        '"Content-Transfer-Encoding: 8bit\\n"',
        f'"Plural-Forms: {meta["plural"]}\\n"',
        '"X-Generator: Conversion Agent Discovery i18n builder\\n"',
        "",
    ]

    for entry in entries:
        msgid = str(entry["msgid"])
        if msgid == "":
            continue
        for comment in entry["comments"]:
            lines.append(str(comment))
        lines.append(f'msgid "{escape_po(msgid)}"')
        lines.append(f'msgstr "{escape_po(translate(msgid, locale, cache))}"')
        lines.append("")

    po.write_text("\n".join(lines), encoding="utf-8")
    return po


def make_mo(po: Path) -> None:
    mo = po.with_suffix(".mo")
    run(["msgfmt", "--check", "--output-file", str(mo), str(po)])


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--use-cache", default=str(LANG_DIR / ".translation-cache.json"))
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    pot = make_pot()
    entries = parse_pot(pot)
    cache_path = Path(args.use_cache)
    cache = json.loads(cache_path.read_text(encoding="utf-8")) if cache_path.exists() else {}
    for locale, meta in LOCALES.items():
        po = write_po(locale, meta, entries, cache)
        make_mo(po)
        print(f"Generated {po.name} and {po.with_suffix('.mo').name}")
        cache_path.write_text(json.dumps(cache, ensure_ascii=False, indent=2, sort_keys=True), encoding="utf-8")
    cache_path.unlink(missing_ok=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
    translate_batch([str(entry["msgid"]) for entry in entries], locale, meta["language"], cache)
