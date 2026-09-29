import { createServerFn } from "@tanstack/react-start";
import { z } from "zod";
import { authMiddleware } from "@/lib/auth/middleware";
import { getSql } from "@/lib/db";
import { importParsedInquiry } from "./intake";
import { parseInquiryEmail, parsePastedEmail } from "./intake-parse";
import { applyMerge, mergeContext } from "./merge";
import { seedDeskIfEmpty } from "./seed";
import { SOURCE_PATHS } from "./labels";
import type {
  Activity,
  Company,
  Contact,
  DeskSummary,
  Inquiry,
  InquiryKind,
  InquiryStatus,
  Message,
  School,
  Template,
} from "./types";

const inquiryKind = z.enum([
  "new_client_account",
  "vr_client_account",
  "vr_school",
  "in_person",
  "private_onsite",
  "veo_nov",
  "compliance_plan",
  "readings",
  "general",
]);

const inquiryStatus = z.enum([
  "new",
  "qualified",
  "scheduled",
  "waiting",
  "won",
  "lost",
]);

async function withSeed(userId: string) {
  const sql = await getSql();
  await seedDeskIfEmpty(sql, userId);
  return sql;
}

function iso(value: unknown): string {
  if (value == null) return "";
  if (typeof value === "string") return value;
  if (value instanceof Date) return value.toISOString();
  return String(value);
}

function isoOrNull(value: unknown): string | null {
  if (value == null || value === "") return null;
  const s = iso(value);
  return s || null;
}

function num(value: unknown): number {
  return typeof value === "number" ? value : Number(value ?? 0);
}

export const loadDesk = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await withSeed(context.userId);
    const summaryRows = await sql<{
      open_inquiries: number;
      recerts_due: number;
      recerts_expired: number;
      upcoming_schools: number;
      queued_mail: number;
      won_this_month: number;
    }>`
      select
        (select count(*)::int from inquiries
          where status in ('new','qualified','scheduled','waiting')) as open_inquiries,
        (select count(*)::int from contacts
          where cert_expires_on is not null
            and cert_expires_on >= current_date
            and cert_expires_on <= current_date + interval '45 days') as recerts_due,
        (select count(*)::int from contacts
          where cert_expires_on is not null
            and cert_expires_on < current_date) as recerts_expired,
        (select count(*)::int from schools
          where status <> 'closed'
            and (starts_on is null or starts_on >= current_date)) as upcoming_schools,
        (select count(*)::int from messages
          where status = 'draft') as queued_mail,
        (select count(*)::int from inquiries
          where status = 'won'
            and created_at >= date_trunc('month', current_date)) as won_this_month
    `;
    const summary: DeskSummary = {
      openInquiries: num(summaryRows[0]?.open_inquiries),
      recertsDue: num(summaryRows[0]?.recerts_due),
      recertsExpired: num(summaryRows[0]?.recerts_expired),
      upcomingSchools: num(summaryRows[0]?.upcoming_schools),
      queuedMail: num(summaryRows[0]?.queued_mail),
      wonThisMonth: num(summaryRows[0]?.won_this_month),
    };

    const inquiries = await listInquiryRows(sql, 8);
    const recerts = await sql<{
      id: number;
      name: string;
      email: string;
      company_name: string | null;
      cert_expires_on: string | null;
    }>`
      select c.id, c.name, c.email, co.name as company_name, c.cert_expires_on::text
      from contacts c
      left join companies co on co.id = c.company_id
      where c.cert_expires_on is not null
        and c.cert_expires_on <= current_date + interval '60 days'
      order by c.cert_expires_on asc
      limit 8
    `;
    const schools = await sql<{
      id: number;
      kind: string;
      title: string;
      city: string;
      state: string;
      starts_on: string | null;
      enrolled: number;
      seats: number;
      instructor: string;
      status: string;
    }>`
      select id, kind, title, city, state, starts_on::text, enrolled, seats, instructor, status
      from schools
      order by starts_on nulls last, id
      limit 6
    `;

    return {
      summary,
      inquiries,
      recerts: recerts.map((r) => ({
        id: r.id,
        name: r.name,
        email: r.email,
        companyName: r.company_name,
        certExpiresOn: isoOrNull(r.cert_expires_on),
      })),
      schools: schools.map((s) => ({
        id: s.id,
        kind: s.kind,
        title: s.title,
        city: s.city,
        state: s.state,
        startsOn: isoOrNull(s.starts_on),
        enrolled: num(s.enrolled),
        seats: num(s.seats),
        instructor: s.instructor,
        status: s.status,
      })),
    };
  });

