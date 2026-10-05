# Artist Directory for Elementor

A small plugin that adds the custom functionality for the Artist Directory + Events site. You design every page and template in Elementor Pro; this plugin supplies the data, dynamic tags, query IDs, two widgets, a form action, schema and demo content.

- WordPress 6.4+, PHP 8.1+, Elementor + Elementor Pro (3.5+), Hello Elementor child theme
- Uses only native WordPress APIs (no ACF/JetEngine). Everything is prefixed `ade_`.
- Follows WordPress Coding Standards (`phpcs.xml.dist` is included).

## Install

1. Copy `artist-directory-elementor/` to `wp-content/plugins/` and activate it.
2. Copy `hello-elementor-child/` (it sits next to the plugin in this repo) to `wp-content/themes/` and activate it. Hello Elementor must be installed.
3. **Settings → Permalinks → Save** (the plugin flushes rewrites on activation; this is just a safety net).
4. Optional: `wp ade seed` for demo content (see [WP-CLI](#wp-cli)).

## File structure

```
artist-directory-elementor/
├── artist-directory-elementor.php      bootstrap
├── includes/
│   ├── helpers.php                     ade_* template functions
│   ├── class-ade-plugin.php            loader, activation
│   ├── class-ade-post-types.php        CPTs, taxonomies, registered meta
│   ├── class-ade-admin.php             meta boxes, gallery uploader, pickers, list columns
│   ├── class-ade-submissions.php       "Pending submissions" view, Approve action
│   ├── class-ade-queries.php           elementor/query/{id} handlers
│   ├── class-ade-directory.php         directory query + rendering (shared)
│   ├── class-ade-calendar.php          calendar renderer (shared)
│   ├── class-ade-rest.php              ade/v1/artists, ade/v1/calendar
│   ├── class-ade-calendar-export.php   .ics download
│   ├── class-ade-schema.php            JSON-LD
│   ├── class-ade-elementor.php         registers widgets, tags, form action, assets
│   └── class-ade-cli.php               wp ade seed | unseed
├── dynamic-tags/                       one class per tag
├── widgets/                            Directory Filter, Events Calendar
├── form-actions/                       Create Pending Artist
└── assets/css, assets/js
```

## 1. Data structure

### Post types and taxonomies

| Type | Slug | URL | Notes |
|---|---|---|---|
| Post type | `artist` | `/artists/{name}/`, archive `/artists/` | title, content (bio), excerpt, featured image (artist photo) |
| Post type | `event` | `/events/{name}/`, archive `/events/` | title, content, excerpt, featured image |
| Taxonomy | `discipline` | `/discipline/{term}/` | on artist; hierarchical, admin column |
| Taxonomy | `artist_location` | `/artist-location/{term}/` | on artist; hierarchical, admin column |

### Meta keys

These are all registered with `register_post_meta()`, so they are sanitized on save and exposed in the REST API (except the contact email). Elementor Pro's **Post Custom Field** tag can read the plain-text keys as well.

| Post type | Key | Type | Notes |
|---|---|---|---|
| artist | `ade_website` | URL | |
| artist | `ade_instagram` | URL | `@handle` is converted to a full URL |
| artist | `ade_facebook` | URL | page name is converted to a full URL |
| artist | `ade_youtube` | URL | `@handle` is converted to a full URL |
| artist | `_ade_contact_email` | email | **private**: protected key, not in REST, no tag |
| artist | `ade_featured` | bool | |
| artist | `ade_gallery` | int[] | attachment IDs, in order |
| artist | `ade_linked_products` | int[] | WooCommerce product IDs |
| event | `ade_start_datetime` | `Y-m-d H:i:s` | site timezone |
| event | `ade_end_datetime` | `Y-m-d H:i:s` | always saved; defaults to the start |
| event | `ade_venue` | text | |
| event | `ade_address` | multi-line text | |
| event | `ade_ticket_url` | URL | |
| event | `ade_related_artists` | int, one row per artist | stored as separate meta rows so events can be queried by artist |

**Admin UI:** the artist screen has **Artist Details**, **Artist Gallery** (media uploader with drag-to-reorder) and **Linked Products** (a searchable product picker when WooCommerce is active, otherwise a product-ID field). The event screen has **Event Details** (date/time pickers) and **Related Artists** (a searchable artist picker). The artist list shows photo and featured columns; the event list has a sortable **Starts** column.

**Test it:** open Artists → Add New. Fill in the details, add 3 gallery images, drag them to reorder, and save. Reload and check the order stuck. Enter `@someone` for Instagram; after saving it shows `https://www.instagram.com/someone`. Then create an event with no end time, pick two artists and save. The end time is filled in with the start time.

## 2. Dynamic tags

In any widget, click the **dynamic tags** icon (the stacked-discs icon) and look for the **Artist** and **Event** groups. Every tag reads the artist or event *in context*: the current post inside a Loop Grid item, otherwise the single page being viewed.

| Tag (panel name) | ID | Works in | Output |
|---|---|---|---|
| Artist Website | `ade-artist-website` | text, link | URL |
| Artist Instagram URL | `ade-artist-instagram` | text, link | URL |
| Artist Facebook URL | `ade-artist-facebook` | text, link | URL |
| Artist YouTube URL | `ade-artist-youtube` | text, link | URL |
| Artist Gallery | `ade-artist-gallery` | gallery | images for Gallery / Basic Gallery / Image Carousel |
| Artist Linked Products | `ade-artist-linked-products` | text | `<ul>` of product links. Options: price, thumbnail. **Empty when WooCommerce is inactive.** |
| Event Venue | `ade-event-venue` | text | |
| Event Address | `ade-event-address` | text | Option: single line (commas) or keep line breaks |
| Event Date / Time | `ade-event-datetime` | text | Options: smart range / start / start date / start time / end… + custom PHP date and time formats |
| Event Ticket URL | `ade-event-ticket-url` | text, link | |
| Event: Add to Google Calendar URL | `ade-event-gcal-url` | link | |
| Event: Download .ics URL | `ade-event-ics-url` | link | `/?ade_ics={id}` serves an RFC 5545 `.ics` file |
| Event: Google Maps URL | `ade-event-map-url` | link | map search for venue + address |

Use Elementor's own tags for the title, content, featured image, excerpt and terms (Post Title, Post Content, Featured Image, Post Terms).

**Test it:** edit a single-event template (see [Theme Builder wiring](#theme-builder-wiring)). Set its preview to an event (⚙ → Preview Settings). Add a Heading, choose the **Event Date / Time** tag and check the date appears in the editor. Add a Button, set Link → dynamic → **Event: Download .ics URL**, then click it on the live page. A `.ics` file downloads and imports into Apple, Google or Outlook calendars.

## 3. Loop Grid query IDs

In a **Loop Grid** (or Loop Carousel / Posts) widget, go to **Query → Query ID** and enter one of the IDs below. The plugin sets the post type, so the Source setting does not matter. Posts per page, offset and pagination still come from the widget.

| Query ID | Use on | Returns |
|---|---|---|
| `ade_upcoming_events` | any page | events with start ≥ now, soonest first |
| `ade_past_events` | any page | events with start < now, most recent first |
| `ade_featured_artists` | any page | artists marked Featured |
| `ade_artist_events` | single artist template | all events linked to this artist, chronological |
| `ade_artist_upcoming_events` | single artist template | this artist's upcoming events only |
| `ade_event_artists` | single event template | the event's related artists, in picker order |
| `ade_artist_directory` | directory page | artists filtered by the Directory Filter URL parameters (see §4) |
| `ade_artist_products` | single artist template | WooCommerce products linked to the artist |

The contextual IDs (`ade_artist_*`, `ade_event_artists`, `ade_artist_products`) work with **no pagination** or **Load on click**. Leave them unpaginated: the lists are short.

**Test it:** on a page, add a Loop Grid, pick an event loop item template and set Query ID `ade_upcoming_events`. Run `wp ade seed`: the grid shows 5 events in date order, and with `ade_past_events` it shows 3. On the single artist template, a Loop Grid with `ade_artist_events` shows only that artist's events. Switch the preview artist to see the list change.

## 4. Artist Directory Filter widget

**Panel:** Artist Directory → *Artist Directory Filter*.

- Keyword search (title and bio), Discipline dropdown, Location dropdown, Sort (A–Z, Z–A, newest). Each can be shown or hidden and relabelled.
- **Filter as you type / select** (debounced), or update only when the button is pressed.
- AJAX over `GET /wp-json/ade/v1/artists`. Results are rendered with **your loop item template** (Results → Loop item template).
- Pagination: **Load more** (moves keyboard focus to the first new artist) or **page numbers**. Without JavaScript, the controls fall back to plain links.
- Accessibility: real `<label>`s (visually hideable), a `role="search"` form, an `aria-live` results count ("5 artists found"), `aria-busy` while loading, `aria-current` on the current page, and 44px tap targets.
- **Keep filters in the URL** (`?ade_s=…&ade_discipline=…&ade_location=…&ade_sort=…&ade_page=…`) makes filtered views shareable.
- Style tab: form layout and gap, labels, inputs (typography, colors, border, radius, padding, focus ring), buttons (normal/hover), results count, results grid columns and gaps, pagination.

There are two ways to show results:

- **A. Filter an Elementor Loop Grid (recommended).**
  1. Add a **Loop Grid**: choose your artist loop item, Query ID `ade_artist_directory`, pagination **None**, and the Posts Per Page you want. In Advanced → **CSS ID**, enter `artist-grid`.
  2. Add the **Artist Directory Filter** above it. Under Results, set **Loop item template** to the same template, **Loop Grid CSS ID** to `artist-grid`, and **Artists per page** to the same number.
  3. The filter replaces the grid's items and puts its own Load more / page numbers under the grid. The Loop Grid's first render uses the same URL parameters, so shared links open pre-filtered.
- **B. Self-contained.** Leave **Loop Grid CSS ID** empty. The widget renders the results grid itself, with responsive column and gap controls.

**Test it:** publish the page and open it logged out. Pick a discipline: the grid updates without a reload, the count changes, and the URL gains `?ade_discipline=…`. Add a location and type part of a name; the filters combine. Press **Clear** to see all artists again. Use **Load more** with only the keyboard; focus lands on the first new card. With a screen reader (VoiceOver: ⌘F5), the count is announced after each change.

## 5. Events Calendar widget

**Panel:** Artist Directory → *Events Calendar*.

- **Month grid** and **List** views with a toggle, previous/next month, and a "This month" shortcut. Navigation is AJAX over `GET /wp-json/ade/v1/calendar`, with `?ade_month=YYYY-MM&ade_view=list` links as the no-JS fallback.
- Events link to their single page. Multi-day events appear on every day they cover.
- Below 768px, the month grid collapses into an agenda of the days that have events.
- Accessibility: a real `<table>` with caption and column headers, `aria-current="date"` on today, a polite live region ("November 2026, month view: 2 events"), and focus kept on the button you pressed.
- Settings: default view, week start, show times / venue / image (list view), title tag, empty-state text.
- Style tab: header (title, buttons, active view, focus ring), grid (weekday row, borders, day / empty / today backgrounds, date typography, cell height and padding), event chips (normal/hover), list (date badge, title, meta, spacing).

**Add to calendar on single events:** use two Buttons with the **Event: Add to Google Calendar URL** and **Event: Download .ics URL** tags.

**Test it:** add the widget to an Events page. Click › to move to next month; the title changes without a reload. Switch to **List**, then resize the editor to mobile and check the grid turns into a day list. Click an event to open its single page.

## 6. Artist submission form (Elementor Pro Forms)

1. Add a **Form** widget. In each field's **Advanced → ID**, give the fields these IDs (or change the mapping in step 3):

   | Field | Type | ID |
   |---|---|---|
   | Name | Text (required) | `name` |
   | Email | Email | `email` |
   | Discipline | Select or Checkbox. Option values = discipline names or slugs | `discipline` |
   | Location | Select. Options = location names or slugs | `location` |
   | Bio | Textarea | `bio` |
   | Portfolio link | URL | `portfolio` |
   | Images | File Upload: multiple, `jpg,jpeg,png,webp`, Attachment type **Link** or **Both** | `images` |

2. **Actions After Submit:** add **Create Pending Artist** and keep **Email** (and Collect Submissions / Redirect) if you want them. Put Create Pending Artist **first**.
3. The **Create Pending Artist** section lets you change the field IDs and set the admin notification (on by default, sent to the site admin email unless you set other addresses).

**What happens on submit:**

- A pending `artist` post is created: name → title, bio → content, email → private email meta, portfolio → website.
- Discipline and location are matched to *existing* terms; unknown values are listed in the Submission box instead of creating terms.
- Up to 10 uploaded images are copied into the Media Library and attached to the post. The first becomes the featured image and all of them go into the gallery.
- An admin email is sent with review links. Elementor's own email, confirmation message and redirect keep working.

**Reviewing:** Artists → **Pending submissions** lists only form submissions (the Artists menu shows a count bubble). Each row has a quick **Approve** link. There is also a bulk **Approve** action and an **Approve & publish** button in the Submission box on the edit screen. Every approve path checks a nonce and the publish capability.

**Test it:** submit the form while logged out with 2 images. Check you see the normal success message and receive both Elementor's email and the plugin's notification. In wp-admin, open Artists → Pending submissions: the artist is there with a photo and gallery. Click **Approve**; it moves to Published and appears in the directory.

## 7. SEO: JSON-LD

- Single artist → `Person`: name, url, image, description, disciplines (`knowsAbout`), location, and `sameAs` (website and social links).
- Single event → `Event`: start/end with timezone offset, `Place` + `PostalAddress`, image, an `Offer` with the ticket URL, `performer` (related artists), and `organizer` (the site).
- If your SEO plugin already outputs these types, disable ours with `add_filter( 'ade_schema', '__return_empty_array' );`.

**Test it:** open a live artist or event page in Google's Rich Results Test (or view source and search for `application/ld+json`).

## 8. WooCommerce readiness

- The child theme declares `woocommerce` support and the product-gallery features.
- Link products to artists in the **Linked Products** box. Output them with the **Artist Linked Products** tag, or with a Loop Grid using Query ID `ade_artist_products` and a product loop item. Both render nothing when WooCommerce is inactive.

## Theme Builder wiring

Go to **Templates → Theme Builder**.

### Artist loop item (card)
1. **Loop Item → Add New**, name it "Artist card". In ⚙ Preview Settings choose post type **Artists**.
2. Add Featured Image (link: Post URL), a Heading with **Post Title**, and a Text with **Post Terms** (taxonomy Discipline). Optionally add an Icon with Link → **Artist Instagram URL**.
3. Publish. Use this template in the directory Loop Grid *and* in the Directory Filter widget.

### Event loop item (card)
1. **Loop Item → Add New**, "Event card", preview post type **Events**.
2. Add Featured Image, a Heading with **Post Title**, Text with **Event Date / Time** (Show: Start date + time), and Text with **Event Venue**.
3. Publish.

### Single artist
1. **Single Post → Add New**, "Single artist". Conditions: **Include → Artists**.
2. Add a Heading with **Post Title**, a Featured Image, and a Text Editor with **Post Content** (the bio).
3. Add buttons or icons for the website and socials, with Link set to the **Artist Website / Instagram / Facebook / YouTube** tags.
4. Add a **Gallery** widget → Images → dynamic → **Artist Gallery**.
5. Add a Loop Grid with the "Event card" template, Query ID `ade_artist_upcoming_events` (or `ade_artist_events`), and no pagination.
6. Optional shop block: Text Editor → **Artist Linked Products**, or a Loop Grid with Query ID `ade_artist_products`.

### Single event
1. **Single Post → Add New**, "Single event". Conditions: **Include → Events**.
2. Add Post Title, Featured Image, Text with **Event Date / Time** (smart range), **Event Venue**, and **Event Address** (keep line breaks).
3. Add buttons: **Tickets** (link → Event Ticket URL), **Add to Google Calendar** (→ Event: Add to Google Calendar URL), **Download .ics** (→ Event: Download .ics URL), and **Map** (→ Event: Google Maps URL).
4. Add Post Content.
5. Add a Loop Grid with the "Artist card" template and Query ID `ade_event_artists`.

### Artist archive / directory
1. **Archive → Add New**, "Artist directory". Conditions: **Include → Artists Archive** (and the Discipline / Location archives if you want them).
2. Add the **Artist Directory Filter** and a **Loop Grid** wired as in [§4, option A](#4-artist-directory-filter-widget). Use Query ID `ade_artist_directory` on the archive too.
3. Alternative: a normal Page with the same two widgets.

### Events page
A Page with the **Events Calendar** widget, plus Loop Grids with `ade_upcoming_events` and `ade_past_events` if you also want card lists.

### Home page
A Loop Grid with "Artist card" and Query ID `ade_featured_artists`, plus a Loop Grid with "Event card", `ade_upcoming_events` and 3 per page.

## WP-CLI

```bash
wp ade seed                # 6 disciplines, 4 locations, 12 artists (4 featured, photo + 3 gallery images each), 8 events (3 past, 5 upcoming, 1 multi-day)
wp ade seed --no-images    # faster; skips GD placeholder images
wp ade seed --force        # remove existing demo content, then seed again
wp ade unseed --yes        # remove demo posts, images and demo-created terms
```

Event dates are relative to the day you run the command, so "upcoming" always has content. Demo content is tagged `_ade_seed` and never touches your real posts.

## REST endpoints (used by the widgets)

Both are read-only and return published content only. The widget sends the `wp_rest` nonce for logged-in users.

- `GET /wp-json/ade/v1/artists?s=&discipline=&location=&sort=az|za|newest&page=&per_page=&template_id=&pagination=` → `{ html, total, pages, page, count_text, pagination, query }`. `template_id` must be a published Elementor template; otherwise a plain card is used.
- `GET /wp-json/ade/v1/calendar?month=YYYY-MM&view=month|list&week_start=&show_time=&show_venue=&show_thumb=&toggle=&tag=` → `{ html, month, view, status }`

## Hooks for developers

| Hook | Type | Purpose |
|---|---|---|
| `ade_directory_query_args` | filter | change the directory `WP_Query` args |
| `ade_schema` | filter | change or disable the JSON-LD |
| `ade_datetime_separator` | filter | separator in formatted dates (default ` · `) |
| `ade_artist_submitted` | action | `( $post_id, $record )` after a form creates an artist |
| `ade_artist_approved` | action | `( $post_id )` after Approve |

Template helpers (in `includes/helpers.php`): `ade_format_event_datetime()`, `ade_get_event_dates()`, `ade_get_artist_gallery_ids()`, `ade_get_related_artist_ids()`, `ade_get_linked_product_ids()`, `ade_google_calendar_url()`, `ade_ics_url()`, `ade_map_url()`.

## Coding standards

```bash
composer global require wp-coding-standards/wpcs
phpcs   # uses phpcs.xml.dist (WordPress-Extra + WordPress-Docs)
```

The only excluded sniff is `PrefixAllGlobals`, because it rejects the required 3-letter `ade_` prefix as too short.
