'use strict';
const test = require('node:test');
const assert = require('node:assert');
const net = require('net');
const { EmailVerifier, normalize, checkSyntax, suggestDomain, isRole, isDisposable, classifyReply } = require('../src/verifier');
const CSV = require('../public/csv');

// A tiny fake mail server. `mailboxes` lists accepted addresses; `catchAll` accepts everything.
function fakeSmtp({ mailboxes = [], catchAll = false, greylist = false, blockSender = false } = {}) {
  const sessions = [];
  const server = net.createServer((sock) => {
    const log = [];
    sessions.push(log);
    sock.write('220 mx.test ESMTP ready\r\n');
    let buf = '';
    sock.on('data', (d) => {
      buf += d;
      let i;
      while ((i = buf.indexOf('\r\n')) !== -1) {
        const line = buf.slice(0, i); buf = buf.slice(i + 2);
        log.push(line);
        const cmd = line.slice(0, 4).toUpperCase();
        if (cmd === 'EHLO') sock.write('250-mx.test hello\r\n250-PIPELINING\r\n250 SIZE 1000000\r\n');
        else if (cmd === 'MAIL') sock.write(blockSender ? '554 5.7.1 Client host blocked using zen.spamhaus.org\r\n' : '250 2.1.0 OK\r\n');
        else if (cmd === 'RCPT') {
          const addr = line.match(/<(.*)>/)[1].toLowerCase();
          if (greylist) sock.write('451 4.7.1 Greylisted, try again later\r\n');
          else if (catchAll || mailboxes.includes(addr)) sock.write('250 2.1.5 OK\r\n');
          else if (addr.startsWith('full')) sock.write('552 5.2.2 Mailbox full\r\n');
          else sock.write("550 5.1.1 The email account that you tried to reach does not exist\r\n");
        } else if (cmd === 'QUIT') { sock.write('221 bye\r\n'); sock.end(); }
        else sock.write('502 unknown\r\n');
      }
    });
    sock.on('error', () => {});
  });
  return new Promise((resolve) => server.listen(0, '127.0.0.1', () => resolve({ server, port: server.address().port, sessions })));
}

function fakeResolver(map) {
  const err = (code) => Object.assign(new Error(code), { code });
  return {
    async resolveMx(d) { const v = map[d]; if (v === undefined) throw err('ENOTFOUND'); if (v === 'nomx') throw err('ENODATA'); if (v === 'nullmx') return [{ exchange: '', priority: 0 }]; return [{ exchange: v, priority: 10 }]; },
    async resolve4(d) { if (map[d] === 'nomx' && map[`${d}#a`]) return ['127.0.0.1']; throw err('ENODATA'); },
    async resolve6() { throw err('ENODATA'); },
  };
}

test('normalize and syntax', () => {
  assert.deepStrictEqual(normalize(' <John.Doe@Example.COM> ').email, 'John.Doe@example.com');
  assert.strictEqual(normalize('mailto:a@b.co').email, 'a@b.co');
  assert.strictEqual(checkSyntax('john', 'example.com'), null);
  assert.strictEqual(checkSyntax('john..doe', 'example.com'), 'invalid_local_part');
  assert.strictEqual(checkSyntax('john', 'example'), 'invalid_domain');
  assert.strictEqual(checkSyntax('jo hn', 'example.com'), 'invalid_local_part');
  assert.strictEqual(checkSyntax('a'.repeat(65), 'example.com'), 'local_part_too_long');
  assert.strictEqual(normalize('nope').domain, '');
});

test('typo suggestions, role, disposable', () => {
  assert.strictEqual(suggestDomain('gmial.com'), 'gmail.com');
  assert.strictEqual(suggestDomain('hotmial.com'), 'hotmail.com');
  assert.strictEqual(suggestDomain('acme.con'), 'acme.com');
  assert.strictEqual(suggestDomain('mail.com'), null);
  assert.strictEqual(suggestDomain('acme.com'), null);
  assert.ok(isRole('info'));
  assert.ok(isRole('Sales+leads'));
  assert.ok(!isRole('jane.doe'));
  assert.ok(isDisposable('mailinator.com'));
  assert.ok(isDisposable('foo.mailinator.com'));
  assert.ok(!isDisposable('gmail.com'));
});

test('classifyReply separates "no mailbox" from "we blocked you"', () => {
  assert.strictEqual(classifyReply(250, 'OK'), 'accepted');
  assert.strictEqual(classifyReply(550, '5.1.1 user unknown'), 'rejected');
  assert.strictEqual(classifyReply(550, '5.5.0 Requested action not taken: mailbox unavailable'), 'rejected');
  assert.strictEqual(classifyReply(550, '5.7.1 Service unavailable, Client host [1.2.3.4] blocked using Spamhaus'), 'blocked');
  assert.strictEqual(classifyReply(451, 'greylisted'), 'tempfail');
  assert.strictEqual(classifyReply(552, '5.2.2 mailbox full'), 'full');
});

