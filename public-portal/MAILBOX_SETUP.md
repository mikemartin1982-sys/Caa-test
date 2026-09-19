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
2. Run the four `2026_09_19_*` migrations from
   `public-portal/database/migrations` with `php artisan migrate` in the
   assembled application. These are fresh-install migrations. If the ZIP's
   original migrations have already run anywhere, do not run or edit them
   in place: prepare an additive schema/data migration first. The three
   pre-existing untracked PHP files in repository-level `db/migrations`
   were left untouched and are not the corrected installation source.
3. Configure `GRAPH_TENANT_ID`, `GRAPH_CLIENT_ID`, `GRAPH_CLIENT_SECRET`,
   `GRAPH_MAILBOX`, and `GRAPH_INBOX_FOLDER` on the server. Use one mailbox
   per installation. Do not commit secrets.
4. Grant mailbox-scoped application read and send access using the
   organization's Microsoft Entra/Exchange configuration. Graph operations
   here require `Mail.Read` and `Mail.Send`; this implementation does not
   mark mail read and does not require `Mail.ReadWrite`. Prefer Exchange
   Application RBAC for mailbox scoping. Existing tenant-wide Entra grants
   must also be reviewed because permissions can be additive.
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
  inbox, including read mail. Later runs use saved delta checkpoints.
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
