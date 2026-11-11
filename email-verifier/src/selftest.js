'use strict';
const dns = require('dns').promises;
const os = require('os');
const { smtpProbe } = require('./smtp');

/**
 * Can this machine talk SMTP on port 25? Most home ISPs and many clouds
 * (AWS, GCP, Azure, DigitalOcean by default) block it, and without it the
 * mailbox check returns "unknown" for every address.
 */
async function selfTest({ heloHost = process.env.HELO_HOST || 'localhost', timeout = 10000 } = {}) {
  const report = { heloHost, hostname: os.hostname(), dns: false, port25: false, mailFromAccepted: false, publicIp: null, ptr: null, details: [], tips: [] };
  let mxHost;
  try {
    const mx = (await dns.resolveMx('gmail.com')).sort((a, b) => a.priority - b.priority);
    mxHost = mx[0].exchange;
    report.dns = true;
    report.details.push(`DNS works (gmail.com MX = ${mxHost})`);
  } catch (e) {
    report.details.push(`DNS lookup failed: ${e.code || e.message}`);
    report.tips.push('DNS is not working on this machine, so nothing can be verified.');
    return report;
  }

  try {
    const res = await fetch('https://api.ipify.org', { signal: AbortSignal.timeout(5000) });
    report.publicIp = (await res.text()).trim();
    report.ptr = (await dns.reverse(report.publicIp).catch(() => []))[0] || null;
  } catch { /* optional */ }

  const r = await smtpProbe({ host: mxHost, heloHost, mailFrom: `verify@${heloHost}`, rcpts: [], timeout });
  report.port25 = !!(r.connected && r.banner);
  report.mailFromAccepted = r.mailFrom?.code === 250;
  if (!report.port25) {
    report.details.push(`Could not open SMTP on ${mxHost}:25 (${r.error || 'no banner'})`);
    report.tips.push('Outbound port 25 is blocked here. Run the verifier on a VPS that allows port 25 (see README), or use "DNS only" mode.');
  } else {
    report.details.push(`Port 25 open: ${r.banner.code} ${r.banner.message.slice(0, 80)}`);
    report.details.push(`MAIL FROM: ${r.mailFrom ? `${r.mailFrom.code} ${r.mailFrom.message.slice(0, 120)}` : 'no reply'}`);
    if (!report.mailFromAccepted) report.tips.push('The server refused MAIL FROM. Set HELO_HOST to a real hostname whose DNS points to this server, with a matching reverse DNS (PTR).');
  }
  if (heloHost === 'localhost') report.tips.push('HELO_HOST is "localhost". Set it to a real hostname (e.g. verify.yourdomain.com) so mail servers take you seriously.');
  if (report.publicIp && !report.ptr) report.tips.push(`Public IP ${report.publicIp} has no reverse DNS (PTR). Ask your VPS provider to set one to your HELO_HOST.`);
  if (report.ptr && heloHost !== 'localhost' && report.ptr.replace(/\.$/, '') !== heloHost) report.tips.push(`Reverse DNS is ${report.ptr}, but HELO_HOST is ${heloHost}. Making them match improves results.`);
  return report;
}

module.exports = { selfTest };
