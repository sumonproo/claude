/* =========================================================
   Long Haul Brewing — site script
   Edit CONFIG below to change details without touching the page.
   ========================================================= */

const CONFIG = {
  phone: '(555) 018-2716',
  phoneHref: '+15550182716',
  timeZone: 'America/New_York',      // Asheville time, used for "open now"
  openHour: 15,                      // 3 pm
  closeHour: null,                   // PLACEHOLDER: e.g. 22 for 10 pm. Leave null until you know.
  openDays: [0, 2, 3, 4, 5, 6],      // Sun=0 … Sat=6. Tue–Sun.
  tourPrice: 18,
  bookingUrl: '',                    // PLACEHOLDER: link to your booking system (e.g. a Square or Eventbrite page)
  signupUrl: '',                     // PLACEHOLDER: your email-list signup form address (e.g. Mailchimp)
  // PLACEHOLDER: tonight's food truck. Fill in a name for each night.
  foodTrucks: { 2: '', 3: '', 4: '', 5: '', 6: '', 0: '' },
};

// The beers in cans. Inks: a = first ink, b = second ink.
const BEERS = [
  { id: 'hazy', name: ['HAZY', 'IPA'], title: 'Hazy IPA', style: 'Hazy IPA', abv: '6.8%',
    note: 'Cloudy, soft and full of hops.', bg: '#FFE800', a: '#FF48B0', b: '#FF48B0', txt: '#FF48B0', knock: '#FFE800', motif: 'sun' },
  { id: 'porter', name: ['SMOKED', 'PORTER'], title: 'Smoked Porter', style: 'Smoked Porter', abv: '7.4%',
    note: 'Dark and roasty, with smoke from the malt.', bg: '#0078BF', a: '#1A1A1A', b: '#1A1A1A', txt: '#F3EEE2', knock: '#0078BF', motif: 'smoke' },
  { id: 'pils', name: ['PILS', 'NER'], title: 'Pilsner', style: 'Pilsner', abv: '4.9%',
    note: 'Crisp, pale and clean.', bg: '#F3EEE2', a: '#FFE800', b: '#0078BF', txt: '#0078BF', knock: '#F3EEE2', motif: 'ridge' },
  { id: 'can4', name: ['CAN', 'NO. 4'], title: '[Beer name]', style: '[Style]', abv: '[x.x%]', ph: true,
    note: '[Your fourth canned beer: name, style, ABV and a line about it.]', bg: '#F3EEE2', a: '#FF48B0', b: '#0078BF', txt: '#0078BF', knock: '#F3EEE2', motif: 'sun' },
  { id: 'can5', name: ['CAN', 'NO. 5'], title: '[Beer name]', style: '[Style]', abv: '[x.x%]', ph: true,
    note: '[Your fifth canned beer: name, style, ABV and a line about it.]', bg: '#FF48B0', a: '#0078BF', b: '#0078BF', txt: '#1A2477', knock: '#FF48B0', motif: 'smoke' },
  { id: 'can6', name: ['CAN', 'NO. 6'], title: '[Beer name]', style: '[Style]', abv: '[x.x%]', ph: true,
    note: '[Your sixth canned beer: name, style, ABV and a line about it.]', bg: '#F3EEE2', a: '#FFE800', b: '#FF48B0', txt: '#FF48B0', knock: '#F3EEE2', motif: 'ridge' },
];

// PLACEHOLDER stockists. x/y are positions on the illustrated map (600 × 480).
const STOCKISTS = [
  { name: '[Stockist name]', where: '[Address or neighborhood]', beers: ['hazy', 'pils'], x: 190, y: 120 },
  { name: '[Stockist name]', where: '[Address or neighborhood]', beers: ['hazy', 'porter', 'pils'], x: 470, y: 110 },
  { name: '[Stockist name]', where: '[Address or neighborhood]', beers: ['porter'], x: 520, y: 260 },
  { name: '[Stockist name]', where: '[Address or neighborhood]', beers: ['pils'], x: 300, y: 370 },
  { name: '[Stockist name]', where: '[Address or neighborhood]', beers: ['hazy', 'porter'], x: 90, y: 300 },
  { name: '[Stockist name]', where: '[Address or neighborhood]', beers: ['hazy'], x: 420, y: 400 },
];

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

