-- Fields captured from the website form emails (registrar -> client@).
alter table companies add column if not exists address text not null default '';
alter table companies add column if not exists zip text not null default '';

-- Communication preferences the customer ticked on the form.
-- NULL = never told us; true/false = they answered.
alter table contacts add column if not exists newsletter boolean;
alter table contacts add column if not exists class_confirms boolean;
alter table contacts add column if not exists cert_reminders boolean;

alter table inquiries add column if not exists how_heard text not null default '';
-- Every label/value pair from the form, as written, so nothing is lost even
-- when a form type has fields we do not model yet.
alter table inquiries add column if not exists form_data jsonb not null default '{}'::jsonb;
