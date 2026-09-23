#!/usr/bin/env python3
"""Build the Skyline Roofing Pro site from site.config.json.

    pip install jinja2
    python3 build.py

Writes public/index.html, public/robots.txt and (once business.website is set)
public/sitemap.xml, then prints a checklist of anything still missing.
"""
import json
import re
from datetime import date
from pathlib import Path

from jinja2 import Environment, FileSystemLoader, select_autoescape

ROOT = Path(__file__).parent
PUBLIC = ROOT / "public"
cfg = json.loads((ROOT / "site.config.json").read_text())

biz, loc, offers = cfg["business"], cfg["location"], cfg["offers"]
missing = []


def need(value, label, placeholder):
    """Return value, or a visible placeholder and record it as missing."""
    if value:
        return value
    missing.append(label)
    return placeholder


city = need(loc["city"], "location.city", "[City]")
state_abbr = need(loc["state_abbr"], "location.state_abbr", "[ST]")
state = loc["state"] or state_abbr
phone = need(biz["phone"], "business.phone", "[Phone]")
place = f"{city}, {state_abbr}"
tel = "tel:" + re.sub(r"[^\d+]", "", biz["phone"]) if biz["phone"] else "#estimate"
areas = cfg["service_areas"]
if not areas:
    missing.append("service_areas (nearby cities to rank in)")
if not biz["email"] and not cfg["form"]["endpoint"]:
    missing.append("business.email or form.endpoint (where estimate requests go)")
if not biz["website"]:
    missing.append("business.website (needed for canonical URL, sitemap and schema)")
if not loc["google_business_profile_url"]:
    missing.append("location.google_business_profile_url")
if not any(h["opens"] for h in cfg["hours"]):
    missing.append("hours")

has_address = bool(loc["street"] and not loc["service_area_business"])
site_url = biz["website"].rstrip("/") + "/" if biz["website"] else ""

# ---------- FAQs (also emitted as FAQPage schema) ----------
faqs = [
    {
        "q": f"How much does a new roof cost in {city}?",
        "a": "It depends on the size and pitch of the roof, how many layers come off, the condition of the decking, "
             "and the material you choose. We measure the roof and give you a written, itemized price before any work starts.",
    },
    {
        "q": "Should I repair my roof or replace it?",
        "a": "If the damage is limited to one area and the rest of the roof is in good shape, a repair usually makes sense. "
             "Widespread granule loss, curling shingles, repeated leaks or soft decking point to a replacement. We show you photos of what we find so you can decide.",
    },
    {
        "q": "How long does a roof replacement take?",
        "a": "Most homes take one to three days, depending on size, weather and whether damaged decking has to be replaced.",
    },
    {
        "q": "What should I do after a storm damages my roof?",
        "a": "Stay off the roof, photograph any damage you can see from the ground, cover valuables under active leaks, "
             f"and call a roofer for an inspection. {'We can help you document the damage for your insurance claim.' if offers['insurance_claim_help'] else 'Contact your insurance company before paying for major work.'}",
    },
]
if biz["license_number"]:
    faqs.append({
        "q": f"Are you licensed in {state}?",
        "a": f"Yes. {biz['name']} holds license #{biz['license_number']}.",
    })

# ---------- Structured data ----------
org = {
    "@context": "https://schema.org",
    "@type": "RoofingContractor",
    "name": biz["name"],
    "image": (site_url + biz["logo"]) if site_url else biz["logo"],
    "logo": (site_url + biz["logo"]) if site_url else biz["logo"],
}
if site_url:
    org["@id"] = site_url + "#business"
    org["url"] = site_url
if biz["phone"]:
    org["telephone"] = biz["phone"]
if biz["email"]:
    org["email"] = biz["email"]
if biz["founded_year"]:
    org["foundingDate"] = biz["founded_year"]
address = {"@type": "PostalAddress", "addressCountry": "US"}
if loc["city"]:
    address["addressLocality"] = loc["city"]
if loc["state_abbr"]:
    address["addressRegion"] = loc["state_abbr"]
if has_address:
    address["streetAddress"] = loc["street"]
    address["postalCode"] = loc["zip"]
org["address"] = address
if loc["latitude"] and loc["longitude"]:
    org["geo"] = {"@type": "GeoCoordinates", "latitude": loc["latitude"], "longitude": loc["longitude"]}
served = ([loc["city"]] if loc["city"] else []) + areas
if served:
    org["areaServed"] = [{"@type": "City", "name": c} for c in served]
hours = [h for h in cfg["hours"] if h["opens"] and h["closes"]]
if hours:
    day_map = {"Mo": "Monday", "Tu": "Tuesday", "We": "Wednesday", "Th": "Thursday", "Fr": "Friday", "Sa": "Saturday", "Su": "Sunday"}
    specs = []
    for h in hours:
        parts = h["days"].split("-")
        keys = list(day_map)
        days = keys[keys.index(parts[0]): keys.index(parts[-1]) + 1]
        specs.append({"@type": "OpeningHoursSpecification", "dayOfWeek": [day_map[d] for d in days],
                      "opens": h["opens"], "closes": h["closes"]})
    org["openingHoursSpecification"] = specs
same_as = [u for u in cfg["social"].values() if u] + ([loc["google_business_profile_url"]] if loc["google_business_profile_url"] else [])
if same_as:
    org["sameAs"] = same_as
org["hasOfferCatalog"] = {
    "@type": "OfferCatalog",
    "name": "Roofing services",
    "itemListElement": [{"@type": "Offer", "itemOffered": {"@type": "Service", "name": s["name"]}} for s in cfg["services"]],
}
reviews = cfg["reviews"]
if reviews["rating"] and reviews["count"]:
    org["aggregateRating"] = {"@type": "AggregateRating", "ratingValue": reviews["rating"], "reviewCount": reviews["count"]}

faq_schema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [{"@type": "Question", "name": f["q"], "acceptedAnswer": {"@type": "Answer", "text": f["a"]}} for f in faqs],
}

# ---------- Render ----------
env = Environment(loader=FileSystemLoader(ROOT / "templates"), autoescape=select_autoescape(["html"]),
                  trim_blocks=True, lstrip_blocks=True)
html = env.get_template("index.html.j2").render(
    cfg=cfg, biz=biz, loc=loc, offers=offers, city=city, state=state, state_abbr=state_abbr,
    place=place, phone=phone, tel=tel, areas=areas, has_address=has_address, site_url=site_url,
    faqs=faqs, hours=hours, year=date.today().year,
    org_json=json.dumps(org, indent=2), faq_json=json.dumps(faq_schema, indent=2),
    js_config=json.dumps({"email": biz["email"], "endpoint": cfg["form"]["endpoint"], "business": biz["short_name"]}),
)
(PUBLIC / "index.html").write_text(html)

robots = "User-agent: *\nAllow: /\n"
if site_url:
    robots += f"\nSitemap: {site_url}sitemap.xml\n"
    (PUBLIC / "sitemap.xml").write_text(
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
        f"  <url><loc>{site_url}</loc><lastmod>{date.today().isoformat()}</lastmod></url>\n"
        "</urlset>\n"
    )
(PUBLIC / "robots.txt").write_text(robots)

print(f"Built public/index.html for {biz['name']} ({place})")
if missing:
    print("\nStill missing (shown as placeholders or hidden until filled):")
    for m in missing:
        print(f"  - {m}")