async function listInquiryRows(
  sql: Awaited<ReturnType<typeof getSql>>,
  limit?: number,
): Promise<Inquiry[]> {
  const rows = await sql<{
    id: number;
    contact_id: number | null;
    company_id: number | null;
    school_id: number | null;
    kind: string;
    subject: string;
    body: string;
    status: string;
    source_path: string;
    created_at: string;
    updated_at: string;
    contact_name: string | null;
    contact_email: string | null;
    company_name: string | null;
    school_title: string | null;
    assigned_to: string | null;
    assigned_to_name: string | null;
    created_by: string | null;
    updated_by_name: string | null;
    source: string;
    from_address: string;
    received_at: string | null;
  }>`
    select
      i.id, i.contact_id, i.company_id, i.school_id, i.kind, i.subject, i.body,
      i.status, i.source_path, i.created_at::text, i.updated_at::text,
      c.name as contact_name, c.email as contact_email,
      co.name as company_name, s.title as school_title,
      i.assigned_to, au.name as assigned_to_name, i.created_by,
      uu.name as updated_by_name, i.source, i.from_address, i.received_at::text
    from inquiries i
    left join contacts c on c.id = i.contact_id
    left join companies co on co.id = i.company_id
    left join schools s on s.id = i.school_id
    left join "user" au on au.id = i.assigned_to
    left join "user" uu on uu.id = i.updated_by
    order by
      case i.status
        when 'new' then 0
        when 'qualified' then 1
        when 'waiting' then 2
        when 'scheduled' then 3
        else 4
      end,
      i.updated_at desc
    limit ${limit ?? 200}
  `;
  return rows.map(mapInquiry);
}

function mapInquiry(row: {
  id: number;
  contact_id: number | null;
  company_id: number | null;
  school_id: number | null;
  kind: string;
  subject: string;
  body: string;
  status: string;
  source_path: string;
  created_at: string;
  updated_at: string;
  contact_name: string | null;
  contact_email: string | null;
  company_name: string | null;
  school_title: string | null;
  assigned_to: string | null;
  assigned_to_name: string | null;
  created_by: string | null;
  updated_by_name: string | null;
  source: string;
  from_address: string;
  received_at: string | null;
}): Inquiry {
  return {
    id: row.id,
    contactId: row.contact_id,
    companyId: row.company_id,
    schoolId: row.school_id,
    kind: row.kind as InquiryKind,
    subject: row.subject,
    body: row.body,
    status: row.status as InquiryStatus,
    sourcePath: row.source_path,
    createdAt: iso(row.created_at),
    updatedAt: iso(row.updated_at),
    contactName: row.contact_name,
    contactEmail: row.contact_email,
    companyName: row.company_name,
    schoolTitle: row.school_title,
    assignedTo: row.assigned_to,
    assignedToName: row.assigned_to_name,
    createdBy: row.created_by,
    updatedByName: row.updated_by_name,
    source: row.source,
    fromAddress: row.from_address,
    receivedAt: row.received_at ? iso(row.received_at) : null,
  };
}

export const listInquiries = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await withSeed(context.userId);
    return listInquiryRows(sql);
  });

