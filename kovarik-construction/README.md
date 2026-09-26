# Kovarik Construction Inc. website (WordPress + Elementor)

Website for Kovarik Construction Inc. (CSLB #1147953, Monterey Peninsula), built from the project quote: 15 pages, white/dark/orange contractor design, an estimate form with photo upload, local SEO schema, and click-to-call on mobile.

## What's in here

| Folder | What it is |
|---|---|
| `elementor/` | **The code to paste into WordPress.** Ready to use. |
| `elementor/global.css` | All styles. Paste once for the whole site. |
| `elementor/header.html`, `footer.html` | Site header (menu, phone, estimate button) and footer. |
| `elementor/pages/01-home.html` … `15-terms.html` | One file per page. |
| `elementor/SEO.md` | Page titles, URL slugs and meta descriptions for each page. |
| `preview/` | The same site as plain HTML. Open `preview/index.html` in a browser to see it. |
| `src/`, `site.config.json`, `build.py` | Source files. Only needed if you want to regenerate everything (see below). |

## Put it into Elementor

Recommended theme: **Hello Elementor**, with **Elementor Pro** (for the header, footer and form).

1. **Styles (once).** Open `elementor/global.css`, copy all of it, and paste it into
   *Elementor > Site Settings > Custom CSS* (or *Appearance > Customize > Additional CSS*).
2. **Fonts.** In *Elementor > Site Settings > Global Fonts*, set Primary/Secondary to **Archivo** (headings) and Text to **Inter**. The CSS uses them by name, with system fonts as backup.
3. **Header.** *Templates > Theme Builder > Header > Add New*. Add a container, set it to **Full Width** with **0 padding**, drop in an **HTML** widget and paste `header.html`. Publish with the condition **Entire Site**. For a sticky header: container > Advanced > Motion Effects > Sticky: Top.
4. **Footer.** Same steps with `footer.html` under *Theme Builder > Footer*.
5. **Pages.** For each file in `elementor/pages/`:
   - *Pages > Add New*, set the title, and set the URL slug from the table below.
   - Page Attributes > Template: **Elementor Full Width**. *Edit with Elementor*.
   - Add a container (**Full Width**, content width Full, **0 padding / 0 gap**), add an **HTML** widget, paste the whole file, and publish.
6. *Settings > Reading*: set **Home** as the static front page. *Settings > Permalinks*: **Post name**.
7. Build the WordPress menu later if you want; the header already links to every page.

| File | Page title | Slug |
|---|---|---|
| 01-home | Home | *(front page)* |
| 02-about | About | `about` |
| 03-services | Services | `services` |
| 04-general-remodeling | General Remodeling | `general-remodeling` |
| 05-bathroom-remodeling | Bathroom Remodeling | `bathroom-remodeling` |
| 06-adu-garage-conversions | ADUs & Garage Conversions | `adu-garage-conversions` |
| 07-flooring-tile | Flooring & Tile | `flooring-tile` |
| 08-outdoor-improvements | Outdoor Improvements | `outdoor-improvements` |
| 09-projects | Projects | `projects` |
| 10-service-area | Monterey Peninsula Service Area | `monterey-peninsula-service-area` |
| 11-request-estimate | Request an Estimate | `request-an-estimate` |
| 12-contact | Contact | `contact` |
| 13-thank-you | Thank You | `thank-you` (set to noindex) |
| 14-privacy-policy | Privacy Policy | `privacy-policy` |
| 15-terms | Terms | `terms` |

The slugs must match, because all internal links use them.

## The estimate form

Plain HTML can't send email from WordPress on its own. Pick one:

**A. Elementor Pro Form widget (recommended).** On the Request an Estimate page, delete the `<form> … </form>` block from the pasted HTML, and put an Elementor **Form** widget in its place (split the page into two HTML widgets around it, or put the form in its own column). Give the widget the CSS class `kc-elementor-form` (Advanced > CSS Classes) so it matches the design. Fields:

| Label | Type | Required |
|---|---|---|
| Full name | Text | yes |
| Email address | Email | yes |
| Phone number | Tel | yes |
| Property address | Text | yes |
| Preferred contact method | Radio: Phone call / Text message / Email | |
| Project type | Select: General / whole-home remodeling, Kitchen remodeling, Bathroom remodeling, ADU or garage conversion, Flooring, Tile, Fence or exterior carpentry, Concrete, pavers or hardscape, Other | yes |
| Approximate budget | Select: Under $10,000 · $10,000–$25,000 · $25,000–$50,000 · $50,000–$100,000 · $100,000–$250,000 · $250,000+ · Not sure yet | |
| Preferred start timeline | Select: As soon as possible · Within 1–3 months · In 3–6 months · 6+ months from now · Just planning / flexible | |
| Project description | Textarea | yes |
| Photos or plans | File Upload (multiple, max 10, jpg/png/heic/pdf) | |
| Consent | Acceptance | yes |
| (spam) | reCAPTCHA v3 or Honeypot | |

Actions After Submit: **Email** (to the Kovarik business address) + **Redirect** to `/thank-you/`.

**B. Keep the HTML form.** Set `form_action` in `site.config.json` to a form service endpoint that emails the business (for example Formspree; file uploads need a plan that supports them), run `python3 build.py`, and re-paste page 11. Until then the form shows "not connected yet" with the phone number and email instead of sending.

## Replace before launch

1. **Contact details.** `(831) 555-0100`, `info@kovarikconstruction.com` and `Mon–Fri, 7:00 am – 5:00 pm` are **placeholders**. Put the real ones in `site.config.json` and rebuild, or use Find & Replace on the pasted code. Update `phone_tel` too (digits only, `+1…`), since it drives click-to-call and the schema.
2. **Photos.** Every image is a grey "Replace with project photo" placeholder. Upload real photos to the Media Library, copy each URL, and paste it into the matching `src="…"`. Also update the `alt="…"` text (the `[brackets]` show what to describe). Keep photos under about 300 KB (WebP or compressed JPG, 1600 px wide).
3. **Logo.** The header and footer use a text logo (orange "K" mark). When the logo file is ready, replace the two `<span>`s inside `.kc-logo` with `<img src="LOGO-URL" alt="Kovarik Construction Inc.">`.
4. **Text in [brackets].** Project names and descriptions (Home, Projects), the company story (About) and reviews (Home). Only use **real** reviews, with the customer's permission, or embed a Google Reviews widget instead.
5. **Check the claims.** Confirm the copy matches how Kovarik actually works: the 4-step process, "written estimate", "crew", the service lists on each page, and the city descriptions on the service-area page. I avoided claims the brief didn't support (no "insured", "free estimates", years in business or ratings).
6. **Legal pages.** Privacy Policy and Terms are starting templates. Have them reviewed, and fill in the "Last updated" date.
7. **Google Maps.** The map embeds show the Monterey Peninsula. Once the Google Business Profile is live, you can swap in its embed code.
8. **SEO.** Enter the titles and descriptions from `elementor/SEO.md` in Rank Math or Yoast. The Home page carries `GeneralContractor` schema and each service page carries `Service` schema.
9. **Social links.** Add Facebook / Instagram / Yelp / Google links in the footer (there's a comment marking the spot) once the profiles exist.

## Adding projects to the gallery

In the Projects page HTML, copy one `<button class="kc-gallery__item" …> … </button>` block and change:
- `data-cat`: one of `remodeling`, `bathroom`, `adu`, `flooring`, `outdoor` (drives the filter buttons)
- `src` and `alt` of the image
- the caption (project name and city)

Clicking a photo opens it full-screen with previous/next arrows. The before/after slider at the top takes two photos of the same view.

## Editing and rebuilding (optional)

If you'd rather change things once and regenerate every file:

```bash
python3 build.py        # no dependencies
```

- `site.config.json`: name, license, phone, email, hours, form endpoint (used everywhere)
- `src/global.css`, `src/header.html`, `src/footer.html`, `src/pages/*.html`: the source, with tokens like `{{phone}}`, `{{ico:phone}}` (icons) and `{{img:Label}}` (placeholder images)
- Output goes to `elementor/` (paste-ready) and `preview/` (viewable)

## Notes

- All CSS is scoped under the `.kc` class, so it won't restyle other parts of WordPress, and theme styles won't break it.
- Icons are inline SVG, so no icon font or plugin is needed.
- On phones, a fixed **Call Now / Get an Estimate** bar appears at the bottom of every page (part of the footer).

## Cloudflare preview (for client review)

`wrangler.jsonc` deploys `preview/` as a static site, so Jan can review the design before it goes into WordPress. The preview sends a `noindex` header, so Google won't index it.

Cloudflare dashboard → *Workers & Pages* → *Create* → *Import a repository* → pick this repo, then:
- **Root directory:** `kovarik-construction`
- **Build command:** leave empty
- **Deploy command:** `npx wrangler deploy` (the default)
- **Production branch:** the branch that has these files

Cloudflare gives you a `kovarik-construction.<your-subdomain>.workers.dev` link and redeploys on every push. Run `python3 build.py` and commit before pushing, because Cloudflare serves the committed `preview/` folder.
