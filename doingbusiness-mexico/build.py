"""Builds the static HTML pages for the Doing Business Mexico concept.

Shared header, footer and page shells live here so every page stays consistent.
Run:  python3 build.py   (writes the .html files next to this script)
"""
from pathlib import Path
from html import escape

OUT = Path(__file__).parent


def ver(name):
    """Short content hash, so browsers never pair a new page with an old stylesheet or script."""
    import hashlib
    return hashlib.sha1((OUT / name).read_bytes()).hexdigest()[:8]
SITE = "Doing Business Mexico"
REVIEWED = "November 2026"

NAV = [
    ("experts.html", "Experts"),
    ("blog.html", "Blog"),
]  # the Guides dropdown is built from GUIDES, so new guides appear in the menu automatically

GUIDES = {
    "immex": ("immex.html", "IMMEX", "Import inputs duty-free for export manufacturing, and how to qualify."),
    "trade": ("international-trade.html", "International trade", "Importing and exporting: registrations, customs, tariffs and trade agreements."),
    "labor": ("labor.html", "Labor", "Contracts, mandatory benefits, unions and hiring foreign staff."),
    "corporate": ("corporate.html", "Corporate", "Choosing an entity, incorporating, and staying compliant."),
    "real-estate": ("real-estate.html", "Real estate", "Buying or leasing industrial land, and the checks to run first."),
}

EXPERTS = [
    ("ex1", "immex", "IMMEX and customs", "Monterrey, Nuevo León", "English, Spanish",
     "Customs specialist who sets up IMMEX programs, Annex 24 inventory controls and VAT certification for manufacturers."),
    ("ex2", "labor", "Labor and employment", "Mexico City", "English, Spanish",
     "Employment lawyer covering contracts, union negotiations, REPSE registration and terminations."),
    ("ex3", "corporate", "Corporate and tax", "Mexico City", "English, Spanish, Mandarin",
     "Corporate lawyer who forms companies for foreign investors and handles ongoing tax compliance."),
    ("ex4", "real-estate", "Industrial real estate", "Querétaro", "English, Spanish",
     "Site selection advisor for industrial parks in the Bajío, from shortlist to signed lease."),
    ("ex5", "security", "Security and risk", "Guadalajara, Jalisco", "English, Spanish",
     "Security consultant who prepares site risk assessments and travel protocols for visiting teams."),
]
TOPIC_NAMES = {"immex": "IMMEX, customs and trade", "labor": "Labor", "corporate": "Corporate and tax",
               "real-estate": "Real estate", "security": "Security"}

POSTS = [
    ("immex", "IMMEX", "Annual IMMEX report: what to file and when", "Every IMMEX holder files an annual report with the Ministry of Economy. Here is what goes in it.", "6 min read"),
    ("trade", "International trade", "Importing from a country without a trade agreement: check your tariff rate first", "Duties on many goods from non-agreement countries have gone up. How to find the rate that applies to your product.", "5 min read"),
    ("labor", "Labor", "Profit sharing (PTU): how the cap works", "Employers share 10% of taxable profit with staff. The 2021 reform added a cap per employee.", "5 min read"),
    ("corporate", "Corporate", "S.A. de C.V. or S. de R.L.: which one fits a foreign parent?", "The two most common entity types compared, including how US tax rules treat each.", "7 min read"),
    ("real-estate", "Real estate", "Industrial lease terms to negotiate before you sign", "Currency, escalation, deposits and who pays for what in a typical park lease.", "8 min read"),
    ("labor", "Labor", "Hiring through a staffing agency after the outsourcing ban", "What is still allowed, and when a provider must be registered in REPSE.", "6 min read"),
    ("corporate", "Corporate", "Beneficial owner records: what your company must keep", "Mexican companies must identify and document who ultimately controls them.", "4 min read"),
]


# Unsplash photos are hotlinked from images.unsplash.com, as Unsplash's API terms require.
# Their CDN resizes and serves WebP/AVIF automatically (auto=format).
PHOTOS = {
    "immex": ("1647427060118-4911c9821b82", "Simon Kadula", "simonkadula", "Robots on an automotive assembly line"),
    "labor": ("1652211955967-99c892925469", "Arno Senoner", "arnosenoner", "A team at work on a factory floor"),
    "corporate": ("1681505531034-8d67054e07f6", "Amina Atar", "minaslens", "Two people shaking hands over a signed document"),
    "real-estate": ("1759310347467-578dfd846229", "Mathias Reding", "matreding", "Industrial warehouse with loading gates"),
    "port": ("1590497008432-598f04441de8", "Timelab", "timelabpro", "Container port with cranes"),
    "trade": ("1590497008432-598f04441de8", "Timelab", "timelabpro", "Container port with cranes at dusk"),
    "containers": ("1678182451047-196f22a4143e", "Ali Mkumbwa", "mkumbwajr", "Shipping containers stacked at a port"),
    "docs": ("1583521214690-73421a1829a9", "Wesley Tingey", "wesleyphotography", "Stacks of documents and file folders"),
    "garment": ("1741591646737-e634ae96e83d", "EqualStock", "equalstock", "Workers in a garment factory"),
    "meeting": ("1681505504714-4ded1bc247e7", "Amina Atar", "minaslens", "Two people reviewing papers at a table"),
    "building": ("1669003750682-93cf2c65b9ca", "Point3D Commercial Imaging", "3dottawa", "Industrial building with loading doors"),
    "team": ("1651525670114-2b8117390b28", "Arno Senoner", "arnosenoner", "People working in a factory"),
    "signing": ("1635859890085-ec8cb5466806", "Dimitri Karastelev", "dkfra19", "Person signing a contract"),
}
UTM = "utm_source=doingbusiness_mexico&utm_medium=referral"
TOPIC_PHOTO = {"immex": "immex", "trade": "port", "labor": "labor", "corporate": "corporate", "real-estate": "real-estate"}