export const getInquiry = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .validator(z.object({ id: z.number() }))
  .handler(async ({ context, data }) => {
    const sql = await withSeed(context.userId);
    const rows = await sql<{
      id: number;
      contact_id: number | null;
      company_id: number | null;
      school_id: number | null;
      kind: string;
      subject: string;
      body: string;
      status: string;
      source_path: string;
      created_at: string;
      updated_at: string;
      contact_name: string | null;
      contact_email: string | null;
      company_name: string | null;
      school_title: string | null;
      assigned_to: string | null;
      assigned_to_name: string | null;
      created_by: string | null;
      updated_by_name: string | null;
      source: string;
      from_address: string;
      received_at: string | null;
    }>`
      select
        i.id, i.contact_id, i.company_id, i.school_id, i.kind, i.subject, i.body,
        i.status, i.source_path, i.created_at::text, i.updated_at::text,
        c.name as contact_name, c.email as contact_email,
        co.name as company_name, s.title as school_title,
        i.assigned_to, au.name as assigned_to_name, i.created_by,
        uu.name as updated_by_name, i.source, i.from_address, i.received_at::text
      from inquiries i
      left join contacts c on c.id = i.contact_id
      left join companies co on co.id = i.company_id
      left join schools s on s.id = i.school_id
      left join "user" au on au.id = i.assigned_to
      left join "user" uu on uu.id = i.updated_by
      where i.id = ${data.id}
      limit 1
    `;
    const inquiry = rows[0] ? mapInquiry(rows[0]) : null;
    if (!inquiry) return { inquiry: null, contact: null, school: null, activities: [], templates: [], messages: [] };

    let contact: Contact | null = null;
    if (inquiry.contactId) {
      const cRows = await sql<{
        id: number;
        company_id: number | null;
        name: string;
        email: string;
        phone: string;
        role: string;
        cert_expires_on: string | null;
        source: string;
        created_at: string;
        company_name: string | null;
      }>`
        select c.id, c.company_id, c.name, c.email, c.phone, c.role,
          c.cert_expires_on::text, c.source, c.created_at::text, co.name as company_name
        from contacts c
        left join companies co on co.id = c.company_id
        where c.id = ${inquiry.contactId}
      `;
      const c = cRows[0];
      if (c) {
        contact = {
          id: c.id,
          companyId: c.company_id,
          companyName: c.company_name,
          name: c.name,
          email: c.email,
          phone: c.phone,
          role: c.role,
          certExpiresOn: isoOrNull(c.cert_expires_on),
          source: c.source,
          createdAt: iso(c.created_at),
        };
      }
    }

    let school: School | null = null;
    if (inquiry.schoolId) {
      const sRows = await sql<{
        id: number;
        kind: string;
        title: string;
        city: string;
        state: string;
        starts_on: string | null;
        ends_on: string | null;
        seats: number;
        enrolled: number;
        instructor: string;
        status: string;
        notes: string;
      }>`
        select id, kind, title, city, state, starts_on::text, ends_on::text,
          seats, enrolled, instructor, status, notes
        from schools
        where id = ${inquiry.schoolId}
      `;
      const s = sRows[0];
      if (s) school = mapSchool(s);
    }

    const activities = await listActivityRows(sql, inquiry.id);
    const templates = await listTemplateRows(sql);
    const messages = await listMessageRows(sql, inquiry.id);
    return { inquiry, contact, school, activities, templates, messages };
  });

function mapSchool(s: {
  id: number;
  kind: string;
  title: string;
  city: string;
  state: string;
  starts_on: string | null;
  ends_on: string | null;
  seats: number;
  enrolled: number;
  instructor: string;
  status: string;
  notes: string;
}): School {
  return {
    id: s.id,
    kind: s.kind as School["kind"],
    title: s.title,
    city: s.city,
    state: s.state,
    startsOn: isoOrNull(s.starts_on),
    endsOn: isoOrNull(s.ends_on),
    seats: num(s.seats),
    enrolled: num(s.enrolled),
    instructor: s.instructor,
    status: s.status,
    notes: s.notes,
  };
}

async function listActivityRows(
  sql: Awaited<ReturnType<typeof getSql>>,
  inquiryId?: number,
  contactId?: number,
): Promise<Activity[]> {
  const rows = await sql<{
    id: number;
    contact_id: number | null;
    inquiry_id: number | null;
    kind: string;
    body: string;
    created_at: string;
    author_name: string | null;
  }>`
    select a.id, a.contact_id, a.inquiry_id, a.kind, a.body, a.created_at::text,
      u.name as author_name
    from activities a
    left join "user" u on u.id = a.created_by
    where (${inquiryId ?? 0} = 0 or a.inquiry_id = ${inquiryId ?? 0})
      and (${contactId ?? 0} = 0 or a.contact_id = ${contactId ?? 0})
    order by a.created_at desc
    limit 40
  `;
  return rows.map((r) => ({
    id: r.id,
    contactId: r.contact_id,
    inquiryId: r.inquiry_id,
    kind: r.kind,
    body: r.body,
    createdAt: iso(r.created_at),
    authorName: r.author_name,
  }));
}