/* ---------------------------------------------------------
   Two-ink headlines: copy the text into the print layers
   --------------------------------------------------------- */
function inkHeadline(el) { el.setAttribute('data-text', el.innerText.replace(/\s*\n\s*/g, '\n')); }
function inkAll() { $$('.riso').forEach(inkHeadline); }

/* ---------------------------------------------------------
   Tallboy can, drawn as SVG
   --------------------------------------------------------- */
let canSeq = 0;
const W = 112;          // visible label width = one panel
const STRIP = W * 2;    // full wrap-around label
const REST = -W / 2;    // at rest the can shows its side

function nameSize(lines) {
  const longest = Math.max(...lines.map((l) => l.length));
  return Math.min(34, 92 / (longest * 0.8));
}

function halftoneDisc(cx, cy, r, color, step = 6.2) {
  let out = '';
  for (let y = -r; y <= r; y += step) {
    for (let x = -r; x <= r; x += step) {
      const ox = x + ((Math.round((y + r) / step) % 2) * step) / 2;
      const d = Math.hypot(ox, y);
      if (d > r) continue;
      const t = (y + r) / (2 * r);              // bigger dots toward the bottom
      const rad = (0.9 + t * 2.1) * (1 - Math.pow(d / r, 6) * 0.4);
      out += `<circle cx="${(cx + ox).toFixed(1)}" cy="${(cy + y).toFixed(1)}" r="${rad.toFixed(2)}"/>`;
    }
  }
  return `<g fill="${color}">${out}</g>`;
}

function motif(beer, cx, cy) {
  if (beer.motif === 'sun') {
    return `<g class="lab-a" style="mix-blend-mode:multiply">${halftoneDisc(cx, cy, 30, beer.a)}</g>
      <path d="M${cx - 50} ${cy + 26} L${cx - 22} ${cy + 4} L${cx - 6} ${cy + 16} L${cx + 16} ${cy - 4} L${cx + 50} ${cy + 26} Z" fill="${beer.b}" style="mix-blend-mode:multiply"/>`;
  }
  if (beer.motif === 'smoke') {
    return `<g fill="none" stroke="${beer.a}" stroke-width="4.5" stroke-linecap="round" style="mix-blend-mode:multiply">
        <path d="M${cx - 14} ${cy + 30} C${cx - 30} ${cy + 10} ${cx + 4} ${cy} ${cx - 10} ${cy - 18} S${cx + 2} ${cy - 40} ${cx - 6} ${cy - 46}"/>
        <path d="M${cx + 10} ${cy + 30} C${cx - 4} ${cy + 12} ${cx + 26} ${cy + 2} ${cx + 12} ${cy - 16} S${cx + 22} ${cy - 34} ${cx + 16} ${cy - 40}"/>
      </g>
      <rect x="${cx - 26}" y="${cy + 28}" width="52" height="8" fill="${beer.txt}"/>`;
  }
  // ridge
  return `<circle cx="${cx}" cy="${cy - 4}" r="28" fill="${beer.a}" style="mix-blend-mode:multiply"/>
    <path d="M${cx - 56} ${cy + 30} L${cx - 30} ${cy + 2} L${cx - 14} ${cy + 14} L${cx + 10} ${cy - 12} L${cx + 56} ${cy + 30} Z" fill="${beer.b}" style="mix-blend-mode:multiply"/>
    <path d="M${cx - 56} ${cy + 30} L${cx - 30} ${cy + 2} L${cx - 14} ${cy + 14} L${cx + 10} ${cy - 12} L${cx + 56} ${cy + 30}" fill="none" stroke="${beer.b}" stroke-width="2"/>`;
}

