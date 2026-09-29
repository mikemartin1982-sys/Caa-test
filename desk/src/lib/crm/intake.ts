/**
 * File a parsed website-form email into the shared book (server-side).
 *
 * Idempotent: the email's Message-ID is the dedupe key (unique index on
 * inquiries.source_message_id), so re-running an import, or two staffers
 * pasting the same email, never creates a second inquiry.
 *
 * Callable from a signed-in server function (createdBy = the staffer) and from
 * the future mailbox worker (createdBy = "system:mailbox").
 */
import type { Sql } from "../db.ts";
import type { ParsedInquiry } from "./intake-parse.ts";
import { INQUIRY_KIND_LABEL } from "./labels.ts";

export type ImportOptions = {
  createdBy: string;
  /** Where it came from: the shared mailbox address, or "pasted". */
  sourceMailbox: string;
};

export type ImportResult = {
  inquiryId: number;
  created: boolean;
  contactId: number | null;
  companyId: number | null;
};

async function sourceIdFor(p: ParsedInquiry): Promise<string> {
  if (p.messageId) return p.messageId;
  // No Message-ID (pasted text): derive a stable one so pasting twice is a no-op.
  const { createHash } = await import("node:crypto");
  const basis = [p.subject, p.email, p.company, p.contactName, JSON.stringify(p.fields)].join("\u0001");
  return `pasted:${createHash("sha256").update(basis).digest("hex").slice(0, 32)}`;
}

function bodyFrom(p: ParsedInquiry): string {
  return Object.entries(p.fields)
    .map(([label, value]) => `${label}: ${value}`)
    .join("\n");
}

export async function importParsedInquiry(
  sql: Sql,
  p: ParsedInquiry,
  opts: ImportOptions,
): Promise<ImportResult> {
  const sourceId = await sourceIdFor(p);

  const dupe = await sql<{ id: number; contact_id: number | null; company_id: number | null }>`
    select id, contact_id, company_id from inquiries where source_message_id = ${sourceId} limit 1
  `;
  if (dupe[0]) {
    return {
      inquiryId: dupe[0].id,
      created: false,
      contactId: dupe[0].contact_id,
      companyId: dupe[0].company_id,
    };
  }

  // --- company (matched by name, case-insensitive) ---
  let companyId: number | null = null;
  const companyName = p.company.trim();
  if (companyName) {
    const found = await sql<{ id: number }>`
      select id from companies where lower(name) = ${companyName.toLowerCase()} order by id limit 1
    `;
    if (found[0]) {
      companyId = found[0].id;
      // Fill blanks only; never overwrite what staff already typed.
      await sql`
        update companies set
          city    = case when city    = '' then ${p.city}    else city    end,
          state   = case when state   = '' then ${p.state}   else state   end,
          address = case when address = '' then ${p.address} else address end,
          zip     = case when zip     = '' then ${p.zip}     else zip     end
        where id = ${companyId}
      `;
    } else {
      const rows = await sql<{ id: number }>`
        insert into companies (created_by, name, city, state, address, zip)
        values (${opts.createdBy}, ${companyName}, ${p.city}, ${p.state}, ${p.address}, ${p.zip})
        returning id
      `;
      companyId = rows[0]?.id ?? null;
    }
  }

  // --- contact (matched by email, case-insensitive) ---
  let contactId: number | null = null;
  const contactName = p.contactName.trim() || p.email || companyName;
  const contactSource = `client@ form: ${p.pageName || INQUIRY_KIND_LABEL[p.kind]}`;
  if (p.email) {
    const found = await sql<{ id: number }>`
      select id from contacts where lower(email) = ${p.email} order by id limit 1
    `;
    if (found[0]) {
      contactId = found[0].id;
      await sql`
        update contacts set
          company_id     = coalesce(company_id, ${companyId}),
          phone          = case when phone = '' then ${p.phone} else phone end,
          newsletter     = coalesce(${p.newsletter}, newsletter),
          class_confirms = coalesce(${p.classConfirms}, class_confirms),
          cert_reminders = coalesce(${p.certReminders}, cert_reminders)
        where id = ${contactId}
      `;
    }
  }
  if (contactId === null && (p.email || p.contactName.trim())) {
    const rows = await sql<{ id: number }>`
      insert into contacts (created_by, company_id, name, email, phone, source,
                            newsletter, class_confirms, cert_reminders)
      values (${opts.createdBy}, ${companyId}, ${contactName}, ${p.email}, ${p.phone}, ${contactSource},
              ${p.newsletter}, ${p.classConfirms}, ${p.certReminders})
      returning id
    `;
    contactId = rows[0]?.id ?? null;
  }

  // --- inquiry ---
  const label = INQUIRY_KIND_LABEL[p.kind];
  const who = companyName || p.contactName.trim() || p.email || "Unknown sender";
  const subject = `${label} — ${who}`.slice(0, 200);
  const inserted = await sql<{ id: number }>`
    insert into inquiries (
      created_by, contact_id, company_id, kind, subject, body, status, source_path,
      source, source_message_id, source_mailbox, from_address, received_at, how_heard, form_data
    )
    values (
      ${opts.createdBy}, ${contactId}, ${companyId}, ${p.kind}, ${subject}, ${bodyFrom(p)}, 'new', '',
      'email', ${sourceId}, ${opts.sourceMailbox}, ${p.fromAddress},
      coalesce(${p.receivedAt}::timestamptz, now()), ${p.howHeard}, ${JSON.stringify(p.fields)}::jsonb
    )
    on conflict (source_message_id) where source_message_id is not null do nothing
    returning id
  `;
  if (!inserted[0]) {
    // Lost a race with another import of the same email.
    const again = await sql<{ id: number }>`
      select id from inquiries where source_message_id = ${sourceId} limit 1
    `;
    return { inquiryId: again[0]?.id ?? 0, created: false, contactId, companyId };
  }
  const inquiryId = inserted[0].id;
  await sql`
    insert into activities (created_by, contact_id, inquiry_id, kind, body)
    values (
      ${opts.createdBy}, ${contactId}, ${inquiryId}, 'intake',
      ${`Imported from ${opts.sourceMailbox}: ${p.pageName || label}`}
    )
  `;
  return { inquiryId, created: true, contactId, companyId };
}
