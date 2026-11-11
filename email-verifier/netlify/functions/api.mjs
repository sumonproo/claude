// Netlify Function serving /api/* for the dashboard.
// Netlify runs functions on AWS Lambda, where outbound port 25 is blocked, so this
// can only do the checks that need no mail server conversation: syntax, typos,
// disposable, role, free provider and domain/MX. For mailbox checks, point the
// dashboard (Settings) at your own verifier server.
import verifierModule from '../../src/verifier.js';

const { EmailVerifier, DISPOSABLE_COUNT } = verifierModule;
const MAX_EMAILS = 500; // per request; the dashboard sends lists in batches
const verifier = new EmailVerifier({ smtp: false, concurrency: 20 });

const json = (status, body) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

export default async (req) => {
  const { pathname } = new URL(req.url);
  const route = pathname.replace(/^.*\/api\//, '');

  if (route === 'config') {
    return json(200, { mode: 'netlify', smtp: false, maxEmails: MAX_EMAILS, disposableDomains: DISPOSABLE_COUNT });
  }
  if (route === 'selftest') {
    return json(200, {
      port25: false, mailFromAccepted: false, dns: true,
      details: ['Running on Netlify: domain and list checks only.'],
      tips: ['Netlify blocks port 25, so mailboxes cannot be checked from here. Add your verifier server in Settings to check mailboxes.'],
    });
  }
  if (route === 'verify' && req.method === 'POST') {
    let body;
    try { body = await req.json(); } catch { return json(400, { error: 'Body must be JSON' }); }
    const emails = Array.isArray(body.emails) ? body.emails.map(String) : [];
    if (!emails.length) return json(400, { error: 'Send {"emails": ["a@b.com", ...]}' });
    if (emails.length > MAX_EMAILS) return json(413, { error: `At most ${MAX_EMAILS} emails per request` });
    const results = await verifier.verifyMany(emails);
    const ndjson = results.map((r, index) => JSON.stringify({ index, ...r })).join('\n') + '\n';
    return new Response(ndjson, { headers: { 'Content-Type': 'application/x-ndjson', 'Cache-Control': 'no-store' } });
  }
  return json(404, { error: 'Not found' });
};

export const config = { path: '/api/*' };