function labelPanel(beer) {
  const fs = nameSize(beer.name);
  const cx = W / 2;
  const nameY1 = 76 + fs * 0.9;
  const nameY2 = nameY1 + fs * 0.98;
  // front panel (0 … W), back panel (W … 2W)
  const front = `
    <rect x="0" y="40" width="${STRIP}" height="248" fill="${beer.bg}"/>
    <rect x="0" y="44" width="${STRIP}" height="9" fill="${beer.b}" style="mix-blend-mode:multiply"/>
    <rect x="0" y="273" width="${STRIP}" height="9" fill="${beer.b}" style="mix-blend-mode:multiply"/>
    <text x="${cx}" y="70" text-anchor="middle" font-family="Work Sans, sans-serif" font-weight="700" font-size="6.6" letter-spacing="2.4" fill="${beer.txt}">LONG HAUL</text>
    <g font-family="Bowlby One, sans-serif" font-size="${fs.toFixed(1)}" text-anchor="middle" fill="${beer.txt}"${beer.txt === '#F3EEE2' ? '' : ' style="mix-blend-mode:multiply"'}>
      <text x="${cx}" y="${nameY1.toFixed(1)}">${esc(beer.name[0])}</text>
      <text x="${cx}" y="${nameY2.toFixed(1)}">${esc(beer.name[1])}</text>
    </g>
    ${motif(beer, cx, 186)}
    <text x="${cx}" y="238" text-anchor="middle" font-family="Work Sans, sans-serif" font-weight="700" font-size="6.4" letter-spacing="1.6" fill="${beer.txt}">${esc(beer.ph ? 'YOUR BEER HERE' : beer.style.toUpperCase())}</text>
    <rect x="${cx - 26}" y="245" width="52" height="17" fill="${beer.txt}"/>
    <text x="${cx}" y="257.5" text-anchor="middle" font-family="Bowlby One, sans-serif" font-size="9.5" fill="${beer.knock}">${esc(beer.ph ? 'ABV' : beer.abv)}</text>`;
  const bx = W;
  let bars = '';
  const pattern = [2, 1, 1, 3, 1, 2, 1, 1, 2, 3, 1, 1, 2, 1, 3, 1, 2, 1];
  let x = bx + 30;
  pattern.forEach((w, i) => { if (i % 2 === 0) bars += `<rect x="${x}" y="222" width="${w}" height="26" fill="${beer.txt}"/>`; x += w + 1; });
  const back = `
    <rect x="${bx + 8}" y="53" width="14" height="220" fill="${beer.a}" style="mix-blend-mode:multiply"/>
    <text transform="translate(${bx + 19} 262) rotate(-90)" font-family="Bowlby One, sans-serif" font-size="11" fill="${beer.knock}">${esc(beer.name.join(' '))}</text>
    <g font-family="Work Sans, sans-serif" font-weight="700" fill="${beer.txt}" letter-spacing="1.2">
      <text x="${bx + 30}" y="80" font-size="5.2" letter-spacing=".8">BREWED &amp; CANNED BY</text>
      <text x="${bx + 30}" y="89" font-size="5.2" letter-spacing=".8">LONG HAUL BREWING</text>
      <text x="${bx + 30}" y="98" font-size="5.2" letter-spacing=".8">ASHEVILLE, NC</text>
      <text x="${bx + 30}" y="126" font-size="9" font-family="Bowlby One, sans-serif" letter-spacing="0">${esc(beer.ph ? 'STYLE' : beer.style.toUpperCase())}</text>
      <text x="${bx + 30}" y="140" font-size="9" font-family="Bowlby One, sans-serif" letter-spacing="0">${esc(beer.ph ? 'ABV' : beer.abv + ' ABV')}</text>
    </g>
    <g fill="${beer.txt}" opacity=".85">
      <rect x="${bx + 30}" y="156" width="66" height="2.4"/><rect x="${bx + 30}" y="163" width="58" height="2.4"/>
      <rect x="${bx + 30}" y="170" width="70" height="2.4"/><rect x="${bx + 30}" y="177" width="44" height="2.4"/>
      <rect x="${bx + 30}" y="190" width="62" height="2.4"/><rect x="${bx + 30}" y="197" width="52" height="2.4"/>
    </g>
    ${bars}
    <text x="${bx + 30}" y="262" font-family="Work Sans, sans-serif" font-weight="700" font-size="5.6" letter-spacing="1.6" fill="${beer.txt}">TALLBOY</text>`;
  return front + back;
}

