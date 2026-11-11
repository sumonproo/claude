'use strict';
const dnsPromises = require('dns').promises;
const crypto = require('crypto');
const { smtpProbe } = require('./smtp');
const { ROLE_PREFIXES, FREE_PROVIDERS, POPULAR_DOMAINS, TLD_FIXES, loadDisposable } = require('./data');

const DISPOSABLE = loadDisposable();

const LOCAL_RE = /^[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*$/;
const DOMAIN_RE = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{1,59})$/;

const STATUS_SCORE = { valid: 95, risky: 50, unknown: 30, invalid: 0 };

function defaultOptions() {
  const heloHost = process.env.HELO_HOST || 'localhost';
  return {
    heloHost,
    mailFrom: process.env.MAIL_FROM || `verify@${heloHost}`,
    smtp: true,                 // false = stop after DNS (fast, no port 25 needed)
    smtpPort: 25,
    timeout: Number(process.env.SMTP_TIMEOUT) || 12000,
    concurrency: Number(process.env.CONCURRENCY) || 5,   // domains checked at once
    maxRcptPerSession: 20,      // addresses asked about per SMTP connection
    maxMxTries: 2,              // MX hosts to try before giving up
    cacheTtlMs: 30 * 60 * 1000,
    resolver: dnsPromises,
  };
}

// ---------- static checks ----------

function normalize(raw) {
  let s = String(raw == null ? '' : raw).trim();
  s = s.replace(/^mailto:/i, '').replace(/^<(.*)>$/, '$1').trim();
  const at = s.lastIndexOf('@');
  if (at < 1) return { email: s, local: s, domain: '' };
  const local = s.slice(0, at);
  let domain = s.slice(at + 1).toLowerCase().replace(/\.$/, '');
  try { domain = new URL(`http://${domain}`).hostname || domain; } catch { /* keep as is */ }
  return { email: `${local}@${domain}`, local, domain };
}

function checkSyntax(local, domain) {
  if (!domain) return 'missing_at_sign';
  if (local.length > 64) return 'local_part_too_long';
  if (local.length + domain.length + 1 > 254) return 'address_too_long';
  if (!LOCAL_RE.test(local)) return 'invalid_local_part';
  if (!DOMAIN_RE.test(domain)) return 'invalid_domain';
  return null;
}

// Edit distance where swapping two neighbouring letters counts as one edit (gmial -> gmail).
function editDistance(a, b) {
  const d = Array.from({ length: a.length + 1 }, (_, i) => [i, ...Array(b.length).fill(0)]);
  for (let j = 1; j <= b.length; j++) d[0][j] = j;
  for (let i = 1; i <= a.length; i++) {
    for (let j = 1; j <= b.length; j++) {
      const cost = a[i - 1] === b[j - 1] ? 0 : 1;
      d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + cost);
      if (i > 1 && j > 1 && a[i - 1] === b[j - 2] && a[i - 2] === b[j - 1]) d[i][j] = Math.min(d[i][j], d[i - 2][j - 2] + 1);
    }
  }
  return d[a.length][b.length];
}

function suggestDomain(domain) {
  if (!domain || POPULAR_DOMAINS.includes(domain)) return null;
  let best = null;
  let bestDist = Infinity;
  for (const d of POPULAR_DOMAINS) {
    const dist = editDistance(domain, d);
    const limit = d.length > 9 ? 2 : 1;
    if (dist <= limit && dist < bestDist) { best = d; bestDist = dist; }
  }
  if (best) return best;
  const dot = domain.lastIndexOf('.');
  const fix = TLD_FIXES[domain.slice(dot + 1)];
  return fix ? `${domain.slice(0, dot)}.${fix}` : null;
}

function isDisposable(domain) {
  const parts = domain.split('.');
  for (let i = 0; i < parts.length - 1; i++) if (DISPOSABLE.has(parts.slice(i).join('.'))) return true;
  return false;
}

function isRole(local) {
  const base = local.toLowerCase().split('+')[0];
  return ROLE_PREFIXES.has(base) || ROLE_PREFIXES.has(base.replace(/[._-]/g, ''));
}

// ---------- SMTP reply interpretation ----------

