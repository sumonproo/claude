# K&J Gutters website

A static site with no build step. The website lives in `public/` (`index.html`, `styles.css`, `main.js`, `assets/`). Fonts (Geist) and icons (Phosphor Light) are stored under `assets/`, so the site needs no CDN.

Preview locally:

```bash
cd public && python3 -m http.server 8000
```

## Cloudflare

- **Workers (default import):** `wrangler.jsonc` is included, so the default deploy command `npx wrangler deploy` works. Leave the build command empty.
- **Pages:** framework preset *None*, build command empty, build output directory `public`.
- Set the **production branch** to the branch that has these files.

## Before launch

1. **Contact details:** fill in `CONFIG` at the top of `public/main.js` (phone, email, service area). Empty fields stay hidden.
   Estimate requests open the visitor's email app, addressed to `CONFIG.email`. Until that is set, the form copies the details and points visitors to an Instagram DM.
2. **Photos:** the images in `public/assets/img/` come from the Instagram feed and are only 640px wide. Higher-resolution originals will look sharper, especially `hero.jpg`.
3. **Logo:** `public/assets/brand-logo.jpg` is a 150px image, so it is only used small in the footer. A vector (SVG) version would allow it in the nav too.
4. **Check the claims:** confirm that the copy matches how K&J works (copper offered, micro-mesh guards, typical one-day installs).
