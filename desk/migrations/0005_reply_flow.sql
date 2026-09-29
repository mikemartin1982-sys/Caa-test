-- Replies are sent from each staffer's own mail app; the Desk records who sent.
alter table messages add column if not exists sent_by text;
-- The old "queued for Outlook" / "failed" states no longer exist.
update messages set status = 'draft', error = '' where status in ('queued', 'failed');