test('end to end against a fake mail server', async (t) => {
  const real = await fakeSmtp({ mailboxes: ['jane@acme.test', 'info@acme.test'] });
  const ca = await fakeSmtp({ catchAll: true });
  t.after(() => { real.server.close(); ca.server.close(); });

  // Both domains point at 127.0.0.1; the port decides which fake answers, so use two verifiers.
  const resolver = fakeResolver({ 'acme.test': '127.0.0.1', 'catchall.test': '127.0.0.1', 'nullmx.test': 'nullmx', 'mailinator.com': '127.0.0.1' });
  const v = new EmailVerifier({ resolver, smtpPort: real.port, heloHost: 'verify.test', timeout: 3000 });
  const out = await v.verifyMany(['jane@acme.test', 'JANE@acme.test', 'ghost@acme.test', 'info@acme.test', 'full1@acme.test',
    'bad@@acme.test', 'x@missing.test', 'x@nullmx.test', 'x@mailinator.com']);
  const by = (i) => [out[i].status, out[i].reason];
  assert.deepStrictEqual(by(0), ['valid', 'accepted']);
  assert.deepStrictEqual(by(1), ['valid', 'accepted']);            // duplicate, case-insensitive
  assert.deepStrictEqual(by(2), ['invalid', 'mailbox_not_found']);
  assert.deepStrictEqual(by(3), ['valid', 'accepted']);
  assert.strictEqual(out[3].role, true);
  assert.deepStrictEqual(by(4), ['risky', 'mailbox_full']);
  assert.strictEqual(out[5].status, 'invalid');
  assert.deepStrictEqual(by(6), ['invalid', 'domain_not_found']);
  assert.deepStrictEqual(by(7), ['invalid', 'null_mx']);
  assert.deepStrictEqual(by(8), ['risky', 'disposable']);
  assert.strictEqual(out[0].catchAll, false);
  // One SMTP session for all acme.test addresses; no MAIL DATA ever sent.
  assert.strictEqual(real.sessions.length, 1);
  assert.ok(real.sessions[0].every((l) => !/^DATA/i.test(l)));
  assert.ok(real.sessions[0].includes('MAIL FROM:<verify@verify.test>'));

  const v2 = new EmailVerifier({ resolver, smtpPort: ca.port, heloHost: 'verify.test', timeout: 3000 });
  const [r] = await v2.verifyMany(['anyone@catchall.test']);
  assert.deepStrictEqual([r.status, r.reason, r.catchAll], ['risky', 'catch_all', true]);
});

test('greylisting, blocked sender, unreachable server and DNS-only mode', async (t) => {
  const grey = await fakeSmtp({ greylist: true });
  const block = await fakeSmtp({ blockSender: true });
  t.after(() => { grey.server.close(); block.server.close(); });
  const resolver = fakeResolver({ 'acme.test': '127.0.0.1' });

  const [g] = await new EmailVerifier({ resolver, smtpPort: grey.port, timeout: 3000 }).verifyMany(['a@acme.test']);
  assert.deepStrictEqual([g.status, g.reason], ['unknown', 'greylisted_or_tempfail']);

  const [b] = await new EmailVerifier({ resolver, smtpPort: block.port, timeout: 3000 }).verifyMany(['a@acme.test']);
  assert.deepStrictEqual([b.status, b.reason], ['unknown', 'smtp_blocked']);

  const [u] = await new EmailVerifier({ resolver, smtpPort: 1, timeout: 2000 }).verifyMany(['a@acme.test']);
  assert.deepStrictEqual([u.status, u.reason], ['unknown', 'smtp_unreachable']);

  const [d] = await new EmailVerifier({ resolver, smtp: false }).verifyMany(['a@acme.test']);
  assert.deepStrictEqual([d.status, d.reason], ['unknown', 'smtp_skipped']);
});

test('CSV parsing finds the email column and round-trips', () => {
  const rows = CSV.parseCSV('Name,Company,Email\r\n"Doe, Jane",Acme,jane@acme.com\nBob,"Big ""B"" Co",bob@b.co\n');
  const f = CSV.findEmailColumn(rows);
  assert.strictEqual(f.column, 2);
  assert.deepStrictEqual(f.rows[0], ['Doe, Jane', 'Acme', 'jane@acme.com']);
  assert.strictEqual(f.rows[1][1], 'Big "B" Co');
  assert.deepStrictEqual(CSV.parseCSV(CSV.toCSV(rows)), rows);
  assert.strictEqual(CSV.findEmailColumn(CSV.parseCSV('a@b.com\nc@d.com')).header, null);
  assert.strictEqual(CSV.parseCSV('n;email\nx;a@b.com')[1][1], 'a@b.com');
  assert.deepStrictEqual(CSV.extractEmails('a@b.com, <c@d.com>; e@f.io\n'), ['a@b.com', 'c@d.com', 'e@f.io']);
});