function canSVG(beer, { front = false } = {}) {
  const id = `c${++canSeq}`;
  const body = 'M14 22 L106 22 C106 30 116 32 116 42 L116 284 C116 293 108 297 106 302 A46 5 0 0 1 14 302 C12 297 4 293 4 284 L4 42 C4 32 14 30 14 22 Z';
  return `
<svg viewBox="0 8 120 304" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="${id}m" x1="0" x2="1">
      <stop offset="0" stop-color="#5d6167"/><stop offset=".12" stop-color="#b9bcc1"/><stop offset=".26" stop-color="#f6f7f8"/>
      <stop offset=".4" stop-color="#c6c9cd"/><stop offset=".7" stop-color="#8b8f95"/><stop offset=".9" stop-color="#5a5e64"/><stop offset="1" stop-color="#3e4146"/>
    </linearGradient>
    <linearGradient id="${id}s" x1="0" x2="1">
      <stop offset="0" stop-color="#000" stop-opacity=".55"/><stop offset=".14" stop-color="#000" stop-opacity=".16"/>
      <stop offset=".34" stop-color="#000" stop-opacity="0"/><stop offset=".64" stop-color="#000" stop-opacity="0"/>
      <stop offset=".86" stop-color="#000" stop-opacity=".26"/><stop offset="1" stop-color="#000" stop-opacity=".6"/>
    </linearGradient>
    <linearGradient id="${id}h" x1="0" x2="1">
      <stop offset=".16" stop-color="#fff" stop-opacity="0"/><stop offset=".25" stop-color="#fff" stop-opacity=".62"/>
      <stop offset=".3" stop-color="#fff" stop-opacity=".2"/><stop offset=".36" stop-color="#fff" stop-opacity="0"/>
      <stop offset=".74" stop-color="#fff" stop-opacity="0"/><stop offset=".78" stop-color="#fff" stop-opacity=".22"/><stop offset=".82" stop-color="#fff" stop-opacity="0"/>
    </linearGradient>
    <radialGradient id="${id}l" cx=".5" cy=".5" r=".6">
      <stop offset="0" stop-color="#d9dbde"/><stop offset=".7" stop-color="#a7abb0"/><stop offset="1" stop-color="#70747a"/>
    </radialGradient>
    <clipPath id="${id}c"><path d="${body}"/></clipPath>
    <g id="${id}p">${labelPanel(beer)}</g>
  </defs>
  <path d="${body}" fill="url(#${id}m)"/>
  <g clip-path="url(#${id}c)">
    <g transform="translate(4 0)">
      <g class="can-wrap" style="transform:translateX(${front ? 0 : REST}px)">
        <use href="#${id}p"/><use href="#${id}p" x="${STRIP}"/><use href="#${id}p" x="${-STRIP}"/>
      </g>
    </g>
    <rect x="0" y="0" width="120" height="320" fill="url(#${id}s)"/>
    <rect x="0" y="0" width="120" height="320" fill="url(#${id}h)"/>
    <path d="M4 40 H116 M4 286 H116" stroke="#000" stroke-opacity=".25" stroke-width="1.2"/>
    <path d="M4 43 H116" stroke="#fff" stroke-opacity=".35" stroke-width=".8"/>
  </g>
  <!-- lid, rim and tab -->
  <ellipse cx="60" cy="22" rx="46.5" ry="6.6" fill="url(#${id}m)" stroke="#3e4146" stroke-width=".7"/>
  <ellipse cx="60" cy="22.4" rx="41.5" ry="5.1" fill="url(#${id}l)"/>
  <ellipse cx="60" cy="21.6" rx="41.5" ry="5.1" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width=".7"/>
  <path d="M46 21.2 C46 18.6 52 17.6 60 17.6 C67.5 17.6 73 18.6 73 21 C73 23.6 67 25 60 25 C52 25 46 23.8 46 21.2 Z" fill="#c9ccd0" stroke="#55595f" stroke-width=".7"/>
  <ellipse cx="54" cy="21.3" rx="4.6" ry="2.1" fill="#55595f"/>
  <circle cx="62" cy="21.3" r="1.5" fill="#8b8f95" stroke="#4a4d52" stroke-width=".5"/>
  <path d="M64 19.4 C67 19.4 70 20.2 70 21.4 C70 22.4 67.5 23.1 65 23.2" fill="none" stroke="#7b7f85" stroke-width=".9"/>
  <ellipse cx="60" cy="22" rx="46.5" ry="6.6" fill="none" stroke="#fff" stroke-opacity=".4" stroke-width=".5" stroke-dasharray="40 200" stroke-dashoffset="-10"/>
</svg>`;
}

