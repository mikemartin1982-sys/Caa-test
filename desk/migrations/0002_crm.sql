-- CAA Desk CRM schema. All rows are scoped to the signed-in staffer.
create table if not exists companies (
  id          serial primary key,
  user_id     text not null,
  name        text not null,
  industry    text not null default '',
  city        text not null default '',
  state       text not null default '',
  notes       text not null default '',
  created_at  timestamptz not null default now()
);
create index if not exists companies_user_id_idx on companies (user_id);

create table if not exists contacts (
  id              serial primary key,
  user_id         text not null,
  company_id      integer references companies (id) on delete set null,
  name            text not null,
  email           text not null default '',
  phone           text not null default '',
  role            text not null default '',
  cert_expires_on date,
  source          text not null default '',
  created_at      timestamptz not null default now()
);
create index if not exists contacts_user_id_idx on contacts (user_id);
create index if not exists contacts_company_id_idx on contacts (company_id);

create table if not exists schools (
  id          serial primary key,
  user_id     text not null,
  kind        text not null,
  title       text not null,
  city        text not null default '',
  state       text not null default '',
  starts_on   date,
  ends_on     date,
  seats       integer not null default 0,
  enrolled    integer not null default 0,
  instructor  text not null default '',
  status      text not null default 'open',
  notes       text not null default '',
  created_at  timestamptz not null default now()
);
create index if not exists schools_user_id_idx on schools (user_id);

create table if not exists inquiries (
  id          serial primary key,
  user_id     text not null,
  contact_id  integer references contacts (id) on delete set null,
  company_id  integer references companies (id) on delete set null,
  school_id   integer references schools (id) on delete set null,
  kind        text not null,
  subject     text not null,
  body        text not null default '',
  status      text not null default 'new',
  source_path text not null default '',
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now()
);
create index if not exists inquiries_user_id_idx on inquiries (user_id);
create index if not exists inquiries_status_idx on inquiries (user_id, status);

create table if not exists templates (
  id          serial primary key,
  user_id     text not null,
  slug        text not null,
  name        text not null,
  kind        text not null,
  subject     text not null,
  body        text not null,
  created_at  timestamptz not null default now(),
  unique (user_id, slug)
);
create index if not exists templates_user_id_idx on templates (user_id);

create table if not exists messages (
  id           serial primary key,
  user_id      text not null,
  contact_id   integer references contacts (id) on delete set null,
  inquiry_id   integer references inquiries (id) on delete set null,
  template_id  integer references templates (id) on delete set null,
  to_email     text not null,
  subject      text not null,
  body         text not null,
  status       text not null default 'draft',
  error        text not null default '',
  sent_at      timestamptz,
  created_at   timestamptz not null default now()
);
create index if not exists messages_user_id_idx on messages (user_id);

create table if not exists activities (
  id          serial primary key,
  user_id     text not null,
  contact_id  integer references contacts (id) on delete set null,
  inquiry_id  integer references inquiries (id) on delete set null,
  kind        text not null,
  body        text not null default '',
  created_at  timestamptz not null default now()
);
create index if not exists activities_user_id_idx on activities (user_id);
