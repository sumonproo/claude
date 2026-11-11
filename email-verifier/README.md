# Email Verifier

A self-hosted email verifier for cold outreach lists. It runs the same checks as paid tools
like Reoon or Hunter, on your own server, with no credits and no limits.
Plain Node.js with **no dependencies**: no `npm install` needed.

- **Web UI**: paste emails or upload a CSV (it finds the email column and keeps your other columns), watch results come in, then download *valid only*, *valid + risky*, or everything.
- **CLI**: `node cli.js leads.csv` writes `leads.verified.csv`.
- **HTTP API**: `POST /api/verify`, for Make, n8n, Zapier or your own scripts.

## What it checks

| Step | What it does | Needs port 25? |
|---|---|---|
| Syntax | RFC-style address rules, length limits | no |
| Typo | `gmial.com` → *did you mean gmail.com?*, `.con` → `.com` | no |
| Disposable | Mailinator, 10minutemail, Yopmail… (built-in list + 4,000+ with `npm run update-disposable`) | no |
| Role account | `info@`, `sales@`, `support@`… flagged (lower reply rates) | no |
| Free provider | Gmail, Outlook, Yahoo… flagged | no |
| Domain / MX | Domain exists and has a mail server (MX, or A record fallback, null-MX aware) | no |
| **Mailbox (SMTP)** | Connects to the domain's mail server and asks `RCPT TO:<address>`. Hangs up before `DATA`, so **no email is ever sent** | **yes** |
| **Catch-all** | Also asks about a random address on the same domain. If that is accepted too, the domain accepts everything | **yes** |

Addresses on the same domain share one SMTP connection, and MX and catch-all results are cached for 30 minutes.

### Results

| Status | Meaning | What to do |
|---|---|---|
| `valid` | Mail server confirmed the mailbox exists | Send |
| `invalid` | Bad syntax, no mail server, or *mailbox does not exist* | Remove |
| `risky` | `catch_all`, `disposable` or `mailbox_full` | Send carefully, or skip |
| `unknown` | Server would not say: greylisting, blocked your IP, timeout, port 25 closed | Retry later / from a better IP |

Each result also has `reason`, `score` (0–100), `suggestion`, `role`, `free`, `disposable`, `catchAll`, `mx`, and the raw `smtp` reply.

## ⚠️ The one thing you must get right: port 25

The mailbox check talks to mail servers on **TCP port 25**. Most home internet connections and many
clouds block outgoing port 25 (AWS, Google Cloud, Azure, and DigitalOcean by default). Without it, every
address comes back `unknown / smtp_unreachable`, and you only get the DNS-level checks.

Run it on a small VPS that allows port 25. Some providers open it by default; others open it on request
(for example Hetzner after the first paid invoice, Vultr and Linode via a support ticket, Contabo, OVH).
Check your provider's current policy. A €4–6/month VPS is enough.

For the best results:

1. **Give the server a hostname**, e.g. `verify.yourdomain.com`, with an A record pointing to the VPS IP.
2. **Set reverse DNS (PTR)** of the VPS IP to that same hostname (in the VPS control panel).
3. Start the verifier with `HELO_HOST=verify.yourdomain.com` (and optionally `MAIL_FROM=check@yourdomain.com`).
4. **Never run it from the IP or server you send your cold emails from.** Probing lots of mail servers
   can get an IP listed, and you don't want that to touch your sending reputation. Use a separate VPS,
   and ideally a separate domain from your main sending domain.
5. Click **Check server setup** in the UI (or `node cli.js --check`) to confirm port 25, MAIL FROM and PTR are fine.

## Run it

Requires Node.js 18 or newer.

```bash
cd email-verifier
HELO_HOST=verify.yourdomain.com npm start
```

Open http://localhost:3000. By default it only listens on localhost. To reach it from outside,
**set a password** (otherwise anyone could use your server to probe mail servers):

```bash
BIND=0.0.0.0 AUTH_USER=me AUTH_PASS='a-long-password' HELO_HOST=verify.yourdomain.com npm start
```

Use Caddy or nginx in front of it for HTTPS, or reach it through an SSH tunnel: `ssh -L 3000:localhost:3000 you@vps`.

With Docker:

```bash
docker build -t email-verifier .
docker run -d --restart unless-stopped -p 3000:3000 -e HELO_HOST=verify.yourdomain.com -e AUTH_USER=me -e AUTH_PASS='a-long-password' email-verifier
```

### CLI

```bash
node cli.js --check                          # can this machine do SMTP checks?
node cli.js jane@acme.com bob@example.org    # quick check
node cli.js leads.csv                        # writes leads.verified.csv (all original columns + results)
node cli.js leads.csv -o clean.csv --concurrency 10
node cli.js leads.csv --no-smtp              # DNS-level checks only
node cli.js jane@acme.com --json
```

### API

```bash
curl -X POST http://localhost:3000/api/verify \
  -H 'Content-Type: application/json' \
  -d '{"emails": ["jane@acme.com", "bob@example.org"]}'
```

The response streams one JSON object per line (NDJSON) as each address finishes, each with its `index` in
your input. Add `"smtp": false` for DNS-only checks. `GET /api/selftest` runs the port 25 check.
If `AUTH_USER` is set, add `-u me:password`.

### Settings (environment variables)

| Variable | Default | |
|---|---|---|
| `HELO_HOST` | `localhost` | Hostname sent in `EHLO`. Set this to your server's hostname. |
| `MAIL_FROM` | `verify@HELO_HOST` | Sender used in `MAIL FROM` |
| `PORT` / `BIND` | `3000` / `127.0.0.1` | Where the web server listens |
| `AUTH_USER` / `AUTH_PASS` | none | Password-protect the UI and API |
| `CONCURRENCY` | `5` | Domains checked in parallel |
| `SMTP_TIMEOUT` | `12000` | ms per SMTP connection |
| `MAX_EMAILS_PER_REQUEST` | `10000` | |

Keep the disposable list fresh with `npm run update-disposable`. Put your own junk domains in `data/disposable_custom.txt`.

## Honest limits (paid tools have the same ones)

- **Catch-all domains** (many company domains on Microsoft 365, Mimecast, Proofpoint) accept every address,
  so no SMTP check can tell real from fake. They come back `risky / catch_all`. Paid tools sell a
  "catch-all validation" add-on that guesses, using sending history this tool doesn't have.
- **Yahoo, AOL and some others** accept every `RCPT TO`, so they show as catch-all.
- **Greylisting** returns `unknown`. Re-run those addresses after 5–15 minutes and many become `valid`.
- Verify **slowly and in moderate batches** (the default concurrency is deliberately low). Hammering one
  provider with thousands of checks per minute gets your IP rate-limited or blocked, which shows up as `smtp_blocked`.

## Development

```bash
npm test     # unit tests + end-to-end tests against a fake SMTP server (no network needed)
```

Code: `src/verifier.js` (checks + result logic), `src/smtp.js` (SMTP conversation), `src/data.js` (role / free /
disposable lists), `src/selftest.js`, `server.js`, `cli.js`, `public/index.html` (UI), `public/csv.js` (shared CSV parser).