/* ---------------------------------------------------------
   Hop: up, quarter turn to the front label, land with a squash
   --------------------------------------------------------- */
function hop(canBody, wrap, shadow) {
  if (reduceMotion.matches) {
    wrap.style.transform = 'translateX(0px)';
    return;
  }
  canBody.getAnimations().forEach((a) => a.cancel());
  canBody.animate([
    { transform: 'translateY(0) scale(1, 1)', offset: 0 },
    { transform: 'translateY(0) scale(1.07, .9)', offset: .14 },
    { transform: 'translateY(-34%) scale(.96, 1.05)', offset: .48, easing: 'cubic-bezier(.5,0,.8,.4)' },
    { transform: 'translateY(0) scale(1.12, .86)', offset: .74 },
    { transform: 'translateY(-3%) scale(.97, 1.03)', offset: .86 },
    { transform: 'translateY(0) scale(1, 1)', offset: 1 },
  ], { duration: 900, easing: 'cubic-bezier(.3,.7,.4,1)' });
  wrap.getAnimations().forEach((a) => a.cancel());
  const from = getComputedStyle(wrap).transform;
  wrap.animate([
    { transform: from === 'none' ? `translateX(${REST}px)` : from, offset: 0 },
    { transform: from === 'none' ? `translateX(${REST}px)` : from, offset: .18 },
    { transform: 'translateX(0px)', offset: .7 },
  ], { duration: 900, easing: 'cubic-bezier(.45,0,.3,1)', fill: 'none' });
  wrap.style.transform = 'translateX(0px)';
  if (shadow) {
    shadow.animate([
      { transform: 'scale(1)', opacity: 1 },
      { transform: 'scale(.55)', opacity: .45, offset: .48 },
      { transform: 'scale(1.15)', opacity: 1, offset: .74 },
      { transform: 'scale(1)', opacity: 1 },
    ], { duration: 900 });
  }
}

function turnBack(wrap) {
  if (reduceMotion.matches) { wrap.style.transform = `translateX(${REST}px)`; return; }
  const from = getComputedStyle(wrap).transform;
  wrap.getAnimations().forEach((a) => a.cancel());
  wrap.animate([{ transform: from === 'none' ? 'translateX(0px)' : from }, { transform: `translateX(${REST}px)` }],
    { duration: 600, easing: 'cubic-bezier(.45,0,.3,1)' });
  wrap.style.transform = `translateX(${REST}px)`;
}

/* ---------------------------------------------------------
   Tap list: pinned, cans take turns
   --------------------------------------------------------- */
function setupTaps() {
  const holder = $('#cans');
  const section = $('#beers');
  $('#tapTotal').textContent = String(BEERS.length).padStart(2, '0');
  holder.innerHTML = BEERS.map((b, i) => `
    <button class="can-btn" type="button" data-i="${i}" aria-label="${esc(b.ph ? `Can ${i + 1}, placeholder` : `${b.title}, ${b.abv}`)}" aria-pressed="false">
      <span class="can-body">${canSVG(b)}</span><span class="can-shadow"></span>
    </button>`).join('');
  const btns = $$('.can-btn', holder);
  let current = -1;

  function show(i) {
    if (i === current) return;
    const prev = btns[current];
    if (prev) {
      prev.classList.remove('is-active'); prev.setAttribute('aria-pressed', 'false');
      turnBack($('.can-wrap', prev));
    }
    current = i;
    const b = BEERS[i];
    const btn = btns[i];
    btn.classList.add('is-active'); btn.setAttribute('aria-pressed', 'true');
    hop($('.can-body', btn), $('.can-wrap', btn), $('.can-shadow', btn));

    const d = $('#tapDetail');
    d.classList.add('swap');
    $('#tapNum').textContent = String(i + 1).padStart(2, '0');
    const nameEl = $('#tapName');
    nameEl.textContent = b.title;
    inkHeadline(nameEl);
    $('#tapStyle').innerHTML = b.ph ? `<span class="ph">${esc(b.style)}</span>` : esc(b.style);
    $('#tapAbv').innerHTML = b.ph ? `<span class="ph">${esc(b.abv)}</span>` : esc(b.abv);
    $('#tapNote').innerHTML = b.ph ? `<span class="ph">${esc(b.note)}</span>` : esc(b.note);
    void d.offsetWidth;
    requestAnimationFrame(() => d.classList.remove('swap'));
  }

  function fromScroll() {
    const r = section.getBoundingClientRect();
    const span = r.height - window.innerHeight;
    const p = Math.min(.9999, Math.max(0, -r.top / span));
    show(Math.floor(p * BEERS.length));
  }

  btns.forEach((btn, i) => btn.addEventListener('click', () => {
    const top = section.getBoundingClientRect().top + window.scrollY;
    const span = section.offsetHeight - window.innerHeight;
    window.scrollTo({ top: top + span * ((i + .5) / BEERS.length), behavior: reduceMotion.matches ? 'auto' : 'smooth' });
    show(i);
  }));

  show(0);
  return fromScroll;
}

