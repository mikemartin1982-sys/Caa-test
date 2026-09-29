-- CAA Desk: one shared team book instead of one book per staffer.
--
-- user_id becomes created_by (audit only, never a filter). Inquiries gain an
-- assignee, a last-updated-by, and the fields needed for email intake from the
-- shared client@ mailbox (the website form mails from a system sender such as
-- registrar@, so the real customer is parsed from the message, and the
-- Message-ID is the dedupe key).

alter table companies  rename column user_id to created_by;
alter table contacts   rename column user_id to created_by;
alter table schools    rename column user_id to created_by;
alter table inquiries  rename column user_id to created_by;
alter table templates  rename column user_id to created_by;
alter table messages   rename column user_id to created_by;
alter table activities rename column user_id to created_by;

drop index if exists companies_user_id_idx;
drop index if exists contacts_user_id_idx;
drop index if exists schools_user_id_idx;
drop index if exists inquiries_user_id_idx;
drop index if exists inquiries_status_idx;
drop index if exists templates_user_id_idx;
drop index if exists messages_user_id_idx;
drop index if exists activities_user_id_idx;

-- Templates were unique per (user, slug); they are now unique per slug.
delete from templates t using templates k where t.slug = k.slug and t.id > k.id;
alter table templates drop constraint if exists templates_user_id_slug_key;
create unique index if not exists templates_slug_uidx on templates (slug);

alter table inquiries add column if not exists assigned_to text;
alter table inquiries add column if not exists updated_by text;
alter table inquiries add column if not exists source text not null default 'manual';
alter table inquiries add column if not exists source_message_id text;
alter table inquiries add column if not exists source_mailbox text not null default '';
alter table inquiries add column if not exists from_address text not null default '';
alter table inquiries add column if not exists received_at timestamptz;

-- One inquiry per mailbox message, ever (safe to re-run an import).
create unique index if not exists inquiries_source_message_uidx
  on inquiries (source_message_id) where source_message_id is not null;

create index if not exists inquiries_status_idx on inquiries (status);
create index if not exists inquiries_assigned_to_idx on inquiries (assigned_to);
create index if not exists contacts_email_idx on contacts (lower(email));
create index if not exists messages_inquiry_idx on messages (inquiry_id);
create index if not exists activities_inquiry_idx on activities (inquiry_id);
