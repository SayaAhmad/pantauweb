# Product

<!-- impeccable:product-schema 1 -->

> Written from repository evidence (`docs/PRD.md` v1.0, `README.md`, `GLOSSARY.md`, routes and controllers). The product interview round did not run in this session, so anything not documented in the repo is marked **INFERRED** and should be confirmed before it constrains new work.

## Platform

web

## Users

**Operator / administrator** — the one role in the MVP. A person on a small operations or IT team who is responsible for keeping a set of websites, TCP ports, and health endpoints reachable. They use Pantau during the working day from a desktop browser, and they are the person who finds out first when something breaks. Their job: see current confirmed status at a glance, find out what broke and for how long, and produce an uptime/response report that can be defended in a monthly review.

Accounts are per installation. Public registration is enabled; a new account is a regular user, and admins manage accounts under **Pengguna**. One organization, one installation — not a multi-tenant service.

## Product Purpose

Pantau runs external availability checks against websites (HTTP status, response time, TLS certificate expiry), TCP ports, and service health endpoints, then turns those checks into confirmed status, incident history, uptime, and reports.

It exists because checking services by hand — or across several disconnected tools — means outages get noticed only after a user complains, there is no record of when an outage started or how long it lasted, SSL expiries are forgotten until the browser warns, and there is no defensible uptime number at review time. Success means the operator knows within minutes that something is down, can trace it to an incident with real start and end times, and can export the numbers without re-deriving them by hand.

## Positioning

Availability from the outside: Pantau measures what a user would experience (HTTP response, port reachability, TLS handshake), not what the server reports about itself. Internal metrics (CPU, RAM, disk) are explicitly deferred to phase 2 — the agent ingest endpoint exists, the interface does not.

Two mechanisms carry that position:

1. **Confirmed status, not raw noise.** A monitor turns `down` only after consecutive failures (default 2) and back `up` after consecutive successes, while the last raw check result stays visible as "cek gagal, menunggu konfirmasi". This is the anti-false-alarm mechanism and it shapes the whole UI vocabulary.
2. **Incident accounting that survives data pruning.** Incidents open at the first failure of a run, close at the first success of the recovery, and daily rollups are kept permanently after 30 days of raw data are pruned. The report does not lose history when raw checks expire.

## Operating Context

- Self-hosted: PHP 8.3+, Composer, Node for asset build, SQLite by default with MySQL/PostgreSQL supported. Deployable to Wasmer Edge (`app.yaml`, `wasmer.toml`), where a managed MySQL and the `php/php-eh` runtime replace local defaults.
- Runtime is split: a scheduler enqueues due checks every minute (`monitors:dispatch`) and a queue worker executes them (`CheckMonitorJob`). No worker means no checks, even with the scheduler running — the UI is expected to make "last checked" legible for that reason.
- Daily jobs: rollup at 00:15, raw-data prune at 02:00.
- Time is stored in UTC and displayed in `Asia/Jakarta` (`MONITOR_DISPLAY_TIMEZONE`), with Indonesian date and duration formatting (`11 Nov 2026 14:05`, `2 jam 15 menit`).
- Reports are filtered by server, monitor, and date range (default 7 days, max 90), exported as CSV, and printable through the browser's print view.
- Optional email alerts via `MAIL_*` + `MONITOR_ALERT_EMAIL`; in-app notification is the always-on channel.
- Development and evaluation use `DemoDataSeeder` to fill 72 hours of history.

## Capabilities and Constraints

**Canonical vocabulary** (`GLOSSARY.md`, binding for UI copy): Monitor · Jenis Monitor (`http`, `port`, `service`) · Pemeriksaan · Hasil Pemeriksaan · Status Monitor (`up`, `down`, `unknown`) · Ambang Konfirmasi · Ambang Pemulihan · Insiden · Uptime · Downtime · Server · Layanan · Masa Berlaku SSL · Rollup Harian · Pemeriksaan Eksternal · Agent. Avoided synonyms are listed there and should not appear in the interface.

**Rules that bind the interface**