def usrc(key, w, h):
    pid = PHOTOS[key][0]
    return f"https://images.unsplash.com/photo-{pid}?auto=format&fit=crop&w={w}&h={h}&q=70"


def photo(key, w, h, cls="", eager=False):
    alt = PHOTOS[key][3]
    lazy = 'fetchpriority="high"' if eager else 'loading="lazy" decoding="async"'
    return (f'<img class="{cls}" src="{usrc(key, w, h)}" srcset="{usrc(key, w, h)} 1x, {usrc(key, w * 2, h * 2)} 2x" '
            f'width="{w}" height="{h}" alt="{escape(alt)}" {lazy}>')


def credit(key):
    _, name, user, _ = PHOTOS[key]
    return (f'Photo by <a href="https://unsplash.com/@{user}?{UTM}">{escape(name)}</a> on '
            f'<a href="https://unsplash.com/?{UTM}">Unsplash</a>')

POST_PHOTO = ["docs", "containers", "garment", "meeting", "building", "team", "signing"]


def header(active):
    guide_files = [h for h, _, _ in GUIDES.values()]
    in_guides = active in guide_files
    items = "".join(
        f'<li><a href="{h}"{" aria-current=\"page\"" if h == active else ""}><span class="menu__name">{t}</span><span class="menu__desc">{d}</span></a></li>'
        for h, t, d in GUIDES.values()
    )
    guides = f"""<div class="menu">
        <button class="menu__btn{' is-current' if in_guides else ''}" type="button" aria-expanded="false" aria-controls="guides-menu">Guides<svg viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5 6 7.5 9 4.5"/></svg></button>
        <ul class="menu__panel" id="guides-menu">{items}</ul>
      </div>"""
    links = guides + "".join(
        f'<a href="{h}"{" aria-current=\"page\"" if h == active else ""}>{t}</a>' for h, t in NAV
    )
    return f"""<a class="skip" href="#main">Skip to content</a>
<header class="nav">
  <div class="wrap nav__inner">
    <a class="brand" href="index.html">
      <img class="brand__logo" src="assets/logo-ink.png" width="134" height="46" alt="Doing Business Mexico">
    </a>
    <nav class="nav__links" id="nav-links" aria-label="Main">{links}
      <a class="nav__mobile-cta" href="contact.html#inquiry">Send an inquiry</a>
    </nav>
    <a class="btn btn--small nav__cta" href="contact.html">Book a consultation</a>
    <button class="nav__toggle" type="button" aria-expanded="false" aria-controls="nav-links">
      <span class="sr-only">Menu</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
    </button>
  </div>
</header>"""


def cta_band(title="Need advice for your company?", text="Talk to an expert who works on these questions every day."):
    return f"""<section class="cta-band">
  <div class="wrap cta-band__inner">
    <div>
      <h2>{title}</h2>
      <p>{text}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn--light" href="contact.html">Book a consultation</a>
      <a class="btn btn--outline-light" href="contact.html#inquiry">Send an inquiry</a>
    </div>
  </div>
</section>"""


def footer():
    guides = "".join(f'<li><a href="{h}">{t}</a></li>' for h, t, _ in GUIDES.values())
    return f"""<footer class="foot">
  <div class="wrap foot__grid">
    <div class="foot__about">
      <a class="brand" href="index.html"><img class="brand__logo" src="assets/logo-white.png" width="134" height="46" alt="Doing Business Mexico"></a>
      <p>Free, public guidance for companies doing business in Mexico, written with the experts who do this work.</p>
    </div>
    <div><h2 class="foot__h">Guides</h2><ul>{guides}</ul></div>
    <div><h2 class="foot__h">Get help</h2><ul>
      <li><a href="experts.html">Find an expert</a></li>
      <li><a href="contact.html">Book a consultation</a></li>
      <li><a href="contact.html#inquiry">Send an inquiry</a></li>
      <li><a href="experts.html#contribute">Become a contributor</a></li>
    </ul></div>
    <div><h2 class="foot__h">Read</h2><ul>
      <li><a href="blog.html">Blog</a></li>
    </ul></div>
  </div>
  <div class="wrap foot__legal">
    <p>Content on this site is general information, not legal or tax advice. Rules change, so confirm details with an expert before acting.</p>
    <p>Stock photos from <a href="https://unsplash.com/?{UTM}">Unsplash</a>.</p>
  </div>
</footer>
<button class="chat-launch" type="button" aria-haspopup="dialog">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg><span>Ask a question</span>
</button>
<script src="main.js?v={ver('main.js')}" defer></script>"""