function classifyReply(code, message) {
  if (code >= 200 && code < 300) return 'accepted';
  const m = String(message || '').toLowerCase();
  if (code >= 400 && code < 500) return /quota|full|over.?limit/.test(m) ? 'full' : 'tempfail';
  if (code >= 500) {
    if (/5\.2\.2|quota|mailbox (is )?full|over.?quota|insufficient storage/.test(m)) return 'full';
    // The sender (your IP) is being refused, not the mailbox: say nothing about the address.
    if (/spamhaus|barracuda|spamcop|sorbs|blacklist|blocklist|reputation|\bptr\b|reverse dns|rdns|client host|your ip|ip address|banned|blocked using/.test(m)) return 'blocked';
    if (/5\.1\.[0-36]|user unknown|unknown user|no such (user|mailbox|recipient|account)|does ?n[o']t exist|not exist|invalid (recipient|mailbox|address|user)|recipient (address )?rejected|mailbox (unavailable|not found|disabled)|address rejected|not found|unrouteable|undeliverable|disabled|deactivated|inactive|unknown recipient|no mailbox/.test(m)) return 'rejected';
    if (/5\.7\.|block|blacklist|blocklist|spamhaus|barracuda|spam|policy|reputation|banned|not permitted|relay|\bptr\b|reverse dns|rdns|helo|authenticat/.test(m)) return 'blocked';
    return 'rejected';
  }
  return 'unknown';
}

function randomLocal() {
  return `nx${crypto.randomBytes(8).toString('hex')}`;
}

// ---------- verifier ----------

class EmailVerifier {
  constructor(options = {}) {
    this.opts = { ...defaultOptions(), ...options };
    if (!options.mailFrom && options.heloHost) this.opts.mailFrom = `verify@${options.heloHost}`;
    this.mxCache = new Map();       // domain -> { at, value: Promise }
    this.catchAllCache = new Map(); // domain -> { at, value: true|false|null }
  }

  cached(map, key) {
    const hit = map.get(key);
    if (hit && Date.now() - hit.at < this.opts.cacheTtlMs) return hit;
    map.delete(key);
    return null;
  }

  lookupMx(domain) {
    const hit = this.cached(this.mxCache, domain);
    if (hit) return hit.value;
    const value = this._lookupMx(domain);
    this.mxCache.set(domain, { at: Date.now(), value });
    return value;
  }

  async _lookupMx(domain) {
    const r = this.opts.resolver;
    try {
      const mx = await r.resolveMx(domain);
      if (mx.length === 1 && (!mx[0].exchange || mx[0].exchange === '.')) return { hosts: [], reason: 'null_mx' };
      if (mx.length) {
        return { hosts: mx.sort((a, b) => a.priority - b.priority).map((m) => m.exchange.replace(/\.$/, '')), implicit: false };
      }
    } catch (e) {
      if (e.code === 'ENOTFOUND') return { hosts: [], reason: 'domain_not_found' };
      if (e.code !== 'ENODATA') return { hosts: [], reason: 'dns_error', error: e.code || e.message };
    }
    // No MX record: RFC 5321 says to fall back to the domain's own A/AAAA record.
    for (const fn of ['resolve4', 'resolve6']) {
      try { if (r[fn] && (await r[fn](domain)).length) return { hosts: [domain], implicit: true }; } catch { /* next */ }
    }
    return { hosts: [], reason: 'no_mx_records' };
  }

  /** Verify one address. */
  async verify(email) {
    return (await this.verifyMany([email]))[0];
  }

  /**
   * Verify a list. Results come back in input order; `onResult(result, index)`
   * fires as soon as each one is known (useful for progress bars).
   * Pass an AbortSignal to stop starting new domains.
   */
  async verifyMany(emails, onResult = () => {}, { signal } = {}) {
    const results = new Array(emails.length);
    const emit = (i, r) => { results[i] = r; onResult(r, i); };
    const byDomain = new Map(); // domain -> Map(lowercased email -> { entry, indexes })

    emails.forEach((raw, i) => {
      const { email, local, domain } = normalize(raw);
      const base = { input: String(raw ?? ''), email, local, domain };
      const syntaxError = checkSyntax(local, domain);
      if (syntaxError) {
        return emit(i, finalize(base, { status: 'invalid', reason: syntaxError, syntax: false }));
      }
      const flags = {
        syntax: true,
        suggestion: suggestDomain(domain),
        disposable: isDisposable(domain),
        role: isRole(local),
        free: FREE_PROVIDERS.has(domain),
      };
      if (!byDomain.has(domain)) byDomain.set(domain, new Map());
      const group = byDomain.get(domain);
      const key = email.toLowerCase();
      if (!group.has(key)) group.set(key, { base, flags, indexes: [] });
      group.get(key).indexes.push(i);
    });

    const tasks = [...byDomain.entries()].map(([domain, group]) => async () => {
      const entries = [...group.values()];
      const out = await this.checkDomain(domain, entries);
      entries.forEach((entry, k) => entry.indexes.forEach((i) => emit(i, { ...out[k], input: String(emails[i] ?? '') })));
    });
    await runPool(tasks, this.opts.concurrency, signal);
    return results;
  }

  async checkDomain(domain, entries) {
    const mx = await this.lookupMx(domain);
    const withMx = (extra) => entries.map(({ base, flags }) => finalize(base, { ...flags, mx: mx.hosts, ...extra }));

    if (!mx.hosts.length) {
      if (mx.reason === 'dns_error') return withMx({ status: 'unknown', reason: 'dns_error' });
      return withMx({ status: 'invalid', reason: mx.reason });
    }

    // Disposable domains are never worth sending to; skip the SMTP round trip.
    const out = new Array(entries.length);
    const toProbe = [];
    entries.forEach((e, k) => {
      if (e.flags.disposable) out[k] = finalize(e.base, { ...e.flags, mx: mx.hosts, status: 'risky', reason: 'disposable' });
      else toProbe.push(k);
    });

    if (!this.opts.smtp) {
      toProbe.forEach((k) => { out[k] = finalize(entries[k].base, { ...entries[k].flags, mx: mx.hosts, status: 'unknown', reason: 'smtp_skipped' }); });
      return out;
    }

    const cachedCatchAll = this.cached(this.catchAllCache, domain);
    let catchAll = cachedCatchAll ? cachedCatchAll.value : undefined;

    for (let start = 0; start < toProbe.length; start += this.opts.maxRcptPerSession) {
      const chunk = toProbe.slice(start, start + this.opts.maxRcptPerSession);
      const probeAddr = catchAll === undefined ? `${randomLocal()}@${domain}` : null;
      const rcpts = [...(probeAddr ? [probeAddr] : []), ...chunk.map((k) => entries[k].base.email)];

      const session = await this.probeMx(mx.hosts, rcpts);
      const replies = new Map(session.rcpts.map((r) => [r.rcpt, r]));

      if (probeAddr) {
        const p = replies.get(probeAddr);
        const kind = p ? classifyReply(p.code, p.message) : null;
        catchAll = kind === 'accepted' ? true : kind === 'rejected' ? false : undefined;
        if (catchAll !== undefined) this.catchAllCache.set(domain, { at: Date.now(), value: catchAll });
      }

      for (const k of chunk) {
        const { base, flags } = entries[k];
        const rep = replies.get(base.email);
        const smtp = rep ? { host: session.host, code: rep.code, message: rep.message } : { host: session.host, error: session.error, code: session.mailFrom?.code, message: session.mailFrom?.message };
        out[k] = finalize(base, { ...flags, mx: mx.hosts, smtp, catchAll: catchAll === undefined ? null : catchAll, ...interpret(rep, session, catchAll) });
      }
    }
    return out;
  }

  async probeMx(hosts, rcpts) {
    let last = null;
    for (const host of hosts.slice(0, this.opts.maxMxTries)) {
      last = await smtpProbe({ host, port: this.opts.smtpPort, heloHost: this.opts.heloHost, mailFrom: this.opts.mailFrom, rcpts, timeout: this.opts.timeout });
      if (last.rcpts.length) return last;
      // Try the next MX only when this one was unreachable; a policy rejection will repeat.
      if (last.connected && last.banner) return last;
    }
    return last;
  }
}

function interpret(rep, session, catchAll) {
  if (!rep) {
    if (!session.connected) return { status: 'unknown', reason: 'smtp_unreachable' };
    if (session.error === 'timeout') return { status: 'unknown', reason: 'smtp_timeout' };
    return { status: 'unknown', reason: session.error === 'mail_from_rejected' || session.error === 'helo_rejected' ? 'smtp_blocked' : 'smtp_error' };
  }
  switch (classifyReply(rep.code, rep.message)) {
    case 'accepted': return catchAll ? { status: 'risky', reason: 'catch_all' } : { status: 'valid', reason: 'accepted' };
    case 'rejected': return { status: 'invalid', reason: 'mailbox_not_found' };
    case 'full': return { status: 'risky', reason: 'mailbox_full' };
    case 'tempfail': return { status: 'unknown', reason: 'greylisted_or_tempfail' };
    case 'blocked': return { status: 'unknown', reason: 'smtp_blocked' };
    default: return { status: 'unknown', reason: 'smtp_error' };
  }
}

function finalize(base, info) {
  const r = {
    input: base.input,
    email: base.email,
    status: info.status,
    reason: info.reason,
    score: STATUS_SCORE[info.status],
    domain: base.domain,
    syntax: info.syntax !== false,
    suggestion: info.suggestion || null,
    disposable: !!info.disposable,
    role: !!info.role,
    free: !!info.free,
    catchAll: info.catchAll ?? null,
    mx: info.mx || [],
    smtp: info.smtp || null,
  };
  if (r.status === 'valid') r.score -= (r.role ? 15 : 0);
  return r;
}

async function runPool(tasks, size, signal) {
  let next = 0;
  const worker = async () => { while (next < tasks.length && !signal?.aborted) await tasks[next++](); };
  await Promise.all(Array.from({ length: Math.max(1, Math.min(size, tasks.length)) }, worker));
}

module.exports = { EmailVerifier, normalize, checkSyntax, suggestDomain, isDisposable, isRole, classifyReply };
