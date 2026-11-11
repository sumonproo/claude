# Doing Business Mexico: redesign

Static redesign of doingbusiness-mexico.com (no build step): `index.html`, `styles.css`, `main.js`.

```bash
cd doingbusiness-mexico && python3 -m http.server 8000
```

- **Languages:** EN / 中文 toggle. Chinese strings live in `ZH` in `main.js`. The English text in the HTML is the source.
- **Live clocks:** the hero shows Hong Kong and Mexico City time (14 h apart).
- **Before launch:** set `CONFIG.email` in `main.js` so the form can send requests. Add office addresses and phone numbers, and link the guides to real articles (they currently point to the contact form).
- **Check the copy:** service and region descriptions are drafts based on public information about the platform and must be confirmed by VTZ.
