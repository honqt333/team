#!/usr/bin/env python3
"""Build World-Class Roadmap HTML + PDF."""
import re
import html
import subprocess
import sys
from pathlib import Path

SRC = Path("/Users/ahmad/Desktop/Carag-V2-World-Class-Transformation-Roadmap.md")
OUT_HTML = Path("/Users/ahmad/Desktop/Carag-V2-World-Class-Transformation-Roadmap.html")
OUT_PDF = Path("/Users/ahmad/Desktop/Carag-V2-World-Class-Transformation-Roadmap.pdf")


def slugify(s: str) -> str:
    s = re.sub(r"[^\w\u0600-\u06FF\- ]+", "", s)
    s = re.sub(r"\s+", "-", s.strip())
    return s or "section"


def md_to_html(md_text: str) -> str:
    lines = md_text.splitlines()
    html_out = []
    in_code = False
    code_buf = []
    list_stack = []
    code_lang = ""

    def close_lists(level=None):
        if level is None:
            while list_stack:
                html_out.append("</li></ul>")
                list_stack.pop()
            return
        while len(list_stack) > level:
            html_out.append("</li></ul>")
            list_stack.pop()

    def inline(t: str) -> str:
        t = html.escape(t)
        t = re.sub(r"`([^`]+)`", r"<code>\1</code>", t)
        t = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", t)
        t = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r'<a href="\2">\1</a>', t)
        return t

    i = 0
    while i < len(lines):
        line = lines[i]
        stripped = line.strip()

        if stripped.startswith("```"):
            if not in_code:
                in_code = True
                code_lang = stripped[3:].strip()
                code_buf = []
            else:
                html_out.append(
                    f'<pre dir="ltr"><code class="lang-{html.escape(code_lang)}">'
                    + html.escape("\n".join(code_buf))
                    + "</code></pre>"
                )
                in_code = False
            i += 1
            continue
        if in_code:
            code_buf.append(line)
            i += 1
            continue

        if not stripped:
            close_lists()
            html_out.append("")
            i += 1
            continue

        m = re.match(r"^(#{1,6})\s+(.*)$", stripped)
        if m:
            close_lists()
            level = len(m.group(1))
            text = m.group(2).strip()
            slug = slugify(text)
            tag = f"h{level}"
            html_out.append(f"<{tag} id='{slug}'>{inline(text)}</{tag}>")
            i += 1
            continue

        if stripped == "---":
            close_lists()
            html_out.append('<hr class="hr"/>')
            i += 1
            continue

        if stripped.startswith("|") and i + 1 < len(lines) and re.match(
            r"^\|[\s\-|:]+\|", lines[i + 1].strip()
        ):
            close_lists()
            header_cells = [c.strip() for c in stripped.strip("|").split("|")]
            i += 2
            rows = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                row_cells = [c.strip() for c in lines[i].strip().strip("|").split("|")]
                rows.append(row_cells)
                i += 1
            tbl = ["<table dir='ltr'>", "<thead><tr>"]
            for h in header_cells:
                tbl.append(f"<th>{inline(h)}</th>")
            tbl.append("</tr></thead><tbody>")
            for r in rows:
                tbl.append("<tr>")
                for c in r:
                    tbl.append(f"<td>{inline(c)}</td>")
                tbl.append("</tr>")
            tbl.append("</tbody></table>")
            html_out.append("\n".join(tbl))
            continue

        m = re.match(r"^(\s*)[-*]\s+(.*)$", line)
        if m:
            indent = len(m.group(1)) // 2
            content = m.group(2)
            while len(list_stack) < indent + 1:
                html_out.append("<ul>")
                list_stack.append(indent + 1)
            while len(list_stack) > indent + 1:
                html_out.append("</li></ul>")
                list_stack.pop()
            if list_stack and html_out and html_out[-1].endswith("<ul>"):
                pass
            elif list_stack:
                html_out.append("</li>")
            html_out.append(f"<li>{inline(content)}</li>")
            i += 1
            continue

        m = re.match(r"^(\s*)\d+\.\s+(.*)$", line)
        if m:
            indent = len(m.group(1)) // 2
            content = m.group(2)
            while len(list_stack) < indent + 1:
                html_out.append("<ol>")
                list_stack.append(indent + 1)
            while len(list_stack) > indent + 1:
                html_out.append("</ol>")
                list_stack.pop()
            html_out.append(f"<li>{inline(content)}")
            i += 1
            continue

        close_lists()
        para = [stripped]
        i += 1
        while i < len(lines) and lines[i].strip() and not re.match(
            r"^(#{1,6}\s|[-*]\s|\d+\.\s|\||---|\s*```)", lines[i]
        ):
            para.append(lines[i].strip())
            i += 1
        text = " ".join(para)
        html_out.append(f"<p>{inline(text)}</p>")

    close_lists()
    return "\n".join(html_out)


