# K&J Gutters website

A static site with no build step: `index.html`, `styles.css` and `main.js`. Fonts (Geist) and icons (Phosphor Light) are stored under `assets/`, so the site needs no CDN.

Preview locally:

```bash
python3 -m http.server 8000
```

Deploy by uploading this folder to any static host (Netlify, Vercel, Cloudflare Pages, GitHub Pages).

## Before launch

1. **Contact details:** fill in `CONFIG` at the top of `main.js` (phone, email, service area). Empty fields stay hidden.
   Estimate requests open the visitor's email app, addressed to `CONFIG.email`. Until that is set, the form copies the details and points visitors to an Instagram DM.
2. **Photos:** the images in `assets/img/` come from the Instagram feed and are only 640px wide. Higher-resolution originals will look sharper, especially `hero.jpg`.
3. **Logo:** `assets/brand-logo.jpg` is a 150px image, so it is only used small in the footer. A vector (SVG) version would allow it in the nav too.
4. **Check the claims:** confirm that the copy matches how K&J works (copper offered, micro-mesh guards, typical one-day installs).
