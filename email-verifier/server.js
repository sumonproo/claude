#!/usr/bin/env node
'use strict';
const http = require('http');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { EmailVerifier } = require('./src/verifier');
const { selfTest } = require('./src/selftest');

const PORT = Number(process.env.PORT) || 3000;
const HOST = process.env.BIND || '127.0.0.1';
const MAX_EMAILS = Number(process.env.MAX_EMAILS_PER_REQUEST) || 10000;
const AUTH_USER = process.env.AUTH_USER || '';
const AUTH_PASS = process.env.AUTH_PASS || '';
const PUBLIC = path.join(__dirname, 'public');
const TYPES = { '.html': 'text/html; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.css': 'text/css; charset=utf-8', '.svg': 'image/svg+xml' };

// One shared verifier keeps the MX and catch-all caches warm between requests.
const verifier = new EmailVerifier();
const dnsOnly = new EmailVerifier({ smtp: false });
dnsOnly.mxCache = verifier.mxCache;

function authorized(req) {
  if (!AUTH_USER && !AUTH_PASS) return true;
  const [scheme, value] = (req.headers.authorization || '').split(' ');
  if (scheme !== 'Basic' || !value) return false;
  const given = Buffer.from(Buffer.from(value, 'base64').toString());
  const want = Buffer.from(`${AUTH_USER}:${AUTH_PASS}`);
  return given.length === want.length && crypto.timingSafeEqual(given, want);
}

function readBody(req, limit = 5 * 1024 * 1024) {
  return new Promise((resolve, reject) => {
    let size = 0;
    const chunks = [];
    req.on('data', (c) => {
      size += c.length;
      if (size > limit) { reject(Object.assign(new Error('Request too large'), { status: 413 })); req.destroy(); return; }
      chunks.push(c);
    });
    req.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    req.on('error', reject);
  });
}

function json(res, status, body) {
  res.writeHead(status, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(body));
}

async function handleVerify(req, res) {
  let body;
  try { body = JSON.parse(await readBody(req)); } catch (e) { return json(res, e.status || 400, { error: e.message }); }
  const emails = Array.isArray(body.emails) ? body.emails.map(String) : [];
  if (!emails.length) return json(res, 400, { error: 'Send {"emails": ["a@b.com", ...]}' });
  if (emails.length > MAX_EMAILS) return json(res, 413, { error: `At most ${MAX_EMAILS} emails per request` });

  // Stream one JSON line per result so the UI can show progress.
  res.writeHead(200, { 'Content-Type': 'application/x-ndjson', 'Cache-Control': 'no-store', 'X-Accel-Buffering': 'no' });
  const ac = new AbortController();
  res.on('close', () => ac.abort());
  const v = body.smtp === false ? dnsOnly : verifier;
  await v.verifyMany(emails, (result, index) => {
    if (!res.writableEnded) res.write(JSON.stringify({ index, ...result }) + '\n');
  }, { signal: ac.signal });
  res.end();
}

function serveStatic(req, res) {
  const url = new URL(req.url, 'http://x');
  const rel = url.pathname === '/' ? 'index.html' : decodeURIComponent(url.pathname).replace(/^\/+/, '');
  const file = path.join(PUBLIC, rel);
  if (!file.startsWith(PUBLIC + path.sep)) { res.writeHead(403); return res.end(); }
  fs.readFile(file, (err, data) => {
    if (err) { res.writeHead(404); return res.end('Not found'); }
    res.writeHead(200, { 'Content-Type': TYPES[path.extname(file)] || 'application/octet-stream' });
    res.end(data);
  });
}

const server = http.createServer(async (req, res) => {
  try {
    if (!authorized(req)) {
      res.writeHead(401, { 'WWW-Authenticate': 'Basic realm="Email Verifier"' });
      return res.end('Authentication required');
    }
    const { pathname } = new URL(req.url, 'http://x');
    if (pathname === '/api/verify' && req.method === 'POST') return await handleVerify(req, res);
    if (pathname === '/api/selftest' && req.method === 'GET') return json(res, 200, await selfTest({ heloHost: verifier.opts.heloHost }));
    if (pathname === '/api/config' && req.method === 'GET') return json(res, 200, { heloHost: verifier.opts.heloHost, mailFrom: verifier.opts.mailFrom, maxEmails: MAX_EMAILS });
    if (req.method === 'GET') return serveStatic(req, res);
    json(res, 405, { error: 'Method not allowed' });
  } catch (e) {
    if (!res.headersSent) json(res, 500, { error: e.message });
    else res.end();
  }
});

server.listen(PORT, HOST, () => {
  console.log(`Email verifier running at http://${HOST === '0.0.0.0' ? 'localhost' : HOST}:${PORT}`);
  console.log(`HELO ${verifier.opts.heloHost}, MAIL FROM <${verifier.opts.mailFrom}>${AUTH_USER ? ', password protected' : ''}`);
  if (HOST !== '127.0.0.1' && !AUTH_USER) console.log('Warning: listening publicly without AUTH_USER/AUTH_PASS. Anyone could use your IP to probe mail servers.');
});
