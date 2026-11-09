# Long Haul Brewing website

A one-page site in plain files: `index.html` (words and layout), `styles.css` (look), `main.js` (moving parts and easy-to-edit details), `assets/fonts/` (Bowlby One and Work Sans, stored here so nothing loads from elsewhere). No build step.

Open `index.html` in a browser to see it. Some browsers limit pages opened straight from disk; if anything looks off, preview it with:

```bash
cd long-haul-brewing && python3 -m http.server 8000
```

then visit http://localhost:8000.

## Placeholders to fill in

Every placeholder shows on the page with a yellow highlight and [square brackets]. Search the files for `[` or `PLACEHOLDER`.

In `main.js` (top of the file, `CONFIG` and `BEERS`):
- `closeHour`: closing time (used by the "open now" note)
- `foodTrucks`: the truck for each night
- `bookingUrl`: a link to your tour booking system (until set, the form tells people to call)
- `signupUrl`: your email-list signup address (until set, the signup isn't connected)
- Cans 4, 5 and 6: name, style, ABV and a line about each
- Tasting notes for the Hazy IPA, Smoked Porter and Pilsner (they currently describe the style in general)
- `STOCKISTS`: the real shops and bars, and which beers each one has

In `index.html`:
- Closing time on each day in the hours table
- Your story (taproom section)
- The draft list (link or current board)
- Start times for Thursday bluegrass and Tuesday trivia
- The real tour stops and how long the tour runs (the floor plan is a sketch)
- Street address (footer)

## Going live

It's a static site, so any static host works. With Cloudflare Pages, Netlify or GitHub Pages, set the publish/output folder to `long-haul-brewing` and leave the build command empty.