/* ---------------------------------------------------------
   Hero: sunset dots + the big can
   --------------------------------------------------------- */
function setupHero() {
  const g = $('#sunDots');
  const cx = 500, cy = 400, r = 270, step = 13;
  const ang = 15 * Math.PI / 180;
  let out = '';
  for (let y = -r - step; y <= r + step; y += step) {
    for (let x = -r - step; x <= r + step; x += step) {
      // rotated halftone grid
      const px = x * Math.cos(ang) - y * Math.sin(ang);
      const py = x * Math.sin(ang) + y * Math.cos(ang);
      const d = Math.hypot(px, py);
      if (d > r - 2) continue;
      const t = (py + r) / (2 * r);
      const rad = Math.max(0, (t * t) * 7.6 - .4);
      if (rad < .5) continue;
      out += `<circle cx="${(cx + px).toFixed(1)}" cy="${(cy + py).toFixed(1)}" r="${rad.toFixed(2)}"/>`;
    }
  }
  g.innerHTML = `<g fill="#FF48B0">${out}</g>`;
  const heroCan = $('#heroCan');
  heroCan.insertAdjacentHTML('afterbegin', canSVG(BEERS[0], { front: true }));
}

/* ---------------------------------------------------------
   Open-now note + hours + food truck
   --------------------------------------------------------- */
function nowThere() {
  const parts = new Intl.DateTimeFormat('en-US', { timeZone: CONFIG.timeZone, weekday: 'short', hour: 'numeric', hourCycle: 'h23' }).formatToParts(new Date());
  const wd = parts.find((p) => p.type === 'weekday').value;
  const hour = parseInt(parts.find((p) => p.type === 'hour').value, 10);
  return { day: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(wd), hour };
}
const DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const DAY_LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function setupOpen() {
  const note = $('#openNote');
  const text = $('.open-text', note);
  const { day, hour } = nowThere();
  const openToday = CONFIG.openDays.includes(day);
  const closed = CONFIG.closeHour != null && hour >= CONFIG.closeHour;
  let msg, short, open = false;
  if (openToday && hour >= CONFIG.openHour && !closed) { msg = 'Open now · taps on since 3 pm'; short = 'Open now'; open = true; }
  else if (openToday && hour < CONFIG.openHour) { msg = 'Opens today at 3 pm'; short = 'Opens 3 pm'; }
  else {
    let n = (day + 1) % 7; while (!CONFIG.openDays.includes(n)) n = (n + 1) % 7;
    const when = n === (day + 1) % 7 ? 'tomorrow' : DAY[n];
    msg = `Closed · back ${when} at 3 pm`;
    short = `Closed · ${DAY[n]} 3 pm`;
  }
  text.textContent = msg;
  $('.open-short', note).textContent = short;
  note.classList.toggle('is-open', open);

  const today = $(`#hoursList li[data-day="${day}"]`);
  if (today) today.classList.add('today');

  // food truck week, Tue → Sun
  const order = [2, 3, 4, 5, 6, 0];
  $('#truckWeek').innerHTML = order.map((d) => {
    const name = CONFIG.foodTrucks[d];
    return `<li class="${d === day ? 'tonight' : ''}"><b>${DAY[d]}</b>${name ? esc(name) : '<span class="ph">[truck]</span>'}</li>`;
  }).join('');
  const tonight = $('#truckTonight');
  if (!openToday) tonight.innerHTML = `Closed tonight. Trucks back ${DAY_LONG[order.find((d) => d > day) ?? 2]}.`;
  else {
    const name = CONFIG.foodTrucks[day];
    tonight.innerHTML = `Tonight: ${name ? esc(name) : '<span class="ph">[Add tonight\'s food truck in main.js]</span>'}`;
  }
}

