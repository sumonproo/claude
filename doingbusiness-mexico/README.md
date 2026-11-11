# Doing Business Mexico: page concepts for WordPress + Elementor

Static HTML concepts for doingbusiness-mexico.com, to review with the client and then rebuild in Elementor. English only.

| Page | File |
|---|---|
| Home | `index.html` |
| IMMEX | `immex.html` |
| Labor | `labor.html` |
| Corporate | `corporate.html` |
| Real estate | `real-estate.html` |
| Blog | `blog.html` |
| Blog post template | `blog-post.html` |
| Experts directory (the "members page") | `experts.html` |
| Book a consultation / Send an inquiry | `contact.html` |

Preview:

```bash
cd doingbusiness-mexico && python3 -m http.server 8000
```

Edit content in `build.py`, then run `python3 build.py` to regenerate the pages.

## Rebuilding in Elementor

- **Global colors and fonts:** copy the tokens at the top of `styles.css` into Site Settings (Archivo, one family).
- **Header, footer and CTA band:** Theme Builder templates, shared by every page.
- **Guide pages:** one Single Page template with a sticky contents column, the article, and a sticky help box that shows the expert for that topic.
- **Blog post:** a Theme Builder Single Post template. The byline and author box come from the post author, so every expert should be a WordPress user with a bio and photo.
- **Experts:** one entry per expert (a custom post type or a Loop Grid), with a topic taxonomy that drives the filter buttons. The client can add more experts over time without design work.
- **Forms:** use the Elementor Form widget for the inquiry form, with email delivery. Embed the booking calendar (Calendly or similar) in the slot on `contact.html`.

## Speed

- Only `main.js` (~4 KB) loads on each page. The AI chat (`chat.js`) is fetched only when a visitor clicks "Ask a question" or uses the search box on the home page, so it does not affect load time or Core Web Vitals.
- Self-host Archivo (or let Elementor load it locally), convert images to WebP, and remove plugins the new pages don't use.

## AI chat

`chat.js` currently answers from a small built-in index of the guide content, so the concept works with no server. For production, set `ENDPOINT` to a server route (a WordPress REST endpoint or a small worker) that sends the question and the site's guide content to an AI model and returns `{ answer, links }`. Keep the API key on the server.

## Placeholders to replace

- Expert names, photos and bios: the client is sending at least 5 experts.
- Guide and blog copy is a draft based on general rules. The client is writing the final copy, and each guide should be reviewed by its expert.
- Blog featured images are colored placeholders.
- The "Inquiry sent" message is shown without sending anything until the real form is connected.