- A monitor that has never been checked is `unknown`, never assumed healthy.
- Last check outcome stays visible even when it has not yet flipped the confirmed status.
- Uptime is successful ÷ total checks in the range; with no data it renders `—`, never `0%` or `100%`.
- Response statistics (average, p95, maximum) count successful checks only.
- SSL warning tiers: critical ≤ 7 days, warning ≤ 14, attention ≤ 30.
- Exactly one open incident per monitor; incident start is the first failure, not the confirmation moment.

**Scope boundaries**

- In MVP: auth, monitor/server CRUD, scheduling, monitor detail with 24h/7d/30d uptime and response chart, dashboard, incidents, reports, CSV, print, retention + rollup, in-app and optional email alerts.
- Out of MVP: multi-tenant, billing, layered roles, internal server metrics UI, Slack/Telegram/webhook, on-call escalation, public status page, distributed probing, contractual SLA, server-side PDF, public API.

**Technical constraints**

- Laravel 13 + Blade (server-rendered), Tailwind CSS v4 (`@theme` in CSS, no JS config), Alpine.js, Chart.js.
- UI language is Indonesian throughout; no i18n switcher is part of the product.
- Dashboard target: under 1.5 s for 50 monitors × 30 days of raw data; queries indexed on `(monitor_id, checked_at)`.
- One failing check must not fail other jobs; every exception is recorded as a `down` result.
- Every page requires a session; CSRF on; agent tokens stored hashed and compared safely; login attempts throttled.

## Brand Commitments

- Name: **Pantau** (Indonesian: to watch, to keep an eye on). Product subtitle in use: "Monitoring". The mark is a signal/pulse line inside a rounded tile, rendered in teal.
- Voice: Indonesian, direct, operational, unhurried. It states facts and times ("Berlangsung 2 jam 15 menit", "Diperbarui 12 Nov 2026 09:41") rather than adjectives. Error text names the failure and the next step, never blames the user.
- Status is never carried by color alone — every state has a text label beside its dot or badge.
- No marketing claims, customer logos, testimonials, or benchmarks exist for this product, and none may be invented.

## Evidence on Hand

- `docs/PRD.md` — approved MVP requirements, personas, domain rules, acceptance criteria.
- `GLOSSARY.md` — canonical domain terminology.
- `docs/adr/0001`–`0006` — architecture decisions (single monitors table, one-minute tick + queue, status confirmation and incident timing, retention and rollups, …).
- `docs/TASKS.md`, `docs/DEPLOYMENT.md`, `docs/DEPLOY_WASMER.md` — delivery and operations.
- `database/seeders/DemoDataSeeder` — 72 hours of realistic demo history for evaluating the interface.
- Working application code: controllers, Blade views, Tailwind tokens, Chart.js configuration.
- Absent: real production screenshots, user research, traffic or reliability statistics, brand guidelines beyond the above.

## Product Principles

1. **Confirmed truth over raw signal.** Show what is known, say how it was confirmed, and keep the unconfirmed evidence visible beside it.
2. **The last hour is the job.** An operator opens Pantau to answer "is anything wrong right now?" The dashboard and monitor detail must answer that before anything else on the page.
3. **Every number is traceable.** Uptime, response time, and incident duration always come from stored checks with a stated range; an empty range says so instead of showing a flattering zero.
4. **Quiet until it matters.** Healthy state is calm and low-contrast; alarm state earns the attention with a single, unmistakable treatment. No decorative urgency.
5. **Self-hosted means legible.** The operator also runs the thing: timestamps, retention, and "last checked" are part of the product, not debug output.

## Accessibility & Inclusion

- Contrast must be sufficient for text at its actual size; the PRD states this as a requirement rather than an aspiration.
- Status is encoded redundantly: text label + dot/badge, never hue alone.
- Keyboard operation is expected throughout: visible `:focus-visible` ring, skip link to content, dialog semantics and Escape handling for the off-canvas navigation.
- `prefers-reduced-motion` is honored globally.
- Touch targets sit at a 44px minimum (`.btn`, `.input`, nav rows use `min-h-11`).
