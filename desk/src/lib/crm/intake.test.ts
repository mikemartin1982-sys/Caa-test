import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { before, describe, it } from "node:test";
import { PGlite } from "@electric-sql/pglite";
import { importParsedInquiry } from "./intake.ts";
import { parseInquiryEmail, parsePastedEmail } from "./intake-parse.ts";

const BODY = `A visitor on the NEW CLIENT ACCOUNT page has completed the form:

Company: Sample Energy Co.
Primary Contact: Jane Sample
Email: Jane.Sample@Example.com
Phone: 5555550100
How They Heard About CAA: Referral
Company Address: 100 Main Street
City, State, Zip: Springfield, MT 59000
Newsletter: 1 | Class Confirms: 1 | Cert Reminders: 0

Thank you,
Client Management`;

const email = (over: Record<string, unknown> = {}) =>
  parseInquiryEmail({
    subject: "Compliance Assurance New Client Account Request",
    from: "Compliance Assurance Associates, Inc. <registrar@compliance-assurance.com>",
    messageId: "<m1@mail.example>",
    receivedAt: "2026-09-28T17:17:41.000Z",
    text: BODY,
    ...over,
  });

describe("importParsedInquiry (real Postgres via PGlite, real migrations)", () => {
  const pg = new PGlite();
  // Minimal tagged-template adapter matching the app's Sql type.
  const sql = (async (strings: TemplateStringsArray, ...values: unknown[]) => {
    let text = strings[0];
    for (let i = 0; i < values.length; i += 1) text += `$${i + 1}${strings[i + 1]}`;
    return (await pg.query(text, values as never[])).rows;
  }) as never;
  const q = async (text: string) => (await pg.query(text)).rows as Record<string, unknown>[];
  const opts = { createdBy: "system:mailbox", sourceMailbox: "client@compliance-assurance.com" };

  before(async () => {
    for (const f of ["0001_auth.sql", "0002_crm.sql", "0003_shared_team.sql", "0004_intake_fields.sql"]) {
      await pg.exec(readFileSync(new URL(`../../../migrations/${f}`, import.meta.url), "utf8"));
    }
  });

  it("files company, contact and inquiry from one email", async () => {
    const r = await importParsedInquiry(sql, email(), opts);
    assert.equal(r.created, true);
    const inq = (await q("select * from inquiries"))[0];
    assert.equal(inq.kind, "new_client_account");
    assert.equal(inq.status, "new");
    assert.equal(inq.source, "email");
    assert.equal(inq.from_address, "registrar@compliance-assurance.com");
    assert.equal(inq.source_mailbox, "client@compliance-assurance.com");
    assert.equal(inq.how_heard, "Referral");
    assert.equal(inq.subject, "New Client Account Request — Sample Energy Co.");
    assert.equal((inq.form_data as Record<string, string>)["Primary Contact"], "Jane Sample");
    assert.match(String(inq.body), /Company Address: 100 Main Street/);
    const co = (await q("select * from companies"))[0];
    assert.deepEqual([co.name, co.city, co.state, co.zip, co.address], ["Sample Energy Co.", "Springfield", "MT", "59000", "100 Main Street"]);
    const c = (await q("select * from contacts"))[0];
    assert.equal(c.email, "jane.sample@example.com");
    assert.equal(c.phone, "5555550100");
    assert.deepEqual([c.newsletter, c.class_confirms, c.cert_reminders], [true, true, false]);
    assert.equal(c.company_id, co.id);
    assert.equal((await q("select * from activities where kind='intake'")).length, 1);
  });

  it("importing the same email again changes nothing", async () => {
    const r = await importParsedInquiry(sql, email(), opts);
    assert.equal(r.created, false);
    assert.equal((await q("select 1 from inquiries")).length, 1);
    assert.equal((await q("select 1 from activities")).length, 1);
  });

  it("a second inquiry from the same person reuses the contact and company", async () => {
    const p = email({
      messageId: "<m2@mail.example>",
      subject: "Compliance Assurance VR Inquiry",
      text: BODY.replace("NEW CLIENT ACCOUNT", "VR").replace("Newsletter: 1 | Class Confirms: 1 | Cert Reminders: 0", "Newsletter: 0"),
    });
    const r = await importParsedInquiry(sql, p, opts);
    assert.equal(r.created, true);
    assert.equal((await q("select 1 from contacts")).length, 1);
    assert.equal((await q("select 1 from companies")).length, 1);
    assert.equal((await q("select 1 from inquiries")).length, 2);
    const c = (await q("select * from contacts"))[0];
    // Only the flag they answered this time changes; the others keep their old value.
    assert.deepEqual([c.newsletter, c.class_confirms, c.cert_reminders], [false, true, false]);
  });

  it("company match is case-insensitive and never overwrites staff edits", async () => {
    await pg.exec("update companies set city = 'Billings'");
    const p = email({
      messageId: "<m3@mail.example>",
      text: BODY.replace("Company: Sample Energy Co.", "Company: SAMPLE ENERGY CO.").replace("Jane.Sample@Example.com", "other.person@example.com"),
    });
    await importParsedInquiry(sql, p, opts);
    assert.equal((await q("select 1 from companies")).length, 1);
    assert.equal((await q("select city from companies"))[0].city, "Billings");
    assert.equal((await q("select 1 from contacts")).length, 2);
  });

  it("pasted email without a Message-ID dedupes on content", async () => {
    const pasted = (mid?: string) => ({ ...parseInquiryEmail(parsePastedEmail(`Subject: Compliance Assurance Private Smoke School Request\n\nA visitor on the PRIVATE SMOKE SCHOOL page has completed the form:\nCompany: Paste Co\nEmail: p@paste.co\nStudents: 8`)), messageId: mid ?? null });
    const a = await importParsedInquiry(sql, pasted(), { createdBy: "u1", sourceMailbox: "pasted" });
    const b = await importParsedInquiry(sql, pasted(), { createdBy: "u2", sourceMailbox: "pasted" });
    assert.equal(a.created, true);
    assert.equal(b.created, false);
    assert.equal(a.inquiryId, b.inquiryId);
    assert.equal((await q("select kind from inquiries where id = " + a.inquiryId))[0].kind, "private_onsite");
  });

  it("an email with a person but no company still files", async () => {
    const r = await importParsedInquiry(
      sql,
      email({ messageId: "<m4@mail.example>", text: "A visitor on the VR page has completed the form:\nPrimary Contact: Solo Person\nEmail: solo@person.net" }),
      opts,
    );
    assert.equal(r.created, true);
    assert.equal(r.companyId, null);
    assert.ok(r.contactId);
  });
});
