#!/usr/bin/env node
'use strict';
// Downloads the community-maintained list of disposable email domains
// (https://github.com/disposable-email-domains/disposable-email-domains, ~4k+ domains)
// into data/disposable_domains.txt. Run it now and then; the verifier picks it up on start.
const fs = require('fs');
const path = require('path');

const URL = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/main/disposable_email_blocklist.conf';
const OUT = path.join(__dirname, '..', 'data', 'disposable_domains.txt');

(async () => {
  const res = await fetch(URL);
  if (!res.ok) throw new Error(`Download failed: ${res.status}`);
  const domains = (await res.text()).split(/\r?\n/).map((l) => l.trim().toLowerCase()).filter((l) => l && !l.startsWith('#'));
  fs.writeFileSync(OUT, `# Source: ${URL}\n# Updated: ${new Date().toISOString()}\n${domains.join('\n')}\n`);
  console.log(`Saved ${domains.length} disposable domains to ${OUT}`);
})().catch((e) => { console.error(e.message); process.exitCode = 1; });
