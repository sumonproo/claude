#!/usr/bin/env python3
"""Builds the Kovarik Construction site from src/.

Writes two things:
  elementor/  paste-ready code for WordPress + Elementor (global CSS, header,
              footer and one HTML snippet per page)
  preview/    the same pages as standalone HTML files, to view in a browser

Tokens used in src/:
  {{name}}, {{phone}}, ...   any key from site.config.json
  {{ico:phone}}              inline SVG icon (see ICONS)
  {{img:Kitchen remodel}}    placeholder image (swap for a real photo URL)
  {{year}}                   current year
No dependencies: python3 build.py
"""
import datetime
import json
import re
import urllib.parse
from pathlib import Path

ROOT = Path(__file__).parent
SRC = ROOT / "src"
CONFIG = json.loads((ROOT / "site.config.json").read_text())

# (slug used in WordPress, source file, page title, meta description)
PAGES = [
    ("", "home", "Remodeling & General Contractor in Monterey, CA",
     "Kovarik Construction Inc. is a licensed general contractor (CSLB #1147953) for remodeling, bathrooms, ADUs, flooring, tile and outdoor work across the Monterey Peninsula."),
    ("about", "about", "About Us",
     "Meet Kovarik Construction Inc., a licensed Monterey Peninsula general contractor focused on careful, well-built remodeling projects."),
    ("services", "services", "Remodeling & Construction Services",
     "Home remodeling, bathroom remodeling, ADUs and garage conversions, flooring and tile, and outdoor improvements across the Monterey Peninsula."),
    ("general-remodeling", "general-remodeling", "General & Whole-Home Remodeling in Monterey",
     "Whole-home and general remodeling in Monterey, Carmel, Pacific Grove, Pebble Beach and Seaside by licensed contractor Kovarik Construction Inc."),
    ("bathroom-remodeling", "bathroom-remodeling", "Bathroom Remodeling in Monterey & Carmel",
     "Bathroom remodeling and renovation on the Monterey Peninsula: showers, tubs, tile, vanities and full layout changes. Request an estimate."),
    ("adu-garage-conversions", "adu-garage-conversions", "ADUs & Garage Conversions on the Monterey Peninsula",
     "Accessory dwelling units (ADUs), junior ADUs and garage conversions in Monterey, Carmel, Pacific Grove, Pebble Beach and Seaside."),
    ("flooring-tile", "flooring-tile", "Flooring & Tile Installation in Monterey",
     "Flooring and tile installation and renovation: hardwood, LVP, tile floors, showers and backsplashes across the Monterey Peninsula."),
    ("outdoor-improvements", "outdoor-improvements", "Fences, Decks, Concrete & Hardscape in Monterey",
     "Fences, exterior carpentry, concrete, pavers and hardscape on the Monterey Peninsula by Kovarik Construction Inc."),
    ("projects", "projects", "Our Projects",
     "Completed remodeling, bathroom, ADU, flooring, tile and outdoor projects by Kovarik Construction Inc. on the Monterey Peninsula."),
    ("monterey-peninsula-service-area", "service-area", "Monterey Peninsula Service Area",
     "Kovarik Construction serves Monterey, Carmel-by-the-Sea, Pacific Grove, Pebble Beach and Seaside with remodeling and construction services."),
    ("request-an-estimate", "request-estimate", "Request an Estimate",
     "Tell us about your remodeling or construction project and upload photos. Kovarik Construction serves the Monterey Peninsula."),
    ("contact", "contact", "Contact Us",
     "Call, email or send a project request to Kovarik Construction Inc., a licensed general contractor on the Monterey Peninsula."),
    ("thank-you", "thank-you", "Thank You",
     "Thanks for contacting Kovarik Construction Inc."),
    ("privacy-policy", "privacy-policy", "Privacy Policy",
     "Privacy policy for kovarikconstruction.com."),
    ("terms", "terms", "Terms of Use",
     "Terms of use for kovarikconstruction.com."),
]

ICONS = {
    "phone": '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
    "check": '<path d="M20 6 9 17l-5-5"/>',
    "arrow": '<path d="M5 12h14M13 5l7 7-7 7"/>',
    "mail": '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    "pin": '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    "clock": '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    "shield": '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    "home": '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
    "wrench": '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
    "bath": '<path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.7 3 4 3.7 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path d="M2 12h20"/><path d="M7 19v2M17 19v2"/>',
    "adu": '<path d="M3 21V9l9-6 9 6v12"/><path d="M7 21v-8h10v8"/><path d="M7 17h10"/>',
    "tile": '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    "fence": '<path d="M4 4 2 6v15h4V6zM12 4l-2 2v15h4V6zM20 4l-2 2v15h4V6z"/><path d="M6 9h4M6 16h4M14 9h4M14 16h4"/>',
    "menu": '<path d="M3 6h18M3 12h18M3 18h18"/>',
    "close": '<path d="M18 6 6 18M6 6l12 12"/>',
    "chev": '<path d="m6 9 6 6 6-6"/>',
    "upload": '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
    "file": '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
    "ruler": '<path d="M21.3 15.3 8.7 2.7a1 1 0 0 0-1.4 0L2.7 7.3a1 1 0 0 0 0 1.4l12.6 12.6a1 1 0 0 0 1.4 0l4.6-4.6a1 1 0 0 0 0-1.4z"/><path d="m7.5 10.5 2-2M10.5 13.5l2-2M13.5 16.5l2-2"/>',
    "star": '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
    "camera": '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/>',
    "users": '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
    "chat": '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    "calendar": '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    "leaf": '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.4 5.2-6"/>',
}