def page(filename, title, description, active, body, extra_head=""):
    full_title = title if title == SITE else f"{title} | {SITE}"
    html = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{escape(full_title)}</title>
<meta name="description" content="{escape(description)}">
<link rel="canonical" href="https://doingbusiness-mexico.com/{'' if filename == 'index.html' else filename.replace('.html', '/')}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,400..800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css?v={ver('styles.css')}">
{extra_head}</head>
<body>
{header(active)}
<main id="main">
{body}
</main>
{footer()}
</body>
</html>
"""
    (OUT / filename).write_text(html, encoding="utf-8")


def initials_avatar(n):
    return f'<div class="avatar" aria-hidden="true">E{n}</div>'


def expert_card(i, e, compact=False):
    key, topic, specialty, city, langs, bio = e
    return f"""<article class="expert" data-topic="{topic}">
  {initials_avatar(i + 1)}
  <div class="expert__body">
    <p class="expert__topic">{specialty}</p>
    <h3 class="expert__name">Expert name</h3>
    <p class="expert__meta">{city}<br>{langs}</p>
    {'' if compact else f'<p class="expert__bio">{bio}</p>'}
    <div class="expert__actions">
      <a class="btn btn--small" href="contact.html?expert={key}">Book a consultation</a>
      {'' if compact else f'<a class="link" href="contact.html?expert={key}#inquiry">Send an inquiry</a>'}
    </div>
  </div>
</article>"""


def post_card(p, idx):
    topic, label, title, dek, read = p
    img = photo(POST_PHOTO[idx], 640, 360, "post__img")
    return f"""<article class="post">
  <a class="post__link" href="blog-post.html">
    {img}
    <p class="post__topic">{label}</p>
    <h3 class="post__title">{title}</h3>
    <p class="post__dek">{dek}</p>
    <p class="post__meta">{read}</p>
  </a>
</article>"""


# ---------------------------------------------------------------- HOME
def build_home():
    topics = "".join(
        f'<li><a class="topic topic--img" href="{h}">{photo(TOPIC_PHOTO[k], 560, 360, "topic__img")}<span class="topic__name">{t}</span><span class="topic__desc">{d}</span></a></li>'
        for k, (h, t, d) in GUIDES.items()
    )
    experts = "".join(expert_card(i, e, compact=True) for i, e in enumerate(EXPERTS[:3]))
    posts = "".join(post_card(p, i) for i, p in enumerate(POSTS[:3]))
    body = f"""
<section class="hero">
  <div class="wrap hero__grid">
    <div class="hero__copy">
      <h1 class="hero__title">Practical guidance for doing business in Mexico</h1>
      <p class="hero__lede">Free, public guides on IMMEX, labor, corporate and real estate, written with the experts who handle these questions every day.</p>
      <form class="ask" role="search" data-ask>
        <label for="ask-q" class="sr-only">Ask a question about doing business in Mexico</label>
        <input id="ask-q" name="q" type="text" placeholder="Ask about IMMEX, labor, tax, land…" autocomplete="off">
        <button class="btn" type="submit">Ask</button>
      </form>
      <div class="hero__ctas">
        <a class="btn btn--ghost" href="contact.html">Book a consultation</a>
        <a class="link" href="contact.html#inquiry">Send an inquiry</a>
      </div>
    </div>
    <figure class="hero__media">
      <picture>
        <source media="(max-width: 860px)" srcset="assets/hero-reforma-wide.webp" width="646" height="484">
        <img src="assets/hero-reforma.webp" width="615" height="769" alt="Paseo de la Reforma and the Mexico City skyline at sunset" fetchpriority="high">
      </picture>
      <figcaption>Paseo de la Reforma, Mexico City</figcaption>
    </figure>
  </div>
</section>

<section class="topics">
  <div class="wrap">
    <div class="sec-head"><h2>Guides</h2><p>Start with the topic you need. Each guide is free and kept up to date by our contributors.</p></div>
    <ul class="topics__list topics__list--cards">{topics}</ul>
  </div>
</section>

<section class="pathway textured">
  <div class="wrap">
    <div class="sec-head">
      <h2>How companies set up in Mexico</h2>
      <p>Most projects go through these four stages. Each one links to the guide that covers it.</p>
    </div>
    <ol class="steps">
      <li class="step"><span class="step__n" aria-hidden="true">1</span><h3>Form the company</h3><p>Choose an entity type, incorporate before a notary and register with the tax authority.</p><a class="link" href="corporate.html">Corporate guide</a></li>
      <li class="step"><span class="step__n" aria-hidden="true">2</span><h3>Secure a site</h3><p>Lease or buy industrial space, and check title, land use and utilities first.</p><a class="link" href="real-estate.html">Real estate guide</a></li>
      <li class="step"><span class="step__n" aria-hidden="true">3</span><h3>Set up imports</h3><p>Apply for IMMEX so inputs for export production enter without import duty or VAT.</p><a class="link" href="immex.html">IMMEX guide</a></li>
      <li class="step"><span class="step__n" aria-hidden="true">4</span><h3>Hire your team</h3><p>Write compliant contracts, register with social security and plan for mandatory benefits.</p><a class="link" href="labor.html">Labor guide</a></li>
    </ol>
  </div>
</section>