/* ---------------------------------------------------------
   Tour route draws itself as you scroll
   --------------------------------------------------------- */
function setupRoute() {
  const path = $('#route');
  const walker = $('#walker');
  const stations = $$('#stations g');
  const len = path.getTotalLength();
  path.style.strokeDasharray = `${len}`;
  return function update() {
    let p;
    if (reduceMotion.matches) p = 1;
    else {
      const vh = window.innerHeight;
      const sec = $('#tours').getBoundingClientRect();
      p = (vh * 0.65 - sec.top) / (sec.height * 0.7);
      p = Math.min(1, Math.max(0, p));
    }
    path.style.strokeDashoffset = `${len * (1 - p)}`;
    const pt = path.getPointAtLength(len * p);
    walker.setAttribute('cx', pt.x); walker.setAttribute('cy', pt.y);
    stations.forEach((s) => s.classList.toggle('on', p >= parseFloat(s.dataset.at) - .001));
  };
}

/* ---------------------------------------------------------
   Ticket form
   --------------------------------------------------------- */
function nextSaturday() {
  const d = new Date();
  d.setDate(d.getDate() + ((6 - d.getDay() + 7) % 7));
  return d;
}
const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

function setupTicket() {
  const form = $('#book');
  const date = $('#tDate');
  const guests = $('#tGuests');
  const msg = $('#ticketMsg');
  const sat = nextSaturday();
  date.min = iso(new Date());
  date.value = iso(sat);
  date.step = 7;

  const total = () => {
    const n = Math.min(20, Math.max(1, parseInt(guests.value, 10) || 1));
    $('#tTotal').textContent = `$${n * CONFIG.tourPrice}`;
    $('#tCalc').textContent = `${n} × $${CONFIG.tourPrice}`;
    return n;
  };
  guests.addEventListener('input', total);
  $$('[data-step]', form).forEach((b) => b.addEventListener('click', () => {
    guests.value = Math.min(20, Math.max(1, (parseInt(guests.value, 10) || 1) + parseInt(b.dataset.step, 10)));
    total();
  }));
  total();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    let ok = true;
    const set = (el, bad) => { el.setAttribute('aria-invalid', bad ? 'true' : 'false'); if (bad) ok = false; };
    const d = date.value ? new Date(date.value + 'T12:00:00') : null;
    set(date, !d || d.getDay() !== 6);
    set($('#tName'), !$('#tName').value.trim());
    set($('#tContact'), !$('#tContact').value.trim());
    if (!ok) {
      msg.textContent = !d || d.getDay() !== 6 ? 'Pick a Saturday — that\'s tour day.' : 'Add your name and a way to reach you.';
      return;
    }
    const n = total();
    const time = form.querySelector('input[name="time"]:checked').value;
    const when = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    if (CONFIG.bookingUrl) {
      window.location.href = CONFIG.bookingUrl;
      return;
    }
    msg.innerHTML = `${esc(when)}, ${esc(time)}, ${n} ${n === 1 ? 'guest' : 'guests'}. To lock it in, call <a href="tel:${CONFIG.phoneHref}">${CONFIG.phone}</a>. <span class="ph">[Online booking not connected yet — see notes]</span>`;
  });
}

function setupSignup() {
  const form = $('#signup');
  const input = $('#sEmail');
  const msg = $('#signupMsg');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!input.value.includes('@')) { input.setAttribute('aria-invalid', 'true'); msg.textContent = 'That email doesn\'t look right.'; return; }
    input.setAttribute('aria-invalid', 'false');
    if (CONFIG.signupUrl) { form.action = CONFIG.signupUrl; form.method = 'post'; input.name = 'email'; form.submit(); return; }
    msg.innerHTML = '<span class="ph">[Signup not connected yet — add your email-list service in main.js]</span>';
  });
}

