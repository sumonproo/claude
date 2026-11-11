// AI chat panel. Loaded on demand by main.js, so it adds nothing to page load.
//
// Concept mode: answers come from a small built-in index of this site's guides.
// Production: set ENDPOINT to a server route (WordPress REST endpoint or a small
// worker) that sends the question plus the site's guide content to an AI model
// and returns { answer, links }. Keep the API key on the server, never in this file.
const ENDPOINT = "";

const KB = [
  { k: ["immex", "maquila", "temporary import", "temporary imports", "duty", "pitex"], a: "IMMEX lets manufacturers import raw materials, parts and machinery without paying import duty, as long as the finished goods are exported. VAT on those imports can be offset with VAT certification from SAT.", l: [["IMMEX guide", "immex.html"]] },
  { k: ["18 months", "how long", "stay", "inputs"], a: "Raw materials, parts and packaging imported under IMMEX can generally stay in Mexico for up to 18 months. Machinery can stay for as long as the program is active.", l: [["How long goods can stay", "immex.html#time"]] },
  { k: ["shelter", "without a company", "albergue"], a: "A shelter arrangement lets a foreign manufacturer operate under a Mexican shelter company's IMMEX program without forming its own company, for a limited period.", l: [["IMMEX program types", "immex.html#types"]] },
  { k: ["annual report", "report"], a: "IMMEX holders file an annual report of sales and exports with the Ministry of Economy, due in May for the previous year.", l: [["Annual IMMEX report", "blog-post.html"]] },
  { k: ["benefit", "aguinaldo", "vacation", "bonus", "ptu", "profit sharing"], a: "Mandatory benefits include a Christmas bonus of at least 15 days' salary, 12 vacation days after the first year with a 25% premium, profit sharing of 10% of taxable profit, and social security contributions.", l: [["Mandatory benefits", "labor.html#benefits"]] },
  { k: ["outsourc", "staffing", "subcontract", "repse"], a: "Since 2021, companies cannot subcontract personnel for their core activity. Specialized services are allowed if the provider is registered in REPSE.", l: [["Outsourcing rules", "labor.html#outsourcing"]] },
  { k: ["union", "collective"], a: "Collective agreements must be approved by workers in a personal, free and secret vote, and registered with the Federal Center for Conciliation and Labor Registration.", l: [["Unions", "labor.html#unions"]] },
  { k: ["fire", "dismiss", "severance", "terminat"], a: "An employee dismissed without justified cause is generally owed three months' salary, often 20 days per year of service, a seniority premium and accrued benefits.", l: [["Ending employment", "labor.html#termination"]] },
  { k: ["visa", "foreign staff", "foreign employee", "work permit"], a: "Foreign staff need a visa and residence permit that allows work, through the National Migration Institute. The employer registers with INM first.", l: [["Hiring foreign staff", "labor.html#foreign"]] },
  { k: ["incorporat", "set up a company", "form a company", "company", "s.a.", "s. de r.l", "entity", "subsidiary"], a: "Most foreign investors use an S.A. de C.V. or an S. de R.L. de C.V. Setup involves name approval, bylaws before a notary, commercial registry, tax registration (RFC) and the foreign investment registry.", l: [["Corporate guide", "corporate.html"], ["Incorporation steps", "corporate.html#steps"]] },
  { k: ["tax", "rfc", "cfdi", "invoice"], a: "Companies register with SAT for a tax ID (RFC), issue electronic invoices (CFDI) for every sale, file monthly and file an annual return.", l: [["Ongoing compliance", "corporate.html#ongoing"]] },
  { k: ["land", "buy", "own", "restricted zone", "fideicomiso", "property"], a: "Foreigners cannot directly own land within 100 km of the borders or 50 km of the coast. A Mexican subsidiary can own land there for non-residential use after notifying the Ministry of Foreign Affairs.", l: [["Foreign ownership rules", "real-estate.html#ownership"]] },
  { k: ["lease", "industrial park", "rent", "warehouse", "build-to-suit"], a: "Industrial park leases are often in US dollars, run 5 to 10 years with annual increases, and are usually net leases where the tenant also pays tax, insurance and maintenance.", l: [["Leasing", "real-estate.html#leasing"]] },
  { k: ["expert", "lawyer", "consult", "advisor", "talk", "help"], a: "You can book a consultation or send an inquiry to one of our contributing experts.", l: [["Experts directory", "experts.html"]] },
];

const GREETING = "Ask about IMMEX, labor, corporate or real estate in Mexico. I answer from this site's guides.";
const FALLBACK = "I couldn't find that in our guides. An expert can answer it directly.";

let panel, log, input, launcher;