<section class="home-experts">
  <div class="wrap">
    <div class="sec-head sec-head--row">
      <div><h2>Talk to an expert</h2><p>Lawyers, customs brokers and advisors who contribute to these guides.</p></div>
      <a class="btn btn--ghost" href="experts.html">See all experts</a>
    </div>
    <div class="experts-grid experts-grid--3">{experts}</div>
  </div>
</section>

<section class="home-blog">
  <div class="wrap">
    <div class="sec-head sec-head--row">
      <div><h2>Latest from the blog</h2><p>Short updates when rules change.</p></div>
      <a class="btn btn--ghost" href="blog.html">All articles</a>
    </div>
    <div class="posts-grid">{posts}</div>
  </div>
</section>

{cta_band()}
"""
    jsonld = """<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"Doing Business Mexico","url":"https://doingbusiness-mexico.com/"}</script>
"""
    jsonld += '<link rel="preload" as="image" href="assets/hero-reforma.webp" media="(min-width: 861px)">\n<link rel="preload" as="image" href="assets/hero-reforma-wide.webp" media="(max-width: 860px)">\n'
    page("index.html", SITE, "Free, public guides on IMMEX, labor, corporate and real estate in Mexico, plus a directory of experts you can consult.", None, body, jsonld)


# ---------------------------------------------------------------- GUIDES
def guide_page(slug, h1, lede, sections, faqs, expert_idx):
    filename, label, desc = GUIDES[slug]
    toc = "".join(f'<li><a href="#{sid}">{st}</a></li>' for sid, st, _ in sections)
    toc += '<li><a href="#faq">Common questions</a></li>'
    content = "".join(f'<section id="{sid}" class="prose__sec"><h2>{st}</h2>{html}</section>' for sid, st, html in sections)
    faq_html = "".join(f"<details><summary>{q}</summary><p>{a}</p></details>" for q, a in faqs)
    related = "".join(
        f'<li><a class="topic" href="{h}"><span class="topic__name">{t}</span><span class="topic__desc">{d}</span></a></li>'
        for k, (h, t, d) in GUIDES.items() if k != slug
    )
    e = EXPERTS[expert_idx]
    body = f"""
<div class="guide-hero">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="index.html">Home</a><span aria-hidden="true">/</span><span aria-current="page">{label}</span></nav>
    <h1>{h1}</h1>
    <p class="guide-hero__lede">{lede}</p>
    <p class="guide-hero__meta">Last reviewed {REVIEWED}. Written with contributors from our <a href="experts.html">experts directory</a>.</p>
  </div>
</div>
<figure class="wrap banner">
  {photo(slug, 1200, 420, "banner__img", eager=True)}
  <figcaption>{credit(slug)}</figcaption>
</figure>

<div class="wrap guide">
  <aside class="guide__toc" aria-label="On this page">
    <p class="guide__toc-h">On this page</p>
    <ol>{toc}</ol>
  </aside>

  <article class="prose">
    {content}
    <section id="faq" class="prose__sec faq"><h2>Common questions</h2>{faq_html}</section>
  </article>

  <aside class="guide__side">
    <div class="help-box">
      <h2>Get help with {label.lower() if slug != "immex" else "IMMEX"}</h2>
      <div class="help-box__expert">{initials_avatar(expert_idx + 1)}<div><p class="help-box__name">Expert name</p><p class="help-box__role">{e[2]}, {e[3].split(',')[0]}</p></div></div>
      <a class="btn" href="contact.html?expert={e[0]}">Book a consultation</a>
      <a class="btn btn--ghost" href="contact.html?expert={e[0]}#inquiry">Send an inquiry</a>
    </div>
  </aside>
</div>

<section class="related">
  <div class="wrap">
    <h2>Other guides</h2>
    <ul class="topics__list">{related}</ul>
  </div>
</section>

{cta_band()}
"""
    faq_ld = ",".join(
        '{"@type":"Question","name":%s,"acceptedAnswer":{"@type":"Answer","text":%s}}' % (jsq(q), jsq(a)) for q, a in faqs
    )
    jsonld = f"""<script type="application/ld+json">{{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{faq_ld}]}}</script>
"""
    page(filename, h1, desc, filename, body, jsonld)


def jsq(s):
    import json, re
    return json.dumps(re.sub(r"<[^>]+>", "", s))


def build_guides():
    guide_page("immex", "IMMEX program guide",
        "IMMEX lets manufacturers import raw materials, parts and machinery without paying import duty or VAT up front, as long as the finished goods are exported.",
        [
            ("what", "What IMMEX is", """<p>IMMEX (Industria Manufacturera, Maquiladora y de Servicios de Exportación) is a program run by the Ministry of Economy. It replaced the separate maquiladora and PITEX programs in 2006.</p>
<p>With an IMMEX program, a company can temporarily import goods used to manufacture, transform or repair products for export. General import duty is not paid on those goods. VAT on the import can also be offset with VAT certification from the tax authority (SAT).</p>"""),
            ("types", "Program types", """<ul>
<li><strong>Industrial:</strong> one company manufactures or transforms goods for export.</li>
<li><strong>Services:</strong> repair or service work on goods for export.</li>
<li><strong>Holding:</strong> a parent company holds the program for its group companies in Mexico.</li>
<li><strong>Shelter:</strong> a Mexican shelter company holds the program, and a foreign manufacturer operates under it without forming its own company for a limited period.</li>
<li><strong>Outsourcing:</strong> the program holder has production done by third-party manufacturers.</li>
</ul>"""),
            ("requirements", "Requirements", """<ul>
