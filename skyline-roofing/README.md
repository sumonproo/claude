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
5. **Photos and video:** see *Media* below.
6. **Search setup:** set `business.website`, then submit `sitemap.xml` in Google Search Console. Add `google_business_profile_url`, and a Maps embed URL if you want the map.
7. **Reviews:** only real ones, with permission. `rating` and `count` should match Google.

## Media

Each file switches on its part of the page when it exists; `build.py` lists anything still missing. Use `tools/prep-media.sh` (needs ffmpeg) to size and compress them.

| File (in `public/assets/`) | Where it shows | Suggested stock search |
|---|---|---|
| `video/hero.mp4` (+ `hero.webm`) | Hero background, muted loop | "drone roof", "aerial roofing", "roofers aerial" |
| `img/hero.jpg` | Video poster, and the hero image if there is no video | made by the script from the video |
| `img/replacement.jpg` | Roof replacement card | "roofers installing shingles" |
| `img/repair.jpg` | Roof repair card | "roof repair shingles" |
| `img/storm-damage.jpg` | Storm and hail damage card | "hail damage roof" |
| `img/inspection.jpg` | Roof inspections card | "roof inspection" |
| `img/metal.jpg` | Metal roofing card | "standing seam metal roof" |
| `img/gutters.jpg` | Gutters and ventilation card | "roof gutter house" |
| `img/storm.jpg` (portrait) | Storm damage section | "storm damaged roof" |
| `img/og.jpg` (1200x630) | Link previews on social media | any strong roof photo |
| `img/logo.png` | Header, footer, browser tab | the company logo |

```bash
tools/prep-media.sh video ~/Downloads/drone-roof.mp4 4 12   # 12 seconds starting at 0:04
tools/prep-media.sh image ~/Downloads/shingles.jpg replacement
python3 build.py
```

- **Where to get them:** Pexels and Pixabay are free for commercial use with no attribution required, but check each item's license page. Avoid clips with visible brand names, faces or house numbers.
- **Stock is for illustration only.** Don't caption stock photos as Skyline's own jobs. Swap in real job photos as they come; they build more trust and help local search.
- **How the video behaves:** it plays muted and loops, with a Pause button. It doesn't play for visitors who have reduced motion or data saving turned on; they see the still poster frame instead. Keep `hero.mp4` under about 6 MB.

## Cloudflare

`wrangler.jsonc` deploys `public/` as a static site. With Workers Builds, set the root directory to `skyline-roofing`, leave the build command empty and keep `npx wrangler deploy`. Run `python3 build.py` and commit before pushing, because Cloudflare serves the committed `public/` folder.

## Next pages (for local rankings)

- One page per service (`/roof-replacement/`, `/roof-repair/`, `/storm-damage/`).
- One page per city in `service_areas`, with genuinely local content (neighborhoods, common roof types, recent jobs there), not copies with the city name swapped.