async function listTemplateRows(
  sql: Awaited<ReturnType<typeof getSql>>,
): Promise<Template[]> {
  const rows = await sql<Template & { id: number }>`
    select id, slug, name, kind, subject, body from templates
    order by name
  `;
  return rows;
}

async function listMessageRows(
  sql: Awaited<ReturnType<typeof getSql>>,
  inquiryId?: number,
): Promise<Message[]> {
  const rows = await sql<{
    id: number;
    contact_id: number | null;
    inquiry_id: number | null;
    template_id: number | null;
    to_email: string;
    subject: string;
    body: string;
    status: string;
    error: string;
    sent_at: string | null;
    created_at: string;
    contact_name: string | null;
    sent_by_name: string | null;
  }>`
    select m.id, m.contact_id, m.inquiry_id, m.template_id, m.to_email, m.subject, m.body,
      m.status, m.error, m.sent_at::text, m.created_at::text, c.name as contact_name,
      u.name as sent_by_name
    from messages m
    left join contacts c on c.id = m.contact_id
    left join "user" u on u.id = m.sent_by
    where (${inquiryId ?? 0} = 0 or m.inquiry_id = ${inquiryId ?? 0})
    order by m.created_at desc
    limit 50
  `;
  return rows.map((r) => ({
    id: r.id,
    contactId: r.contact_id,
    inquiryId: r.inquiry_id,
    templateId: r.template_id,
    toEmail: r.to_email,
    subject: r.subject,
    body: r.body,
    status: r.status as Message["status"],
    error: r.error,
    sentAt: isoOrNull(r.sent_at),
    createdAt: iso(r.created_at),
    contactName: r.contact_name,
    sentByName: r.sent_by_name,
  }));
}

export const createInquiry = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      kind: inquiryKind,
      subject: z.string().min(1).max(200),
      body: z.string().max(4000),
      contactName: z.string().min(1).max(120),
      contactEmail: z.string().max(200),
      contactPhone: z.string().max(40).optional(),
      companyName: z.string().max(200),
      schoolId: z.number().nullable().optional(),
    }),
  )
  .handler(async ({ context, data }) => {
    const sql = await withSeed(context.userId);
    let companyId: number | null = null;
    const companyName = data.companyName.trim();
    if (companyName) {
      const found = await sql<{ id: number }>`
        select id from companies
        where lower(name) = ${companyName.toLowerCase()}
        limit 1
      `;
      if (found[0]) companyId = found[0].id;
      else {
        const created = await sql<{ id: number }>`
          insert into companies (created_by, name) values (${context.userId}, ${companyName})
          returning id
        `;
        companyId = created[0]?.id ?? null;
      }
    }

    const contactRows = await sql<{ id: number }>`
      insert into contacts (created_by, company_id, name, email, phone, source)
      values (
        ${context.userId},
        ${companyId},
        ${data.contactName.trim()},
        ${data.contactEmail.trim()},
        ${data.contactPhone?.trim() ?? ""},
        ${"caatest.tech intake"}
      )
      returning id
    `;
    const contactId = contactRows[0]?.id ?? null;
    const sourcePath = SOURCE_PATHS[data.kind];
    const inquiryRows = await sql<{ id: number }>`
      insert into inquiries (
        created_by, contact_id, company_id, school_id, kind, subject, body, status, source_path
      )
      values (
        ${context.userId},
        ${contactId},
        ${companyId},
        ${data.schoolId ?? null},
        ${data.kind},
        ${data.subject.trim()},
        ${data.body.trim()},
        ${"new"},
        ${sourcePath}
      )
      returning id
    `;
    const inquiryId = inquiryRows[0]?.id;
    await sql`
      insert into activities (created_by, contact_id, inquiry_id, kind, body)
      values (
        ${context.userId},
        ${contactId},
        ${inquiryId ?? null},
        ${"intake"},
        ${`Website intake from caatest.tech${sourcePath}`}
      )
    `;
    return { id: inquiryId ?? 0 };
  });