<li>A Mexican company with an active tax ID (RFC) and electronic signature (e.firma).</li>
<li>A positive tax compliance opinion from SAT.</li>
<li>Registration in the importers' registry (Padrón de Importadores).</li>
<li>Proof of an export operation, such as contracts or purchase orders with foreign customers.</li>
<li>Facilities where the operation takes place, registered with SAT.</li>
<li>An automated inventory control system that tracks imported goods (known as Annex 24).</li>
</ul>"""),
            ("time", "How long imported goods can stay", """<p>Raw materials, parts and packaging can generally stay in Mexico for up to 18 months before they must be exported, returned or changed to a definitive import. Machinery and equipment can stay for as long as the program is active.</p>"""),
            ("compliance", "Keeping the program", """<p>IMMEX holders must file an annual report of sales and exports with the Ministry of Economy, keep the inventory control system up to date, and meet the minimum export requirement for their program. Missing these obligations can lead to suspension of the program.</p>"""),
        ],
        [
            ("Do I need a Mexican company to use IMMEX?", "To hold your own program, yes. A shelter arrangement lets you operate under a shelter company's program without forming your own company for a limited period."),
            ("Is IMMEX the same as a maquiladora?", "IMMEX is the current program. It merged the former maquiladora and PITEX programs in 2006, and people still often call IMMEX companies maquiladoras."),
            ("What happens to VAT on temporary imports?", "Companies with VAT certification from SAT get a credit equal to the VAT on their temporary imports, so the tax does not tie up cash."),
        ], 0)

    guide_page("labor", "Labor and employment guide",
        "Mexico's Federal Labor Law sets mandatory benefits, contract rules and how employees can be dismissed. Here is what employers need to know before hiring.",
        [
            ("contracts", "Employment contracts", """<p>Employment relationships should be documented in writing. Contracts are indefinite by default. Fixed-term or project contracts are allowed only when the nature of the work justifies them.</p>
<p>Indefinite contracts can include a probation period of up to 30 days, or up to 180 days for management and specialized technical roles.</p>"""),
            ("benefits", "Mandatory benefits", """<ul>
