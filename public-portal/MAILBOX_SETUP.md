# Client Mailbox integration

## What is included

The staff dashboard and Emails sidebar link to `/admin/mailbox`. Mailbox
routes use the existing `auth:staff` session. All authenticated staff can
read and reply; only Compliance Administrators can manage saved replies.
Staff IDs and display names are recorded from the Compliance Engine;
mailbox tables do not reference Laravel's unrelated `users` table.

The Cloudflare template library is linked from the dashboard, sidebar,
and inbox. Automatic import is **not connected**: the library currently
requires Cloudflare Access authentication. Obtain its Worker source and
template content/export to implement its actual data contract. Do not
embed Cloudflare credentials in client-side code. Local saved replies
are a separate, optional facility, not a copy of the Cloudflare library.

## Deployment in the assembled Laravel application

This repository contains a portal overlay, not a complete bootable Laravel
application. Merge it into the existing Laravel installation as usual.
Keep its existing staff guard, service provider, and bootstrap configuration.

1. Configure the portal's database for mailbox persistence. This feature
   stores message copies, saved replies, and send history in Laravel. This
   is a bounded exception to the portal's existing API-only business-data
   approach; it does not relocate clients, enrollments, or staff identities.
2. Run the five `2026_09_19_*` migrations from
   `public-portal/database/migrations` with `php artisan migrate` in the
   assembled application. These are fresh-install migrations. If the ZIP's
   original migrations have already run anywhere, do not run or edit them
   in place: prepare an additive schema/data migration first. The three
   pre-existing untracked PHP files in repository-level `db/migrations`
   were left untouched and are not the corrected installation source.
3. Configure `GRAPH_TENANT_ID`, `GRAPH_CLIENT_ID`, `GRAPH_CLIENT_SECRET`,
   `GRAPH_MAILBOX`, and `GRAPH_INBOX_FOLDER` on the server. Use one mailbox
   per installation. Do not commit secrets.
4. Choose the Microsoft authentication mode. For the new sign-in flow,
   follow **Delegated Microsoft connection** below. The existing
   `GRAPH_AUTH_MODE=application` mode remains available with mailbox-scoped
   application `Mail.Read` / `Mail.Send` permissions through Exchange
   Application RBAC. Do not combine the two permission setups accidentally.
5. Set `MAILBOX_ENABLED=true` only when configuration and migrations are
   ready, and refresh cached Laravel configuration. The default is false.
6. Merge `routes/console.php` into the existing scheduler configuration;
   retain other scheduled tasks. Laravel's scheduler must already run
   every minute. Ensure `FetchMailboxMessages` is discoverable under
   `app/Console/Commands`. Use a shared lock-capable cache for multiple
   application hosts (for example Redis).
7. Run `php artisan mailbox:fetch` against a designated test mailbox,
   then verify the staff inbox and a reply received by a test recipient.
   No live credentials, production migration, or real email send was used
   during development.

## Import and send behavior

- First synchronization imports the existing contents of the selected
  inbox, including read mail. Application mode uses delta checkpoints.
  Delegated mode uses paginated ordinary message reads, because shared
  delegated delta support must not be assumed. It saves unfinished page
  cursors and starts a new complete inbox scan after finishing each pass.
  This favors compatibility over efficiency for large inboxes; no read
  flags are changed. Mail arriving during a scan is caught on a later pass.
- Checkpoints commit with each imported page. Large initial imports
  continue on subsequent runs. Existing CRM statuses are preserved.
- Deleted or moved messages retain their CRM copy. A message moved out
  before its first import may not be captured by this inbox-only sync.
- Immutable Microsoft IDs reduce breakage when imported mail is moved.
  Incoming follow-ups are currently individual inbox records, not a
  combined conversation view. Attachments and assignment UI are not included.
- Email is displayed as escaped plain text. HTML formatting and remote
  tracking images are not rendered.
- Each reply form has a unique request ID reserved before contacting
  Microsoft. Repeated submission of that form will not send twice.