export const updateInquiryStatus = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ id: z.number(), status: inquiryStatus }))
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    await sql`
      update inquiries
      set status = ${data.status}, updated_at = now(), updated_by = ${context.userId}
      where id = ${data.id}
    `;
    await sql`
      insert into activities (created_by, inquiry_id, kind, body)
      values (
        ${context.userId},
        ${data.id},
        ${"status"},
        ${`Status set to ${data.status}`}
      )
    `;
  });

export const addNote = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      inquiryId: z.number().nullable(),
      contactId: z.number().nullable(),
      body: z.string().min(1).max(4000),
    }),
  )
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    await sql`
      insert into activities (created_by, contact_id, inquiry_id, kind, body)
      values (
        ${context.userId},
        ${data.contactId},
        ${data.inquiryId},
        ${"note"},
        ${data.body.trim()}
      )
    `;
  });

export const listCompanies = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await withSeed(context.userId);
    const rows = await sql<{
      id: number;
      name: string;
      industry: string;
      city: string;
      state: string;
      notes: string;
      created_at: string;
      contact_count: number;
    }>`
      select
        co.id, co.name, co.industry, co.city, co.state, co.notes, co.created_at::text,
        (select count(*)::int from contacts c where c.company_id = co.id) as contact_count
      from companies co
      order by co.name
    `;
    const companies: Company[] = rows.map((r) => ({
      id: r.id,
      name: r.name,
      industry: r.industry,
      city: r.city,
      state: r.state,
      notes: r.notes,
      createdAt: iso(r.created_at),
      contactCount: num(r.contact_count),
    }));
    return companies;
  });

export const getCompany = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .validator(z.object({ id: z.number() }))
  .handler(async ({ context, data }) => {
    const sql = await withSeed(context.userId);
    const companies = await sql<{
      id: number;
      name: string;
      industry: string;
      city: string;
      state: string;
      notes: string;
      created_at: string;
    }>`
      select id, name, industry, city, state, notes, created_at::text
      from companies
      where id = ${data.id}
      limit 1
    `;
    const row = companies[0];
    if (!row) return { company: null, contacts: [], inquiries: [] };
    const contacts = await sql<{
      id: number;
      company_id: number | null;
      name: string;
      email: string;
      phone: string;
      role: string;
      cert_expires_on: string | null;
      source: string;
      created_at: string;
    }>`
      select id, company_id, name, email, phone, role, cert_expires_on::text, source, created_at::text
      from contacts
      where company_id = ${data.id}
      order by name
    `;
    const inquiries = await sql<{
      id: number;
      contact_id: number | null;
      company_id: number | null;
      school_id: number | null;
      kind: string;
      subject: string;
      body: string;
      status: string;
      source_path: string;
      created_at: string;
      updated_at: string;
      contact_name: string | null;
      contact_email: string | null;
      company_name: string | null;
      school_title: string | null;
      assigned_to: string | null;
      assigned_to_name: string | null;
      created_by: string | null;
      updated_by_name: string | null;
      source: string;
      from_address: string;
      received_at: string | null;
    }>`
      select
        i.id, i.contact_id, i.company_id, i.school_id, i.kind, i.subject, i.body,
        i.status, i.source_path, i.created_at::text, i.updated_at::text,
        c.name as contact_name, c.email as contact_email,
        co.name as company_name, s.title as school_title,
        i.assigned_to, au.name as assigned_to_name, i.created_by,
        uu.name as updated_by_name, i.source, i.from_address, i.received_at::text
      from inquiries i
      left join contacts c on c.id = i.contact_id
      left join companies co on co.id = i.company_id
      left join schools s on s.id = i.school_id
      left join "user" au on au.id = i.assigned_to
      left join "user" uu on uu.id = i.updated_by
      where i.company_id = ${data.id}
      order by i.updated_at desc
    `;
    return {
      company: {
        id: row.id,
        name: row.name,
        industry: row.industry,
        city: row.city,
        state: row.state,
        notes: row.notes,
        createdAt: iso(row.created_at),
        contactCount: contacts.length,
      } satisfies Company,
      contacts: contacts.map((c) => ({
        id: c.id,
        companyId: c.company_id,
        companyName: row.name,
        name: c.name,
        email: c.email,
        phone: c.phone,
        role: c.role,
        certExpiresOn: isoOrNull(c.cert_expires_on),
        source: c.source,
        createdAt: iso(c.created_at),
      })) satisfies Contact[],
      inquiries: inquiries.map(mapInquiry),
    };
  });