<li><strong>Christmas bonus (aguinaldo):</strong> at least 15 days of salary, paid by December 20.</li>
<li><strong>Vacation:</strong> 12 days after the first year of service, increasing with seniority.</li>
<li><strong>Vacation premium:</strong> at least 25% of the salary for vacation days.</li>
<li><strong>Profit sharing (PTU):</strong> 10% of the company's taxable profit, shared among employees, with a cap per employee.</li>
<li><strong>Social security:</strong> registration with IMSS, plus housing fund (INFONAVIT) and retirement contributions.</li>
<li><strong>State payroll tax:</strong> rates vary by state.</li>
</ul>"""),
            ("outsourcing", "Outsourcing and staffing", """<p>Since the 2021 reform, companies cannot subcontract personnel to carry out their core business activity. Specialized services that are not part of the company's main activity are still allowed, but the provider must be registered in the REPSE registry.</p>"""),
            ("unions", "Unions and collective agreements", """<p>Following the 2019 labor reform, collective bargaining agreements must be approved by workers in a personal, free and secret vote. Agreements are registered with the Federal Center for Conciliation and Labor Registration. Most labor disputes must go through conciliation before reaching a labor court.</p>"""),
            ("termination", "Ending employment", """<p>An employee dismissed without justified cause is generally entitled to three months of salary, plus 20 days of salary per year of service in many cases, a seniority premium, and any accrued benefits. Justified dismissals require written notice that states the cause.</p>"""),
            ("foreign", "Hiring foreign staff", """<p>Foreign employees need a visa and residence permit that allows work, issued through the National Migration Institute (INM). The employer must first register with INM to sponsor them.</p>"""),
        ],
        [
            ("Is there a probation period in Mexico?", "Yes. Indefinite contracts can include up to 30 days of probation, or up to 180 days for managers and specialized technical roles."),
            ("Can I use a staffing agency?", "Only for specialized services outside your core business activity, and only through a provider registered in REPSE."),
            ("When is the aguinaldo paid?", "By December 20 each year. It is at least 15 days of salary, prorated for employees who worked part of the year."),
        ], 1)

    guide_page("corporate", "Corporate guide: setting up a company in Mexico",
        "Most foreign investors operate through a Mexican company. This guide covers the common entity types, the incorporation steps and what to keep up with afterwards.",
        [
            ("entities", "Common entity types", """<ul>
<li><strong>S.A. de C.V.</strong> (sociedad anónima de capital variable): a corporation with at least two shareholders. The most widely used form.</li>
<li><strong>S. de R.L. de C.V.</strong> (sociedad de responsabilidad limitada): a limited liability company with two to 50 partners. Often chosen by US parents for tax reasons.</li>
<li><strong>Branch:</strong> a registered branch of the foreign company. Less common, because the parent stays directly liable.</li>
</ul>
<p>There is no legal minimum capital for these companies. The amount is set in the bylaws.</p>"""),
            ("steps", "Incorporation steps", """<ol>
<li>Get authorization for the company name from the Ministry of Economy.</li>
<li>Sign the bylaws before a notary public or commercial broker.</li>
<li>Register the company in the Public Registry of Commerce.</li>
<li>Register with SAT to get a tax ID (RFC) and electronic signature (e.firma).</li>
<li>Register foreign shareholders in the National Registry of Foreign Investment (RNIE).</li>
<li>Open a bank account.</li>
<li>Register as an employer with social security (IMSS) and for state payroll tax before hiring.</li>
</ol>"""),
            ("foreign", "Documents from foreign shareholders", """<p>Foreign shareholders usually need apostilled and translated powers of attorney, proof of their own incorporation, and a foreign tax ID. The company also needs a legal representative in Mexico who has their own RFC.</p>"""),
            ("ongoing", "Ongoing compliance", """<ul>
<li>Monthly tax filings and electronic invoices (CFDI) for every sale.</li>
<li>An annual tax return and an annual shareholders' meeting.</li>
<li>Updated corporate books and reports to the foreign investment registry.</li>
<li>Records identifying the company's beneficial owners.</li>
<li>Transfer pricing documentation for transactions with related companies.</li>
</ul>"""),
        ],
        [
            ("How long does incorporation take?", "Usually a few weeks. Opening the bank account and completing tax registration are often the slowest steps."),
            ("Can a company have only foreign shareholders?", "In most sectors, yes. A few activities are reserved for Mexican nationals or have foreign ownership limits."),
            ("Do I need a local director?", "You need a legal representative in Mexico with a tax ID to sign filings and contracts. Directors themselves do not have to be Mexican."),
        ], 2)

    guide_page("real-estate", "Real estate guide: industrial land and buildings",
        "Most manufacturers lease space in an industrial park, and some buy land. Here is how foreign ownership works and what to check before signing.",
        [
            ("ownership", "Foreign ownership rules", """<p>Foreigners cannot directly own land in the restricted zone: within 100 km of the borders and 50 km of the coast. Within that zone, residential property can be held through a bank trust (fideicomiso). Mexican companies with foreign shareholders can own land there for non-residential use, such as a factory, after notifying the Ministry of Foreign Affairs.</p>
<p>Outside the restricted zone, Mexican companies with foreign investment can own land directly.</p>"""),
            ("leasing", "Leasing in an industrial park", """<ul>
<li>Leases are often priced in US dollars, with an annual rent increase.</li>
<li>Terms usually run from 5 to 10 years for standard buildings, longer for build-to-suit projects.</li>
<li>Many leases are net leases, where the tenant also pays property tax, insurance and maintenance.</li>
<li>Expect a security deposit, and sometimes a parent company guarantee.</li>
</ul>"""),
            ("checks", "Checks before you buy or lease", """<ul>
<li>A title search and certificate of no liens from the Public Registry of Property.</li>
<li>Land use and zoning permits that allow your activity.</li>
<li>Whether the land was ever communal (ejido) land, which needs proper regularization.</li>
<li>Electricity capacity from the utility (CFE), water supply and wastewater permits.</li>
<li>Environmental condition of the site.</li>
</ul>"""),
            ("costs", "Purchase costs and taxes", """<p>Buyers pay a real estate acquisition tax set by each state or municipality, plus notary fees and registration costs. Owners pay an annual property tax (predial).</p>"""),
        ],
        [
            ("Can my foreign company buy land in Mexico?", "Not directly in the restricted zone. A Mexican subsidiary can own land for non-residential use, including within the restricted zone after notifying the Ministry of Foreign Affairs."),
            ("Are industrial leases in pesos or dollars?", "Industrial park leases are commonly priced in US dollars. Peso leases are possible, and the currency is worth negotiating."),
            ("What is a build-to-suit lease?", "The developer builds a facility to your specifications and you lease it, usually on a longer term than a standard building."),
        ], 3)

    guide_page("trade", "International trade guide: importing and exporting",
        "What companies need to import into and export from Mexico: registrations, customs clearance, taxes on imports, and how trade agreements lower tariffs.",
        [
            ("registrations", "Registrations you need", """<ul>
<li>An active tax ID (RFC) and electronic signature (e.firma) with the tax authority (SAT).</li>
<li>Registration in the importers' registry (Padrón de Importadores), required for most commercial imports.</li>
<li>A sector registry (padrón sectorial) for certain goods, such as steel, textiles, footwear and some chemicals.</li>
<li>For a small number of goods, a sector registry for exporters as well.</li>
</ul>"""),
            ("broker", "Customs clearance and brokers", """<p>Commercial imports and exports are cleared through a customs declaration (pedimento), filed in practice by a licensed customs broker (agente aduanal). The importer authorizes the broker electronically for each shipment or for a period.</p>
<p>The importer stays responsible for what is declared: the tariff classification, the customs value and the country of origin. Errors can lead to fines and back duties, so review your broker's classifications before your first shipments.</p>"""),
            ("taxes", "Taxes and fees on imports", """<ul>
<li><strong>General import duty (IGI):</strong> set by the product's tariff code and its country of origin.</li>
<li><strong>VAT:</strong> 16%, calculated on the customs value plus duty.</li>
<li><strong>Customs processing fee (DTA)</strong> on each declaration.</li>
<li><strong>Excise tax (IEPS)</strong> on some goods, such as fuels, alcohol and tobacco.</li>
</ul>
<p>In recent years Mexico has raised import duties on many products from countries without a trade agreement with Mexico, including China. Check the current rate for your tariff code before you set prices.</p>
<p>If you import inputs to manufacture goods for export, the <a href="immex.html">IMMEX program</a> lets you defer duty and VAT on them.</p>"""),
            ("agreements", "Trade agreements", """<p>Mexico has free trade agreements covering about 50 countries, including the USMCA (known in Mexico as T-MEC) with the United States and Canada, the agreement with the European Union, and the CPTPP with Pacific countries.</p>
<p>To pay a lower preferential duty, goods must meet the agreement's rules of origin, and the importer must hold valid proof of origin. Plan your supply chain around these rules if you sell into the US or Canada.</p>"""),
            ("requirements", "Standards and permits", """<p>Many products must meet Mexican official standards (NOMs), especially for labeling, safety and energy use, before they can be sold. Some goods also need an import permit or approval from a specific authority, such as the Ministry of Economy, the health regulator (COFEPRIS) or the agriculture authority (SENASICA).</p>"""),
            ("exporting", "Exporting from Mexico", """<p>Exports are generally not subject to export duties and are zero-rated for VAT. For definitive exports, the exporter issues an electronic invoice (CFDI) with the foreign trade complement, which customs matches against the export declaration.</p>"""),
        ],
        [
            ("Do I need to register to import into Mexico?", "Yes. Most commercial imports require registration in the importers' registry with SAT, and some goods also need a sector registry."),
            ("Can I import without a customs broker?", "In practice, almost all commercial shipments are cleared through a licensed customs broker, who files the customs declaration for you."),
            ("Does Mexico have a trade agreement with China?", "No. Goods from China pay Mexico's general import duties, which have increased for many products in recent years."),
        ], 0)