- `accepted` means Graph accepted the request, not confirmed delivery.
  `unknown` or a lingering `sending` entry requires checking Sent Items
  before composing another reply. No automatic send retry is performed.
- Import failures return a failing command exit code. Expired delta
  checkpoints are cleared so the next run can rebuild them. Monitor the
  scheduler/application logs for failures.

## Verification

From `public-portal`, run:

```text
php vendor/bin/phpunit tests/MailboxTest.php --no-coverage
```

Tests bootstrap the installed Laravel framework with a temporary runtime,
an in-memory SQLite database, and fake Microsoft responses. They verify
staff access, escaped email output, pagination and checkpoints, replay
deduplication, send uncertainty, template permissions, and dashboard rendering.

Microsoft references:
- https://learn.microsoft.com/en-us/graph/api/message-delta
- https://learn.microsoft.com/en-us/graph/api/message-reply
- https://learn.microsoft.com/en-us/exchange/permissions-exo/application-rbac

## Delegated Microsoft connection

Existing installations retain application mode until `GRAPH_AUTH_MODE`
is explicitly changed. No live migrations or environment changes were
performed during this development work.

1. Create a single-tenant Microsoft Entra application, or use an appropriate
   existing registration. Add the **Web** redirect URI:
   `https://YOUR-PORTAL-DOMAIN/admin/mailbox/microsoft/callback`.
   Substitute the actual portal hostname. The path is intentionally different
   from the ZIP's old `/oauth/microsoft/callback` path.
2. Request **delegated** Microsoft Graph `Mail.Read.Shared` and
   `Mail.Send.Shared`, plus `offline_access`. This implementation does not
   request `Mail.ReadWrite.Shared` because it does not mark messages read.
   Organizational registration/consent policies may require admin approval.
3. Set `GRAPH_AUTH_MODE=delegated`, `GRAPH_TENANT_ID`, `GRAPH_CLIENT_ID`,
   `GRAPH_CLIENT_SECRET`, `GRAPH_REDIRECT_URI` (exactly the registered URI),
   and `GRAPH_MAILBOX`. Continue using the existing `GRAPH_*` settings;
   the ZIP's separate `MSGRAPH_*` settings are not used here.
4. Once ready, install the pending mailbox migrations. Migration `000005`
   adds encrypted token storage and is additive to our four prior migrations.
   Keep the existing Laravel `APP_KEY` secure and stable: it encrypts tokens.
   Use a shared lock-capable cache on every application host to serialize
   token refreshes and reconnects. Keep `MAILBOX_ENABLED=false` during setup.
5. Sign in to the staff dashboard as a Compliance Administrator. Open
   **Microsoft mailbox connection**, then **Connect Microsoft**. Sign in
   with a Microsoft account that has Full Access and Send As rights on the
   target mailbox. That Microsoft account can differ from the staff login.
6. The callback checks state, expiry, staff identity and PKCE, verifies
   granted scopes, and performs a read-only check of the configured mailbox.
   Tokens are encrypted before storage. A successful connection verifies
   reading only; Exchange Send As rights still require an end-to-end test.
7. Enable `MAILBOX_ENABLED=true` only when ready for mailbox importing and
   sending. Refresh cached config, import a test message, and reply to a
   designated test recipient. Check actual delivery and Sent Items.

Connection setup is deliberately reachable while mailbox operations are
switched off. Before the token migration is installed, it displays a setup
message instead of starting authorization. A refresh-token revocation marks
this connection as needing reconnection; transient refresh failures do not
silently erase working authorization. Microsoft can require sign-in again
according to tenant policy. A failed reconnect leaves previous credentials
intact. The connection page displays saved authorization status, not a
continuous guarantee that Microsoft will accept future requests.

The callback performs no automatic email send. No IMAP/app-password fallback
was installed. Sample fixture messages from the ZIP were not imported.

Reference: https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-auth-code-flow