export const listSchools = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await withSeed(context.userId);
    const rows = await sql<{
      id: number;
      kind: string;
      title: string;
      city: string;
      state: string;
      starts_on: string | null;
      ends_on: string | null;
      seats: number;
      enrolled: number;
      instructor: string;
      status: string;
      notes: string;
    }>`
      select id, kind, title, city, state, starts_on::text, ends_on::text,
        seats, enrolled, instructor, status, notes
      from schools
      order by starts_on nulls last, title
    `;
    return rows.map(mapSchool);
  });

export const listTemplates = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await withSeed(context.userId);
    return listTemplateRows(sql);
  });

export const saveTemplate = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      id: z.number(),
      name: z.string().min(1).max(120),
      subject: z.string().min(1).max(200),
      body: z.string().min(1).max(8000),
    }),
  )
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    await sql`
      update templates
      set name = ${data.name.trim()},
          subject = ${data.subject.trim()},
          body = ${data.body}
      where id = ${data.id}
    `;
  });

export const listMessages = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await withSeed(context.userId);
    return listMessageRows(sql);
  });

export const composeFromTemplate = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      inquiryId: z.number(),
      templateId: z.number(),
    }),
  )
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    const inquiryRows = await sql<{
      id: number;
      contact_id: number | null;
      school_id: number | null;
    }>`
      select id, contact_id, school_id from inquiries
      where id = ${data.inquiryId}
      limit 1
    `;
    const inquiry = inquiryRows[0];
    if (!inquiry) throw new Error("Inquiry not found");

    const templateRows = await sql<{
      id: number;
      subject: string;
      body: string;
    }>`
      select id, subject, body from templates
      where id = ${data.templateId}
      limit 1
    `;
    const template = templateRows[0];
    if (!template) throw new Error("Template not found");

    const contactRows = inquiry.contact_id
      ? await sql<{
          name: string;
          email: string;
          role: string;
          cert_expires_on: string | null;
          company_name: string | null;
        }>`
          select c.name, c.email, c.role, c.cert_expires_on::text, co.name as company_name
          from contacts c
          left join companies co on co.id = c.company_id
          where c.id = ${inquiry.contact_id}
        `
      : [];
    const contact = contactRows[0];
    if (!contact) throw new Error("Contact missing an email");

    const schoolRows = inquiry.school_id
      ? await sql<{
          title: string;
          city: string;
          state: string;
          starts_on: string | null;
          instructor: string;
        }>`
          select title, city, state, starts_on::text, instructor
          from schools
          where id = ${inquiry.school_id}
        `
      : [];
    const school = schoolRows[0];
    const ctx = mergeContext(
      {
        name: contact.name,
        email: contact.email,
        role: contact.role,
        certExpiresOn: isoOrNull(contact.cert_expires_on),
        companyName: contact.company_name,
      },
      school
        ? {
            title: school.title,
            city: school.city,
            state: school.state,
            startsOn: isoOrNull(school.starts_on),
            instructor: school.instructor,
          }
        : null,
    );
    const subject = applyMerge(template.subject, ctx);
    const body = applyMerge(template.body, ctx);
    const msgRows = await sql<{ id: number }>`
      insert into messages (
        created_by, contact_id, inquiry_id, template_id, to_email, subject, body, status
      )
      values (
        ${context.userId},
        ${inquiry.contact_id},
        ${inquiry.id},
        ${template.id},
        ${contact.email},
        ${subject},
        ${body},
        ${"draft"}
      )
      returning id
    `;
    await sql`
      insert into activities (created_by, contact_id, inquiry_id, kind, body)
      values (
        ${context.userId},
        ${inquiry.contact_id},
        ${inquiry.id},
        ${"email"},
        ${`Drafted “${subject}”`}
      )
    `;
    return {
      id: msgRows[0]?.id ?? 0,
      subject,
      body,
      toEmail: contact.email,
    };
  });