# ---------------------------------------------------------------- EXPERTS
def build_experts():
    filters = '<button type="button" class="chip" aria-pressed="true" data-filter="all">All</button>' + "".join(
        f'<button type="button" class="chip" aria-pressed="false" data-filter="{k}">{v}</button>' for k, v in TOPIC_NAMES.items()
    )
    cards = "".join(expert_card(i, e) for i, e in enumerate(EXPERTS))
    body = f"""
<div class="guide-hero">
  <div class="wrap">
    <h1>Experts directory</h1>
    <p class="guide-hero__lede">Lawyers, customs specialists and advisors who contribute to our guides. Book a consultation or send an inquiry to any of them.</p>
  </div>
</div>
<section class="directory">
  <div class="wrap">
    <div class="filters" role="group" aria-label="Filter experts by topic">{filters}</div>
    <p class="filters__count" aria-live="polite"><span data-count>{len(EXPERTS)}</span> experts</p>
    <div class="experts-grid">{cards}</div>
    <p class="empty" hidden>No experts listed for this topic yet. <a href="contact.html#inquiry">Send an inquiry</a> and we'll connect you with someone.</p>
  </div>
</section>
<section class="contribute textured" id="contribute">
  <div class="wrap contribute__inner">
    <div>
      <h2>Become a contributor</h2>
      <p>If you advise companies doing business in Mexico, you can write or review guides and be listed in this directory. Contributors are reviewed before they appear.</p>
    </div>
    <a class="btn" href="contact.html?topic=contributor#inquiry">Apply to contribute</a>
  </div>
</section>
"""
    page("experts.html", "Experts directory", "Find lawyers, customs specialists and advisors for doing business in Mexico.", "experts.html", body)


# ---------------------------------------------------------------- BLOG
def build_blog():
    cats = '<button type="button" class="chip" aria-pressed="true" data-filter="all">All</button>' + "".join(
        f'<button type="button" class="chip" aria-pressed="false" data-filter="{k}">{t}</button>' for k, (_, t, _) in GUIDES.items()
    )
    feat = POSTS[0]
    posts = "".join(post_card(p, i + 1).replace('<article class="post">', f'<article class="post" data-topic="{p[0]}">') for i, p in enumerate(POSTS[1:]))
    body = f"""
<div class="guide-hero">
  <div class="wrap">
    <h1>Blog</h1>
    <p class="guide-hero__lede">Updates and explainers when rules change, from our contributors.</p>
  </div>
</div>
<section class="blog">
  <div class="wrap">
    <article class="feature">
      <a class="feature__link" href="blog-post.html">
        {photo(POST_PHOTO[0], 760, 428, "post__img feature__img", eager=True)}
        <div class="feature__body">
          <p class="post__topic">{feat[1]}</p>
          <h2 class="feature__title">{feat[2]}</h2>
          <p class="post__dek">{feat[3]}</p>
          <p class="post__meta">Expert name, IMMEX and customs · {feat[4]}</p>
        </div>
      </a>
    </article>
    <div class="filters" role="group" aria-label="Filter articles by topic">{cats}</div>
    <div class="posts-grid">{posts}</div>
    <p class="empty" hidden>No articles on this topic yet.</p>
  </div>
</section>
{cta_band()}
"""
    page("blog.html", "Blog", "Updates and explainers on IMMEX, labor, corporate and real estate rules in Mexico.", "blog.html", body)