function css() {
  const s = document.createElement("style");
  s.textContent = `
.chat{position:fixed;right:20px;bottom:20px;z-index:70;width:min(380px,calc(100vw - 32px));height:min(560px,calc(100dvh - 40px));display:flex;flex-direction:column;background:#fff;border:1px solid var(--stone);border-radius:14px;box-shadow:0 18px 48px rgba(23,35,43,.22);overflow:hidden;font-size:.9375rem}
.chat__head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--ink);color:#fff}
.chat__head h2{font-size:1rem;font-variation-settings:"wdth" 100}
.chat__close{width:40px;height:40px;border:0;background:transparent;color:#fff;font-size:1.5rem;cursor:pointer;border-radius:6px}
.chat__log{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;background:var(--paper)}
.msg{max-width:88%;padding:10px 14px;border-radius:12px;line-height:1.5}
.msg--bot{background:#fff;border:1px solid var(--stone);align-self:flex-start}
.msg--me{background:var(--jade);color:#fff;align-self:flex-end}
.msg__links{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.msg__links a{font-weight:600;font-size:.875rem}
.msg__cta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.chat__form{display:flex;gap:8px;padding:12px;border-top:1px solid var(--stone)}
.chat__form input{flex:1;min-width:0;min-height:44px;border:1px solid #C9D2CD;border-radius:8px;padding:0 12px;font:inherit}
.chat__form input:focus{outline:2px solid var(--jade);border-color:transparent}
.chat__note{padding:0 12px 10px;font-size:.75rem;color:var(--slate)}
@media (max-width:480px){.chat{right:0;bottom:0;width:100vw;height:100dvh;border-radius:0}}`;
  document.head.appendChild(s);
}

function el(tag, cls, html) { const n = document.createElement(tag); if (cls) n.className = cls; if (html != null) n.innerHTML = html; return n; }
const esc = s => s.replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));

function say(text, who = "bot", links = [], cta = false) {
  const m = el("div", `msg msg--${who}`);
  m.appendChild(el("p", "", esc(text)));
  if (links.length) m.appendChild(el("div", "msg__links", links.map(([t, h]) => `<a href="${h}">${esc(t)}</a>`).join("")));
  if (cta) m.appendChild(el("div", "msg__cta", `<a class="btn btn--small" href="contact.html">Book a consultation</a><a class="btn btn--small btn--ghost" href="contact.html#inquiry">Send an inquiry</a>`));
  log.appendChild(m);
  log.scrollTop = log.scrollHeight;
}

function localAnswer(q) {
  const t = q.toLowerCase();
  let best = null, score = 0;
  for (const item of KB) {
    const s = item.k.reduce((n, k) => n + (t.includes(k) ? k.length : 0), 0);
    if (s > score) { score = s; best = item; }
  }
  return best ? { answer: best.a, links: best.l } : null;
}

async function ask(q) {
  say(q, "me");
  let res = null;
  if (ENDPOINT) {
    try {
      const r = await fetch(ENDPOINT, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ question: q, page: location.pathname }) });
      if (r.ok) res = await r.json();
    } catch (e) { /* fall back to the local index */ }
  }
  res = res || localAnswer(q);
  if (res) say(res.answer, "bot", res.links || [], true);
  else say(FALLBACK, "bot", [], true);
}

function build() {
  css();
  panel = el("div", "chat");
  panel.setAttribute("role", "dialog");
  panel.setAttribute("aria-label", "Ask a question");
  panel.innerHTML = `<div class="chat__head"><h2>Ask a question</h2><button class="chat__close" type="button" aria-label="Close chat">×</button></div>
<div class="chat__log" aria-live="polite"></div>
<form class="chat__form"><label class="sr-only" for="chat-q">Your question</label><input id="chat-q" autocomplete="off" placeholder="Type your question"><button class="btn btn--small" type="submit">Send</button></form>
<p class="chat__note">Answers are general information from our guides, not legal advice.</p>`;
  document.body.appendChild(panel);
  log = panel.querySelector(".chat__log");
  input = panel.querySelector("input");
  panel.querySelector(".chat__close").addEventListener("click", close);
  panel.addEventListener("keydown", e => { if (e.key === "Escape") close(); });
  panel.querySelector("form").addEventListener("submit", e => {
    e.preventDefault();
    const q = input.value.trim();
    if (q) { ask(q); input.value = ""; }
  });
  say(GREETING);
}

function close() {
  panel.hidden = true;
  launcher.setAttribute("aria-expanded", "false");
  launcher.focus();
}

export function open(btn, question) {
  launcher = btn;
  if (!panel) build();
  panel.hidden = false;
  launcher.setAttribute("aria-expanded", "true");
  if (question) ask(question);
  input.focus();
}