/** Save edits to a draft reply (does not send anything). */
export const saveDraft = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      id: z.number(),
      subject: z.string().min(1).max(200),
      body: z.string().min(1).max(8000),
    }),
  )
  .handler(async ({ data }) => {
    const sql = await getSql();
    await sql`
      update messages
      set subject = ${data.subject.trim()}, body = ${data.body}
      where id = ${data.id} and status = 'draft'
    `;
  });

/**
 * The staffer sent this reply from their own mail app and is recording it.
 * Stores the final wording, who sent it and when, and logs it on the inquiry.
 */
export const markMessageSent = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      id: z.number(),
      subject: z.string().min(1).max(200),
      body: z.string().min(1).max(8000),
    }),
  )
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    const rows = await sql<{ inquiry_id: number | null; contact_id: number | null }>`
      update messages
      set subject = ${data.subject.trim()}, body = ${data.body},
          status = 'sent', sent_at = now(), sent_by = ${context.userId}, error = ''
      where id = ${data.id} and status = 'draft'
      returning inquiry_id, contact_id
    `;
    const m = rows[0];
    if (!m) return { ok: false as const };
    const who = await sql<{ name: string }>`select name from "user" where id = ${context.userId} limit 1`;
    await sql`
      insert into activities (created_by, contact_id, inquiry_id, kind, body)
      values (
        ${context.userId}, ${m.contact_id}, ${m.inquiry_id}, 'email',
        ${`Reply sent by ${who[0]?.name ?? "staff"}: ${data.subject.trim()}`}
      )
    `;
    if (m.inquiry_id) {
      await sql`
        update inquiries set updated_at = now(), updated_by = ${context.userId}
        where id = ${m.inquiry_id}
      `;
    }
    return { ok: true as const };
  });

/** Everyone who has signed in to the Desk — the pool an inquiry can be assigned to. */
export const listStaff = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async () => {
    const sql = await getSql();
    const rows = await sql<{ id: string; name: string }>`
      select id, name from "user" order by lower(name)
    `;
    return rows.map((r) => ({ id: r.id, name: r.name }));
  });

export const assignInquiry = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ id: z.number(), assignedTo: z.string().min(1).nullable() }))
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    let assigneeName = "Unassigned";
    if (data.assignedTo) {
      const who = await sql<{ name: string }>`
        select name from "user" where id = ${data.assignedTo} limit 1
      `;
      if (!who[0]) throw new Error("That staff member does not exist");
      assigneeName = who[0].name;
    }
    await sql`
      update inquiries
      set assigned_to = ${data.assignedTo}, updated_at = now(), updated_by = ${context.userId}
      where id = ${data.id}
    `;
    await sql`
      insert into activities (created_by, inquiry_id, kind, body)
      values (
        ${context.userId},
        ${data.id},
        ${"assign"},
        ${data.assignedTo ? `Assigned to ${assigneeName}` : "Unassigned"}
      )
    `;
  });


/**
 * File an inquiry from a pasted website-form email. The same importer will be
 * used by the mailbox reader; pasting is the manual path until that exists.
 */
export const importPastedEmail = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ text: z.string().min(20).max(60000) }))
  .handler(async ({ context, data }) => {
    const sql = await getSql();
    const parsed = parseInquiryEmail(parsePastedEmail(data.text));
    if (!parsed.email && !parsed.contactName && !parsed.company) {
      throw new Error(
        "Could not find the form details in that text. Paste the whole email, including the lines from “A visitor on the … page has completed the form” down.",
      );
    }
    const result = await importParsedInquiry(sql, parsed, {
      createdBy: context.userId,
      sourceMailbox: "pasted",
    });
    return { ...result, kind: parsed.kind, problems: parsed.problems };
  });
