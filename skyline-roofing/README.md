# Skyline Roofing Pro website

Homepage for Skyline Roofing Pro LLC, built for local search (city and service-area keywords, `RoofingContractor` and `FAQPage` structured data, one consistent name/address/phone block).

## How it works

- `site.config.json`: every business fact (name, city, phone, service areas, hours, offers, reviews). **Edit this, not the HTML.**
- `templates/index.html.j2`: the page template.
- `build.py`: fills the template from the config and writes `public/index.html`, `robots.txt` and `sitemap.xml`.
- `public/`: the finished site (HTML, CSS, JS, fonts, icons, images). This is what gets deployed.

```bash
pip install jinja2
python3 build.py              # rebuild after any config change
cd public && python3 -m http.server 8000   # preview at http://localhost:8000
```

`build.py` ends with a list of anything still missing. Missing text shows on the page as `[City]`, `[Phone]` and so on; unconfirmed offers and badges stay hidden.

## Before launch

1. **Business name.** Instagram says "Skyline Roofing Pro LLC" and Facebook says "Skyline Roofing LLC". Pick one and use it exactly the same way here, on Google Business Profile, Facebook and every directory listing.
2. **Location details:** `location.city`, `state`, `state_abbr`, `business.phone`, and a few nearby cities in `service_areas`. If customers visit an office, set `service_area_business` to `false` and add the street address and ZIP.
3. **Offers:** set `free_inspection`, `insurance_claim_help`, `financing` and the others to `true` only if they are true. Add the license number, warranty and certifications if you have them. Each one shows a badge.
4. **Estimate form:** set `form.endpoint` (for example a free Formspree form) or `business.email`.
5. **Photos:** put them in `public/assets/img/`: `logo.png`, `hero.jpg` (the hero background), `replacement.jpg`, `storm.jpg` and `og.jpg` (1200x630, used when the link is shared).
6. **Search setup:** set `business.website`, then submit `sitemap.xml` in Google Search Console. Add `google_business_profile_url`, and a Maps embed URL if you want the map.
7. **Reviews:** only real ones, with permission. `rating` and `count` should match Google.

## Cloudflare

`wrangler.jsonc` deploys `public/` as a static site. With Workers Builds, set the root directory to `skyline-roofing`, leave the build command empty and keep `npx wrangler deploy`. Run `python3 build.py` and commit before pushing, because Cloudflare serves the committed `public/` folder.

## Next pages (for local rankings)

- One page per service (`/roof-replacement/`, `/roof-repair/`, `/storm-damage/`).
- One page per city in `service_areas`, with genuinely local content (neighborhoods, common roof types, recent jobs there), not copies with the city name swapped.