def build_toc(html_body: str) -> str:
    headings = re.findall(
        r'<h([23])\s+id=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</h\1>', html_body
    )
    items = []
    for level, anchor, text in headings:
        clean = re.sub(r"<[^>]+>", "", text)
        items.append((int(level), anchor, clean))
    rows = []
    for lvl, anchor, text in items:
        if lvl == 2:
            rows.append(
                f'<li class="toc-h2"><a href="#{anchor}">{html.escape(text)}</a></li>'
            )
        else:
            rows.append(
                f'<li class="toc-h3"><a href="#{anchor}">{html.escape(text)}</a></li>'
            )
    return (
        '<section class="toc"><h1 id="toc">فهرس المحتويات</h1><ul>'
        + "\n".join(rows)
        + "</ul></section>"
    )


def make_html(body_html: str) -> str:
    toc = build_toc(body_html)
    body_no_h1 = re.sub(r"<h1[^>]*>.*?</h1>", "", body_html, count=1)
    cover = """
    <section class="cover">
        <div class="cover-inner">
            <div class="cover-badge">World-Class Transformation Roadmap</div>
            <h1 class="cover-title">Carag V2</h1>
            <p class="cover-sub">Towards the Most Excellent System in the World</p>
            <div class="cover-meta">
                <div><strong>Stack</strong>Laravel 12 · Vue 3 · Inertia 2</div>
                <div><strong>Date</strong>2026-07-09</div>
                <div><strong>Author</strong>Mavis · Software Architect</div>
                <div><strong>Today's Score</strong><span style="color:#fbbf24">65 → Target 96</span></div>
            </div>
            <div class="cover-pillars">
                <span>Design</span><span>AI-Native</span><span>Performance</span>
                <span>Accessibility</span><span>Security</span><span>Observability</span>
                <span>Developer XP</span><span>Quality</span><span>Multi-Tenant</span>
                <span>Polish</span>
            </div>
            <div class="cover-footer">10 Pillars · 4 Phases · 9 Months to World-Class</div>
        </div>
    </section>
    """
    css = """
    @page { size: A4; margin: 0; }
    * { box-sizing: border-box; }
    body {
        font-family: 'Helvetica Neue', Arial, 'Segoe UI', sans-serif;
        color: #1f2937;
        line-height: 1.65;
        margin: 0;
        padding: 0;
    }
    .page { padding: 50px 60px; page-break-after: always; }
    .cover {
        height: 100vh;
        background: linear-gradient(135deg, #0c0a1e 0%, #312e81 30%, #6d28d9 70%, #c026d3 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px;
        page-break-after: always;
    }
    .cover-inner { max-width: 800px; text-align: center; }
    .cover-badge {
        display: inline-block;
        background: rgba(255,255,255,0.12);
        padding: 8px 18px;
        border-radius: 24px;
        font-size: 12px;
        letter-spacing: 3px;
        margin-bottom: 30px;
        border: 1px solid rgba(255,255,255,0.25);
        text-transform: uppercase;
    }
    .cover-title {
        font-size: 110px;
        font-weight: 900;
        margin: 0 0 12px;
        background: linear-gradient(135deg, #fff 0%, #fbbf24 50%, #f472b6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -3px;
    }
    .cover-sub { font-size: 22px; opacity: 0.85; margin: 0 0 50px; font-weight: 300; }
    .cover-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        text-align: left;
        background: rgba(255,255,255,0.06);
        padding: 28px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.18);
        margin-bottom: 30px;
    }
    .cover-meta > div { padding: 6px; }
    .cover-meta strong {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #fbbf24;
        margin-bottom: 4px;
    }
    .cover-pillars {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
        margin: 30px 0;
    }
    .cover-pillars span {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.18);
        padding: 6px 14px;
        border-radius: 14px;
        font-size: 12px;
        color: #e9d5ff;
    }
    .cover-footer {
        margin-top: 50px;
        font-size: 13px;
        opacity: 0.7;
        border-top: 1px solid rgba(255,255,255,0.18);
        padding-top: 20px;
        letter-spacing: 1px;
    }

    h1, h2, h3, h4 {
        color: #312e81;
        font-weight: 700;
        margin-top: 28px;
        margin-bottom: 14px;
        line-height: 1.3;
    }
    h1 {
        font-size: 30px;
        border-bottom: 3px solid #312e81;
        padding-bottom: 12px;
        margin-top: 0;
    }
    h2 {
        font-size: 22px;
        border-right: 5px solid #8b5cf6;
        padding-right: 14px;
        margin-top: 36px;
        page-break-before: always;
        color: #5b21b6;
    }
    h3 {
        font-size: 17px;
        color: #6d28d9;
        border-right: 3px solid #c084fc;
        padding-right: 10px;
        margin-top: 24px;
    }
    h4 { font-size: 15px; color: #6d28d9; margin-top: 20px; }

    p { margin: 10px 0; }
    a { color: #7c3aed; text-decoration: none; }
    a:hover { text-decoration: underline; }
    body { direction: rtl; }
    h1, h2, h3, h4, p, li, td, th, ul, ol, blockquote, pre { direction: rtl; text-align: right; }
    h1, h2, h3 { text-align: right; }
    pre, code, .code, table, .toc { direction: ltr; text-align: left; }

    pre {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        color: #e9d5ff;
        padding: 18px;
        border-radius: 8px;
        overflow-x: auto;
        font-size: 12px;
        line-height: 1.55;
        margin: 16px 0;
        border-right: 4px solid #f472b6;
    }
    code {
        background: #faf5ff;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 13px;
        color: #6d28d9;
        font-family: 'SF Mono', Monaco, monospace;
    }
    pre code { background: transparent; padding: 0; color: inherit; font-size: 12px; }

    ul, ol { padding-right: 28px; padding-left: 0; }
    li { margin: 6px 0; }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 18px 0;
        font-size: 13px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    thead { background: linear-gradient(135deg, #312e81 0%, #7c3aed 100%); color: white; }
    th, td { padding: 10px 14px; border: 1px solid #e9d5ff; text-align: left; }
    th { font-weight: 600; letter-spacing: 0.3px; }
    tbody tr:nth-child(even) { background: #faf5ff; }
    tbody tr:hover { background: #f3e8ff; }

    hr.hr { border: none; border-top: 2px dashed #c084fc; margin: 30px 0; }

    blockquote {
        border-right: 4px solid #fbbf24;
        background: linear-gradient(135deg, #fef3c7 0%, #fff7ed 100%);
        padding: 14px 18px;
        margin: 16px 0;
        border-radius: 4px;
        font-style: italic;
    }

    .toc {
        background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 100%);
        padding: 30px 40px;
        border-radius: 12px;
        margin: 0 0 30px;
        border: 1px solid #c084fc;
        page-break-after: always;
    }
    .toc h1 {
        border-bottom: 3px solid #7c3aed;
        padding-bottom: 10px;
        margin-bottom: 20px;
        text-align: right;
        direction: rtl;
    }
    .toc ul { list-style: none; padding-right: 0; }
    .toc li { margin: 6px 0; }
    .toc-h2 { font-weight: 600; font-size: 14px; }
    .toc-h2 a { color: #5b21b6; }
    .toc-h3 { padding-right: 28px; font-size: 13px; color: #6b21a8; }
    .toc-h3 a { color: #6b21a8; }

    @media print {
        .page-break { page-break-before: always; }
    }
    """
    return f"""<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Carag V2 — World-Class Transformation Roadmap</title>
<style>{css}</style>
</head>
<body>
{cover}
<div class="page">{toc}</div>
<div class="page">{body_no_h1}</div>
</body>
</html>"""


def main():
    md_text = SRC.read_text(encoding="utf-8")
    body_html = md_to_html(md_text)
    full = make_html(body_html)
    OUT_HTML.write_text(full, encoding="utf-8")
    print(f"wrote: {OUT_HTML} ({len(full):,} chars)")
    chrome = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
    cmd = [
        chrome,
        "--headless=new",
        "--disable-gpu",
        "--no-sandbox",
        "--print-to-pdf=" + str(OUT_PDF),
        "--print-to-pdf-no-header",
        "file://" + str(OUT_HTML),
    ]
    res = subprocess.run(cmd, capture_output=True, text=True, timeout=180)
    print("stderr:", res.stderr[:300])
    if OUT_PDF.exists():
        size_mb = OUT_PDF.stat().st_size / (1024 * 1024)
        print(f"\nPDF ready: {OUT_PDF} ({size_mb:.2f} MB)")


if __name__ == "__main__":
    main()
