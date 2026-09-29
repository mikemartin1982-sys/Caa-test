# CAA changes to the Grok-generated app — sign-in

- Staff sign in with their Compliance Engine username/password (same as Chart Recorder).
  `src/lib/auth/engine-login.server.ts` verifies against `GET {COMPLIANCE_ENGINE_URL}/api/v1/auth/me`
  (HTTP Basic), then creates/links a local Better Auth user and issues the normal session cookie.
- Removed: Grok/Google/X sign-in, the Grok gate identity plugin, the sign-in popup handler.
- Closed: public email sign-up (`emailAndPasswordEnabled = false`).
- Added: 5 attempts/minute/IP limit on `/api/auth/engine-login`.
- Removed: third-party `grok.com/grok-app-builder/extensions.js` script injection.
- Known: `scripts/grok-pwa-plugin.test.mjs` still asserts the removed script (expected failure).
- Known: disabling an engine account does not end that person's existing Desk session
  (Better Auth default ~7 days). Remove their local session row to force it.
- Known: the fallback email for engine users is `<username>@staff.invalid` unless /auth/me returns one.

# CAA changes — shared team book (migration 0003)

- One shared book for all staff. `user_id` is now `created_by` (audit only, never a filter).
  Migration `0003_shared_team.sql` upgrades existing data in place (tested on old per-user rows).
- Inquiries gain: `assigned_to`, `updated_by`, `source` (manual|email), `source_message_id`
  (unique -> an email can only be imported once), `source_mailbox`, `from_address`, `received_at`.
  These are the hooks for client@ mailbox intake. The website form mails from a system sender
  (currently registrar@), so the real customer must be parsed from the message body, not From.
- Templates are unique by slug and seeded once for the team.
- Demo data (fake companies/inquiries) no longer seeds by default. `CAA_DESK_DEMO_SEED=true`
  enables it for local demos only. NEVER in production.
- New screens/functions: assign an inquiry (dropdown + "Assign to me"), "last touched by",
  author + date on the activity log, `listStaff`, `assignInquiry`.
- Fixed an original bug: inquiry and client detail pages never rendered (they were nested under
  the list pages with no Outlet). Lists are now `inquiries.index.tsx` / `clients.index.tsx`.
- Not done yet: reading client@ mail (intake), outbound send without Grok's Outlook connector,
  public website form endpoint, roles/permissions beyond "any signed-in engine staff".

# CAA changes — website-form email intake (migration 0004)

- `src/lib/crm/intake-parse.ts`: parses the registrar@ notification emails. The customer is read
  from the form fields (Company, Primary Contact, Email, Phone, address, consent flags), never
  from the From line. All fields are kept as written in `inquiries.form_data`.
- `src/lib/crm/intake.ts`: files it. Idempotent on Message-ID; reuses company (by name) and
  contact (by email); fills blanks only, never overwrites staff edits; consent flags
  (Newsletter / Class Confirms / Cert Reminders) live on the contact and only change when the
  customer answers them again.
- "Paste email" button on Inquiries: the manual path until the mailbox reader exists.
- Five inquiry types: New Client Account Request, VR Client Account Request, VR Inquiry,
  Private Smoke School, Notice of Violation. Only the New Client Account subject has been seen
  for real; the other four are matched by keyword, so send real samples to tighten them.
- Fixed a bug from the shared-team change: the client (company) detail page threw a SQL error.
- Tests: `npm test` now includes the parser and importer tests (importer runs on real Postgres
  via PGlite with the real migrations). Test data uses invented customers only.
- Not done: automatic mailbox reading (IMAP), and replies sent as the individual staffer.

# CAA changes — replies from the staffer's own mail app (migration 0005)

- Decision: no mailbox reader for now. The team already reads client@ in Outlook, so intake is
  "Paste email" and replies are sent from each staffer's own mailbox.
- Reply card: pick a template -> "Open in my mail app" (mailto: to the customer, client@ copied,
  CRLF line breaks) -> send it -> "I sent it — mark as sent". The Desk records who sent it and when,
  logs it on the inquiry, and touches "last updated by".
- Replies page (was Outbound): unsent drafts have Open-in-mail-app + Mark-as-sent; sent ones show who sent.
- Message states are now just draft / sent (old queued/failed rows are converted to draft).
- Removed: Grok Outlook connector send + probe, the connector library (src/lib/app-data),
  and the Grok preview-iframe bridge. Settings page is now a short "How email works" help page.
- Upgrade path if IT agrees to "Send As" on the shared mailbox: add a server-side SMTP/Graph send
  that sets From to the staffer and reuses markMessageSent, so nothing else changes.
- Deploy note: the build target is still the Vercel preset. For the VPS, switch nitro to the
  node-server preset and run it behind Nginx (planned next).

## Phone install + hosting
- Replaced Grok PWA scaffolding with CAA `manifest.webmanifest`, icons (`public/icons/`), and a no-cache service worker (`public/sw.js`); iOS meta tags added in `__root.tsx`.
- Removed Grok install page/middleware, og assets, and platform scripts (brand-check, preview, write-atomic, browser-smoke, grok-pwa).
- Build target changed from `vercel` to `node-server` (run `node .output/server/index.mjs`).
- `deploy/`: systemd unit, Nginx site, env template, update script, VPS runbook, staff phone guide.
- Verified: production build on real Postgres 17 (migrations 0001-0005, re-run idempotent), login 13/13, shared book 11/11, manifest valid, service worker registers, no installability errors.