/* ---------------------------------------------------------
   Stockist map with beer filters
   --------------------------------------------------------- */
function setupFind() {
  const canned = BEERS.filter((b) => !b.ph);
  const chipColor = { hazy: 'var(--yellow)', porter: 'var(--blue)', pils: 'var(--paper)' };
  $('#filters').insertAdjacentHTML('beforeend', canned.map((b) =>
    `<button class="chip-btn" data-beer="${b.id}" aria-pressed="false"><i style="--c:${chipColor[b.id]}"></i>${esc(b.title)}</button>`).join(''));
  $('#pins').innerHTML = STOCKISTS.map((s, i) => `
    <g class="pin" data-i="${i}" transform="translate(${s.x} ${s.y})" tabindex="-1">
      <path d="M0 0 C-4 -10 -16 -16 -16 -28 A16 16 0 1 1 16 -28 C16 -16 4 -10 0 0 Z" fill="#1A1A1A" stroke="#F3EEE2" stroke-width="2.5"/>
      <text x="0" y="-23">${i + 1}</text>
    </g>`).join('');
  $('#stockList').innerHTML = STOCKISTS.map((s, i) => `
    <li data-i="${i}">
      <span class="stock-num">${i + 1}</span>
      <span class="stock-name"><span class="ph">${esc(s.name)}</span></span>
      <span><span class="ph">${esc(s.where)}</span><span class="stock-tags">${s.beers.map((id) => `<span>${esc(BEERS.find((b) => b.id === id).title)}</span>`).join('')}</span></span>
    </li>`).join('') + '<li class="stock-empty" hidden>No stockists for that one yet.</li>';

  const chips = $$('#filters .chip-btn');
  chips.forEach((c) => c.addEventListener('click', () => {
    const want = c.dataset.beer;
    chips.forEach((o) => { o.classList.toggle('is-on', o === c); o.setAttribute('aria-pressed', o === c ? 'true' : 'false'); });
    let shown = 0;
    STOCKISTS.forEach((s, i) => {
      const on = want === 'all' || s.beers.includes(want);
      $(`.pin[data-i="${i}"]`).classList.toggle('dim', !on);
      $(`#stockList li[data-i="${i}"]`).hidden = !on;
      if (on) shown++;
    });
    $('#stockList .stock-empty').hidden = shown > 0;
  }));
  // hover link between list and map
  const hot = (i, on) => {
    $(`.pin[data-i="${i}"]`)?.classList.toggle('hot', on);
    $(`#stockList li[data-i="${i}"]`)?.classList.toggle('hot', on);
  };
  $$('#pins .pin, #stockList li[data-i]').forEach((el) => {
    el.addEventListener('mouseenter', () => hot(el.dataset.i, true));
    el.addEventListener('mouseleave', () => hot(el.dataset.i, false));
  });
}

/* ---------------------------------------------------------
   Nav, sections snapping into register, scroll loop
   --------------------------------------------------------- */
function setupNav() {
  const btn = $('#navToggle');
  const links = $('#navLinks');
  const close = () => { links.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); };
  btn.addEventListener('click', () => {
    const open = !links.classList.contains('open');
    links.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', String(open));
  });
  $$('a', links).forEach((a) => a.addEventListener('click', close));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
}

function setupRegister() {
  const secs = $$('.sec');
  if (!('IntersectionObserver' in window)) { secs.forEach((s) => s.classList.add('in')); return; }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { threshold: 0, rootMargin: '0px 0px -18% 0px' });
  secs.forEach((s) => { if (s.id !== 'top') io.observe(s); });
  // hero: start off-register, snap in after first paint
  requestAnimationFrame(() => setTimeout(() => $('#top').classList.add('in'), 180));
}

function init() {
  $('#year').textContent = new Date().getFullYear();
  setupHero();
  inkAll();
  setupOpen();
  setupNav();
  const tapScroll = setupTaps();
  const routeScroll = setupRoute();
  setupTicket();
  setupSignup();
  setupFind();
  setupRegister();

  let ticking = false;
  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { tapScroll(); routeScroll(); ticking = false; });
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', () => { inkAll(); onScroll(); });
  onScroll();
  if (document.fonts) document.fonts.ready.then(inkAll);
}
init();
