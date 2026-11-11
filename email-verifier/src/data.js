'use strict';
const fs = require('fs');
const path = require('path');

// Prefixes that usually belong to a shared inbox, not a person.
const ROLE_PREFIXES = new Set(`
admin administrator info information support sales contact contactus hello hi help helpdesk billing
accounts accounting office team marketing noreply no-reply donotreply do-not-reply postmaster webmaster
hostmaster abuse jobs job careers career hr recruiting recruitment press media pr enquiries enquiry
inquiries inquiry service services customerservice customercare care feedback security privacy legal
finance newsletter news mail email root sysadmin it tech dev ops all staff everyone orders order shop
store booking bookings reservations reception frontdesk partners partnership affiliates investor
investors ir compliance purchasing procurement operations general mailbox web website social events
`.split(/\s+/).filter(Boolean));

const FREE_PROVIDERS = new Set(`
gmail.com googlemail.com yahoo.com yahoo.co.uk yahoo.co.in yahoo.ca yahoo.fr yahoo.de ymail.com
rocketmail.com hotmail.com hotmail.co.uk hotmail.fr hotmail.de hotmail.it outlook.com outlook.fr
live.com live.co.uk msn.com aol.com icloud.com me.com mac.com protonmail.com protonmail.ch proton.me
pm.me gmx.com gmx.de gmx.net gmx.at mail.com zoho.com zohomail.com yandex.com yandex.ru mail.ru
inbox.ru bk.ru list.ru tutanota.com tuta.io fastmail.com hey.com qq.com 163.com 126.com sina.com
web.de t-online.de comcast.net verizon.net att.net sbcglobal.net bellsouth.net cox.net charter.net
earthlink.net btinternet.com sky.com virginmedia.com orange.fr free.fr laposte.net sfr.fr libero.it
virgilio.it rediffmail.com naver.com daum.net hanmail.net seznam.cz wp.pl o2.pl interia.pl
`.split(/\s+/).filter(Boolean));

const BUILTIN_DISPOSABLE = `
mailinator.com mailinator.net mailinator2.com guerrillamail.com guerrillamail.net guerrillamail.org
guerrillamail.biz guerrillamail.de guerrillamailblock.com sharklasers.com grr.la pokemail.net spam4.me
10minutemail.com 10minutemail.net 10minutemail.co.uk 10mail.org temp-mail.org temp-mail.io tempmail.com
tempmail.net tempmailo.com tempmail.plus tempail.com tempmailaddress.com tempinbox.com tempr.email
throwawaymail.com yopmail.com yopmail.net yopmail.fr trashmail.com trashmail.net trashmail.de
mytrashmail.com getnada.com nada.email dispostable.com maildrop.cc mailnesia.com mintemail.com
mohmal.com fakeinbox.com emailondeck.com spamgourmet.com mytemp.email burnermail.io mailcatch.com
moakt.com discard.email mailpoof.com spambox.us 33mail.com getairmail.com inboxkitten.com emailfake.com
fakemail.net tmpmail.org tmpmail.net tmail.ws 1secmail.com 1secmail.net 1secmail.org mail.tm mail.gw
minuteinbox.com harakirimail.com luxusmail.org mvrht.com mailsac.com anonbox.net mailforspam.com
trbvm.com dropmail.me linshiyouxiang.net owlymail.com mailtemp.info wegwerfmail.de wegwerfmail.net
einrot.com mailexpire.com jetable.org kasmail.com spamfree24.org incognitomail.com deadaddress.com
crazymailing.com fexbox.org vomoto.com emltmp.com armyspy.com cuvox.de dayrep.com fleckens.hu gustr.com
jourrapide.com rhyta.com superrito.com teleworm.us emailtemporanea.com tempmail.dev disposablemail.com
mailnull.com spamex.com mailmetrash.com trash-mail.com throwam.com getnada.cc mailbox.in.ua
`.split(/\s+/).filter(Boolean);

// Domains people mean when they mistype. Real providers that look like typos
// (mail.com, ymail.com) are listed so they are never "corrected".
const POPULAR_DOMAINS = [
  'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'hotmail.com', 'outlook.com', 'live.com',
  'aol.com', 'icloud.com', 'me.com', 'mac.com', 'msn.com', 'mail.com', 'gmx.com', 'protonmail.com',
  'proton.me', 'zoho.com', 'yandex.com', 'comcast.net', 'verizon.net', 'att.net', 'sbcglobal.net',
  'hotmail.co.uk', 'yahoo.co.uk', 'btinternet.com', 'fastmail.com',
];
const TLD_FIXES = { con: 'com', cmo: 'com', ocm: 'com', vom: 'com', xom: 'com', comm: 'com', cm: 'com', om: 'com', nte: 'net', ner: 'net', ogr: 'org', orh: 'org' };

// Where data/ may live: next to src/ normally, or the function root when bundled on Netlify.
const DATA_DIRS = [
  path.join(__dirname, '..', 'data'),
  path.join(process.cwd(), 'data'),
  path.join(process.cwd(), 'email-verifier', 'data'),
  ...(process.env.LAMBDA_TASK_ROOT ? [path.join(process.env.LAMBDA_TASK_ROOT, 'data'), path.join(process.env.LAMBDA_TASK_ROOT, 'email-verifier', 'data')] : []),
];

function loadDisposable() {
  const set = new Set(BUILTIN_DISPOSABLE);
  // Bigger lists: data/disposable_domains.txt (from `npm run update-disposable`)
  // and data/disposable_custom.txt (your own additions). One domain per line.
  const dir = DATA_DIRS.find((d) => fs.existsSync(path.join(d, 'disposable_domains.txt'))) || DATA_DIRS[0];
  for (const name of ['disposable_domains.txt', 'disposable_custom.txt']) {
    const file = path.join(dir, name);
    if (!fs.existsSync(file)) continue;
    for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
      const d = line.trim().toLowerCase();
      if (d && !d.startsWith('#')) set.add(d);
    }
  }
  return set;
}

module.exports = { ROLE_PREFIXES, FREE_PROVIDERS, POPULAR_DOMAINS, TLD_FIXES, loadDisposable };
