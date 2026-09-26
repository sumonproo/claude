# Auto Tech Pitstop — Homepage Build (Claude Code handoff)

## What this repo is
A redesigned homepage for **Auto Tech Pitstop**, an auto repair business with two shops (Queens / NYC and Long Island).
The design is approved as a visual reference. Your job is to turn it into a **production-ready, responsive homepage**.

- `index.html` — desktop reference (1440px wide). Open it in a browser.
- `mobile.html` — mobile reference (390px phone frame with sticky bottom bar and location bottom sheet).
- `assets/` — logo and compressed WebP photos used in the design.

The reference pages use a tiny template runtime (`mountDesign`, `<sc-for>`, `<sc-if>`, `{{holes}}`) only so they render standalone.
**Do not ship that runtime.** Rebuild with clean semantic HTML, CSS and small vanilla JS modules.

## Target stack
Default: a lightweight WordPress build (Hello Elementor theme + Elementor Pro, or a child theme with template parts),
with components the client can edit. If the repo owner asks for a different stack, follow them.
If building outside WordPress first, write plain HTML/CSS/JS organized as reusable components so it ports cleanly.

Reusable components: Header, Footer, Location card, Service card, Review card, CTA band, Booking/Quote buttons,
Gallery item, Blog card, Sticky mobile action bar, Location picker (dropdown / bottom sheet).

Client-editable content: blog posts, gallery photos/videos, featured Instagram content, testimonials, services,
location info, business hours, Shopmonkey links, CTA labels, homepage banners.

## Business data (from the current site — do not change without the owner)
| | Queens / NYC | Long Island |
|---|---|---|
| Address | 153-36 Rockaway Blvd, Jamaica, NY 11434 (derived from the old site's map link — confirm) | 76 S Long Beach Rd, Rockville Centre, NY 11570 |
| Phone | (347) 433-3740 | (347) 433-3740 (same number on current site — confirm) |
| Hours | Mon–Sat 9:30 am – 6:00 pm, Sun closed | same |
| Book (Shopmonkey) | https://app.shopmonkey.cloud/public/scheduler/6412799e7ea84c0022b0ac6c?fullPage=true | https://app.shopmonkey.cloud/public/scheduler/cf86a4ec-d561-483b-9fa8-29b956ef36f2?fullPage=true |
| Quote (Shopmonkey) | https://app.shopmonkey.cloud/public/quote-request/6412799e7ea84c0022b0ac6c?noExternalScripts=1 | https://app.shopmonkey.cloud/public/quote-request/cf86a4ec-d561-483b-9fa8-29b956ef36f2?noExternalScripts=1 |
| Location page | /nyc-queens-location | /long-island-location |

Note: the old Long Island page linked to the **Queens** Shopmonkey ID by mistake. Use the IDs above.

Other: email autotechpitstop@gmail.com · Instagram https://instagram.com/autotechpitstop · Facebook https://facebook.com/400862044028010 ·
Yelp https://yelp.com/biz/auto-tech-pitstop-queens · "Pitstop Apparel Co." link in utility bar.
Trust facts: 350+ five-star Google reviews across both locations, 10+ years, locally owned, same-day service, walk-ins welcome,
appointments preferred, overnight drop-off, roadside assistance, towing on request, all major insurance/third-party warranties,
quotes by next business day, waiting lounge with Wi-Fi and TV, fleet and personal vehicles.

Keep existing URL slugs for service pages (see links in `index.html`) to protect SEO.

## Design tokens
- Backgrounds: `#0B0E13` (dark), `#07090D` (utility bar/footer), `#F4F5F7` and `#FFFFFF` (light sections)
- Brand blue (logo): `#248ACA`
- Button fill (white text, passes contrast): `#1C75B3`
- Accent on dark (labels, icons, links): `#36A3E6`
- Links on light: `#16639A`
- Text: `#EEF1F5` on dark, `#10141B` on light; muted `#A3ADBB` (dark) / `#4A5361` (light)
- Fonts: Archivo (expanded, 700–800) for headings; IBM Plex Sans (400–600) body; IBM Plex Mono (500) small labels. Load only these weights.
- Radius: 10–12px buttons, 16–20px cards. Touch targets ≥ 44px.

## Page structure (in order)
1. Utility bar · 2. Sticky header (logo, nav, Choose Location selector, Request a Quote, Book Appointment)
3. Hero (single H1: "Premium Auto Repair in Queens & Long Island") · 4. Choose Your Location (two equal cards)
5. Trust & convenience · 6. Services grid (14 services) + "Not sure what your vehicle needs?"
7. Booking CTA band · 8. Google reviews (tabs per location) · 9. Photo & video gallery (filters + lightbox)
10. Instagram feed (4–8 posts, lazy) · 11. Local SEO copy · 12. FAQ · 13. Blog preview (3 latest) · 14. Footer
Mobile adds: sticky bottom bar (Call / Book / Quote / Directions) and a location bottom sheet.

## Location-selection behavior (core conversion logic)
- Store selected location in `sessionStorage`.
- Book / Quote / Directions CTAs use the selected location's links.
- If no location is chosen: desktop scrolls to the location cards with a prompt; mobile opens the bottom sheet, then shows
  "Continue to <action> — <location>".
- Call works without a choice (both shops share one number today). If numbers diverge, route Call through the picker too.
- Header selector reflects the current choice and can change it.

## Still to build (from the full client brief, not yet in the reference)
- **Final conversion section** before the footer: "Need Reliable Auto Repair Today?" with Choose Your Location (primary),
  Book, Quote, Call.
- **Footer additions:** per-location Book / Quote links, services links, location page links, Terms and Conditions, Privacy Policy.
- **Structured data (JSON-LD):** Organization, AutoRepair / LocalBusiness per location, Service, FAQPage, BlogPosting,
  ImageGallery, BreadcrumbList on inner pages. Review markup only from valid sources.
- **Responsive layout** for every breakpoint between 390px and 1440px. Switch the desktop header to the hamburger menu below ~1280px.
- Blog preview wired to real WordPress posts; gallery wired to an editable CMS source; Instagram via a lightweight cached feed (no iframe embeds).

## Content rules
- **Never invent** Google ratings, review counts, review text or customer names. Bracketed values like `[4.9]`, `[000]`,
  `[First name]` are placeholders to be filled from the Google Business Profile or client-approved testimonials.
- Google review links ("Read more" / "Leave a review") must be location-specific; they are `#` placeholders now.
- Grey tiles marked "SHOP PHOTO NEEDED" / "PHOTO SLOT" need the client's own photos (rim repair, powder coating, lockout, fleet, storefronts).

## Images
All photos in `assets/` are Pexels stock used as stand-ins; replace with the client's own shots before launch where possible.
Two photos show recognizable brands (Mercedes G-Class, Lamborghini badge) — fine for mockups, but avoid implying a dealer
relationship on the live site. Serve responsive `srcset` sizes, lazy-load below the fold, keep hero `fetchpriority="high"`.

## Performance & accessibility
Core Web Vitals first: WebP/AVIF, no autoplay video (poster + click to play), static map image with click-to-load Google Maps,
minimal third-party scripts, no heavy sliders or animations. Semantic landmarks, one H1, logical H2/H3, visible focus states,
real `<button>`/`<a>` elements, `aria-label` on icon-only controls, WCAG AA contrast.
