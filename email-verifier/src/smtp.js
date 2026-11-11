'use strict';
const net = require('net');

/**
 * Open one SMTP conversation and ask the server about each recipient,
 * without ever sending DATA (so no email is delivered).
 *
 *   220 banner -> EHLO -> MAIL FROM -> RCPT TO (one per address) -> QUIT
 *
 * Resolves (never rejects) with what the server said.
 */
function smtpProbe({ host, port = 25, heloHost, mailFrom, rcpts, timeout = 12000 }) {
  return new Promise((resolve) => {
    const result = { host, connected: false, banner: null, mailFrom: null, rcpts: [], error: null };
    const replies = [];
    let waiter = null;
    let lines = [];
    let buf = '';
    let done = false;

    const socket = net.createConnection({ host, port });
    socket.setEncoding('utf8');
    socket.setTimeout(timeout);

    const finish = (err) => {
      if (done) return;
      done = true;
      if (err && !result.error) result.error = err;
      if (waiter) { waiter.reject(new Error(result.error || 'closed')); waiter = null; }
      socket.destroy();
      resolve(result);
    };

    const read = () => replies.length
      ? Promise.resolve(replies.shift())
      : new Promise((res, rej) => { waiter = { resolve: res, reject: rej }; });

    const send = (line) => { socket.write(line + '\r\n'); return read(); };

    socket.on('connect', () => { result.connected = true; });
    socket.on('timeout', () => finish('timeout'));
    socket.on('error', (e) => finish(e.code || e.message));
    socket.on('close', () => finish(result.error ? null : 'connection_closed'));
    socket.on('data', (chunk) => {
      buf += chunk;
      if (buf.length > 64 * 1024) return finish('reply_too_long');
      let i;
      while ((i = buf.indexOf('\n')) !== -1) {
        const line = buf.slice(0, i).replace(/\r$/, '');
        buf = buf.slice(i + 1);
        lines.push(line);
        // "250-..." continues a multi-line reply, "250 ..." ends it.
        if (/^\d{3}(?: |$)/.test(line) || !/^\d{3}-/.test(line)) {
          const code = parseInt(line.slice(0, 3), 10) || 0;
          const reply = { code, message: lines.map((l) => l.slice(4)).join(' ').trim() };
          lines = [];
          if (waiter) { const w = waiter; waiter = null; w.resolve(reply); } else replies.push(reply);
        }
      }
    });

    (async () => {
      const banner = await read();
      result.banner = banner;
      if (banner.code !== 220) return finish(`banner_${banner.code}`);

      let r = await send(`EHLO ${heloHost}`);
      if (r.code !== 250) r = await send(`HELO ${heloHost}`);
      if (r.code !== 250) { result.mailFrom = r; return finish('helo_rejected'); }

      r = await send(`MAIL FROM:<${mailFrom}>`);
      result.mailFrom = r;
      if (r.code !== 250) return finish('mail_from_rejected');

      for (const rcpt of rcpts) {
        r = await send(`RCPT TO:<${rcpt}>`);
        result.rcpts.push({ rcpt, code: r.code, message: r.message });
        if (r.code === 421) break; // server is closing the connection
      }
      socket.write('QUIT\r\n');
      result.error = null;
      finish();
    })().catch(() => finish());
  });
}

module.exports = { smtpProbe };