def build_post():
    related = "".join(post_card(p, i + 1) for i, p in enumerate(POSTS[1:4]))
    body = f"""
<article class="article">
  <header class="wrap article__head">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="index.html">Home</a><span aria-hidden="true">/</span><a href="blog.html">Blog</a><span aria-hidden="true">/</span><span aria-current="page">IMMEX</span></nav>
    <p class="post__topic">IMMEX</p>
    <h1>Annual IMMEX report: what to file and when</h1>
    <p class="article__dek">Every IMMEX holder files an annual report with the Ministry of Economy. Here is what goes in it.</p>
    <div class="byline">{initials_avatar(1)}<div><p class="byline__name">Expert name</p><p class="byline__meta">IMMEX and customs · Published November 2026 · 6 min read</p></div></div>
  </header>
  <figure class="article__fig">
    {photo("docs", 1120, 480, "article__img", eager=True)}
    <figcaption class="wrap">{credit("docs")}</figcaption>
  </figure>
  <div class="wrap article__body">
    <div class="prose">
      <p>Each year, companies with an IMMEX program report their total sales and the value of their exports for the previous year to the Ministry of Economy. The report shows that the company still meets the program's requirements.</p>
      <h2>What the report includes</h2>
      <p>The report covers total annual sales and the value of exports in the previous fiscal year. It is filed online through the Ministry of Economy's systems.</p>
      <h2>When it is due</h2>
      <p>The report is due in May for the previous year. Missing it can lead to the program being suspended, which stops temporary imports.</p>
      <blockquote>Set a reminder in March. Pulling the export figures together takes longer than filing the report.</blockquote>
      <h2>After you file</h2>
      <p>Keep the acknowledgment of receipt with your IMMEX records. Customs or the Ministry may ask for it during an audit.</p>
      <p class="article__note">Read the full <a href="immex.html">IMMEX guide</a> for program types, requirements and how long imported goods can stay in Mexico.</p>
    </div>
    <aside class="author-box">
      {initials_avatar(1)}
      <div>
        <p class="author-box__label">About the author</p>
        <p class="byline__name">Expert name</p>
        <p>{EXPERTS[0][5]}</p>
        <div class="expert__actions"><a class="btn btn--small" href="contact.html?expert=ex1">Book a consultation</a><a class="link" href="contact.html?expert=ex1#inquiry">Send an inquiry</a></div>
      </div>
    </aside>
  </div>
</article>
<section class="related">
  <div class="wrap">
    <h2>Related articles</h2>
    <div class="posts-grid">{related}</div>
  </div>
</section>
{cta_band()}
"""
    jsonld = """<script type="application/ld+json">{"@context":"https://schema.org","@type":"BlogPosting","headline":"Annual IMMEX report: what to file and when","author":{"@type":"Person","name":"Expert name"}}</script>
"""
    page("blog-post.html", "Annual IMMEX report: what to file and when", "What goes in the annual IMMEX report and when it is due.", "blog.html", body, jsonld)


# ---------------------------------------------------------------- CONTACT
def build_contact():
    opts = "".join(f'<option value="{k}">{v}</option>' for k, v in TOPIC_NAMES.items())
    body = f"""
<div class="guide-hero">
  <div class="wrap">
    <h1>Book a consultation or send an inquiry</h1>
    <p class="guide-hero__lede">Book a call with an expert, or send your question and we'll reply within one business day.</p>
  </div>
</div>
<section class="contact">
  <div class="wrap contact__grid">
    <div class="contact__card" id="book">
      <h2>Book a consultation</h2>
      <p>Pick a time for a video call with an expert. Calls are in English.</p>
      <div class="calendar-slot">
        <p>The booking calendar (for example Calendly or Microsoft Bookings) is embedded here.</p>
      </div>
    </div>
    <div class="contact__card" id="inquiry">
      <h2>Send an inquiry</h2>
      <form class="form" data-inquiry novalidate>
        <div class="field"><label for="f-name">Your name</label><input id="f-name" name="name" autocomplete="name" required></div>
        <div class="field"><label for="f-email">Work email</label><input id="f-email" name="email" type="email" autocomplete="email" required></div>
        <div class="field"><label for="f-co">Company</label><input id="f-co" name="company" autocomplete="organization"></div>
        <div class="field"><label for="f-topic">Topic</label><select id="f-topic" name="topic"><option value="general">General question</option>{opts}<option value="contributor">Becoming a contributor</option></select></div>
        <div class="field field--full"><label for="f-msg">Your question</label><textarea id="f-msg" name="message" rows="5" required></textarea></div>
        <div class="form__end field--full">
          <button class="btn" type="submit">Send inquiry</button>
          <p class="form__status" role="status" aria-live="polite"></p>
        </div>
      </form>
    </div>
  </div>
</section>
"""
    page("contact.html", "Book a consultation or send an inquiry", "Book a consultation with an expert or send an inquiry about doing business in Mexico.", None, body)


if __name__ == "__main__":
    build_home()
    build_guides()
    build_experts()
    build_blog()
    build_post()
    build_contact()
    print("Built:", ", ".join(sorted(p.name for p in OUT.glob("*.html"))))
