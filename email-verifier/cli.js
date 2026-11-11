#!/usr/bin/env node
'use strict';
const fs = require('fs');
const path = require('path');
const { EmailVerifier } = require('./src/verifier');
const { selfTest } = require('./src/selftest');
const { RESULT_COLUMNS, resultCells } = require('./src/report');
const CSV = require('./public/csv');

const HELP = `Usage:
  node cli.js <file.csv|file.txt> [-o results.csv] [options]
  node cli.js someone@example.com [more@example.com ...]
  node cli.js --check                 test whether this machine can reach port 25

Options:
  -o, --out <file>        write results CSV (default: <input>.verified.csv)
  --no-smtp               DNS checks only (fast, works without port 25)
  --concurrency <n>       domains checked in parallel (default 5)
  --timeout <ms>          SMTP timeout per connection (default 12000)
  --helo <host>           hostname sent in EHLO (or set HELO_HOST)
  --from <address>        MAIL FROM address (or set MAIL_FROM)
  --json                  print JSON instead of a table
`;

function parseArgs(argv) {
  const a = { inputs: [], smtp: true };
  for (let i = 0; i < argv.length; i++) {
    const v = argv[i];
    if (v === '-h' || v === '--help') a.help = true;
    else if (v === '--check') a.check = true;
    else if (v === '--no-smtp') a.smtp = false;
    else if (v === '--json') a.json = true;
    else if (v === '-o' || v === '--out') a.out = argv[++i];
    else if (v === '--concurrency') a.concurrency = Number(argv[++i]);
    else if (v === '--timeout') a.timeout = Number(argv[++i]);
    else if (v === '--helo') a.helo = argv[++i];
    else if (v === '--from') a.from = argv[++i];
    else a.inputs.push(v);
  }
  return a;
}

const COLORS = { valid: '\x1b[32m', invalid: '\x1b[31m', risky: '\x1b[33m', unknown: '\x1b[90m' };
const paint = (s, status) => (process.stdout.isTTY ? `${COLORS[status]}${s}\x1b[0m` : s);

async function main() {
  const args = parseArgs(process.argv.slice(2));
  if (args.help || (!args.check && !args.inputs.length)) { process.stdout.write(HELP); return; }

  if (args.check) {
    const r = await selfTest({ heloHost: args.helo || process.env.HELO_HOST || 'localhost' });
    console.log(r.details.map((d) => `  ${d}`).join('\n'));
    if (r.publicIp) console.log(`  Public IP: ${r.publicIp}  PTR: ${r.ptr || '(none)'}`);
    console.log(r.port25 && r.mailFromAccepted ? '\nReady: full SMTP verification will work.' : '\nNot ready for SMTP checks:');
    r.tips.forEach((t) => console.log(`  - ${t}`));
    process.exitCode = r.port25 ? 0 : 1;
    return;
  }

  let header = null;
  let rows;
  let column = 0;
  let emails;
  const fileInput = args.inputs.length === 1 && fs.existsSync(args.inputs[0]);
  if (fileInput) {
    const text = fs.readFileSync(args.inputs[0], 'utf8');
    const parsed = CSV.findEmailColumn(CSV.parseCSV(text));
    if (parsed.column === -1) throw new Error('Could not find an email column in the file.');
    ({ header, rows, column } = parsed);
    emails = rows.map((r) => (r[column] || '').trim());
  } else {
    emails = args.inputs;
    rows = emails.map((e) => [e]);
    header = ['email'];
  }

  const verifier = new EmailVerifier({
    smtp: args.smtp,
    ...(args.concurrency && { concurrency: args.concurrency }),
    ...(args.timeout && { timeout: args.timeout }),
    ...(args.helo && { heloHost: args.helo }),
    ...(args.from && { mailFrom: args.from }),
  });

  let done = 0;
  const started = Date.now();
  const results = await verifier.verifyMany(emails, (r) => {
    done++;
    if (args.json) return;
    if (!fileInput) console.log(`${paint(r.status.padEnd(8), r.status)} ${r.email}  (${r.reason}${r.suggestion ? `, did you mean @${r.suggestion}?` : ''})`);
    else if (process.stderr.isTTY) process.stderr.write(`\r  verified ${done}/${emails.length}`);
  });
  if (fileInput && process.stderr.isTTY) process.stderr.write('\n');

  if (args.json) { console.log(JSON.stringify(results, null, 2)); return; }

  const counts = results.reduce((m, r) => ((m[r.status] = (m[r.status] || 0) + 1), m), {});
  console.log(`\n${emails.length} addresses in ${((Date.now() - started) / 1000).toFixed(1)}s:  ` +
    ['valid', 'risky', 'invalid', 'unknown'].map((s) => paint(`${s} ${counts[s] || 0}`, s)).join('  '));
  if (counts.unknown && results.filter((r) => r.reason === 'smtp_unreachable').length === counts.unknown && args.smtp) {
    console.log('All unknowns are "smtp_unreachable": port 25 is probably blocked here. Run `node cli.js --check`.');
  }

  if (fileInput || args.out) {
    const out = args.out || args.inputs[0].replace(/(\.\w+)?$/, '.verified.csv');
    const width = Math.max(header ? header.length : 0, ...rows.map((r) => r.length));
    const pad = (r) => [...r, ...Array(width - r.length).fill('')];
    const outRows = [[...pad(header || Array.from({ length: width }, (_, i) => (i === column ? 'email' : `col${i + 1}`))), ...RESULT_COLUMNS]];
    rows.forEach((r, i) => outRows.push([...pad(r), ...resultCells(results[i])]));
    fs.writeFileSync(out, CSV.toCSV(outRows));
    console.log(`Saved ${path.resolve(out)}`);
  }
}

main().catch((e) => { console.error(e.message); process.exitCode = 1; });