def icon(name):
    return f'<svg class="kc-ico" viewBox="0 0 24 24" aria-hidden="true">{ICONS[name]}</svg>'


def placeholder(label):
    """Neutral 3:2 placeholder with a caption. Replace with a real photo URL."""
    svg = (
        '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">'
        '<rect width="1200" height="800" fill="#E9E4DE"/>'
        '<path d="M0 800 360 430l220 210 170-150 450 310z" fill="#DCD5CD"/>'
        '<circle cx="880" cy="250" r="70" fill="#DCD5CD"/>'
        '<text x="600" y="385" font-family="Arial,sans-serif" font-size="40" font-weight="700" '
        f'fill="#8A7F74" text-anchor="middle">{label}</text>'
        '<text x="600" y="440" font-family="Arial,sans-serif" font-size="26" fill="#A2978C" '
        'text-anchor="middle">Replace with project photo</text></svg>'
    )
    return "data:image/svg+xml," + urllib.parse.quote(svg, safe="=:/,")


def render(text):
    text = re.sub(r"\{\{ico:(\w+)\}\}", lambda m: icon(m.group(1)), text)
    text = re.sub(r"\{\{img:([^}]+)\}\}", lambda m: placeholder(m.group(1)), text)
    text = text.replace("{{year}}", str(datetime.date.today().year))
    for key, value in CONFIG.items():
        if isinstance(value, str):
            text = text.replace("{{" + key + "}}", value)
    left = re.findall(r"\{\{[^}]+\}\}", text)
    if left:
        raise SystemExit(f"Unknown tokens: {sorted(set(left))}")
    return text


def to_preview_links(html):
    """WordPress permalinks (/slug/) -> local preview files (slug.html)."""
    def repl(m):
        slug, anchor = m.group(1).strip("/"), m.group(2) or ""
        return f'href="{slug or "index"}.html{anchor}"'
    return re.sub(r'href="/([a-z0-9\-/]*)(#[\w-]+)?"', repl, html)


def main():
    out = ROOT / "elementor"
    (out / "pages").mkdir(parents=True, exist_ok=True)
    prev = ROOT / "preview"
    prev.mkdir(exist_ok=True)

    css = render((SRC / "global.css").read_text())
    header = render((SRC / "header.html").read_text())
    footer = render((SRC / "footer.html").read_text())
    (out / "global.css").write_text(css)
    (out / "header.html").write_text(header)
    (out / "footer.html").write_text(footer)
    (prev / "global.css").write_text(css)

    seo_rows = ["| Page | URL slug | SEO title | Meta description |", "|---|---|---|---|"]
    for i, (slug, src, title, desc) in enumerate(PAGES, 1):
        body = render((SRC / "pages" / f"{src}.html").read_text())
        (out / "pages" / f"{i:02d}-{src}.html").write_text(body)
        full_title = f"{title} | {CONFIG['name']}" if slug else f"{CONFIG['name']} | {title}"
        seo_rows.append(f"| {src} | /{slug + '/' if slug else ''} | {full_title} | {desc} |")
        page = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{full_title}</title>
<meta name="description" content="{desc}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="global.css">
<style>body{{margin:0;background:#fff}}</style>
</head>
<body>
{header}
{body}
{footer}
</body>
</html>
"""
        (prev / f"{slug or 'index'}.html").write_text(to_preview_links(page))

    (out / "SEO.md").write_text(
        "# Page titles and meta descriptions\n\n"
        "Set these per page in your SEO plugin (Rank Math or Yoast).\n\n"
        + "\n".join(seo_rows) + "\n"
    )

    todo = [k for k in CONFIG.get("_placeholders", [])]
    print(f"Built {len(PAGES)} pages into elementor/ and preview/.")
    if CONFIG.get("form_action", "#") == "#":
        todo.append("form_action (or use the Elementor Pro Form widget)")
    print("Still placeholder values in site.config.json: " + ", ".join(todo))


if __name__ == "__main__":
    main()
