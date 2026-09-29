import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { extractAddress, htmlToText, parseInquiryEmail, parsePastedEmail } from "./intake-parse.ts";

// Same layout as a real registrar@ notification; customer details are invented.
const BODY = `A visitor on the NEW CLIENT ACCOUNT page has completed the form:

Company: Sample Energy Co.
Primary Contact: Jane Sample
Email: jane.sample@example.com
Phone: 5555550100
How They Heard About CAA: Referral
Company Address: 100 Main Street
City, State, Zip: Springfield, MT 59000
Newsletter: 1 | Class Confirms: 1 | Cert Reminders: 0

Thank you,
Client Management, Compliance Assurance Associates, Inc.`;

const PASTED = `From: Compliance Assurance Associates, Inc.
<registrar@compliance-assurance.com>
Sent: Monday, 28 September 2026 12:17:41
To: jane.sample@example.com
<jane.sample@example.com>
Cc: Client Management <client@Compliance-
Assurance.com>
Subject: Compliance Assurance New Client Account
Request

${BODY}`;

describe("parseInquiryEmail: new client account (real layout)", () => {
  const p = parseInquiryEmail({
    subject: "Compliance Assurance New Client Account Request",
    from: "Compliance Assurance Associates, Inc. <registrar@compliance-assurance.com>",
    to: "jane.sample@example.com",
    cc: "Client Management <client@Compliance-Assurance.com>",
    messageId: "<abc123@mail.example>",
    text: BODY,
  });
  it("takes the customer from the form, not the sender", () => {
    assert.equal(p.fromAddress, "registrar@compliance-assurance.com");
    assert.equal(p.company, "Sample Energy Co.");
    assert.equal(p.contactName, "Jane Sample");
    assert.equal(p.email, "jane.sample@example.com");
    assert.equal(p.phone, "5555550100");
  });
  it("splits the address and how they heard", () => {
    assert.equal(p.address, "100 Main Street");
    assert.equal(p.city, "Springfield");
    assert.equal(p.state, "MT");
    assert.equal(p.zip, "59000");
    assert.equal(p.howHeard, "Referral");
  });
  it("reads the pipe-separated consent flags, keeping 0 as false", () => {
    assert.equal(p.newsletter, true);
    assert.equal(p.classConfirms, true);
    assert.equal(p.certReminders, false);
  });
  it("classifies and records the page", () => {
    assert.equal(p.kind, "new_client_account");
    assert.equal(p.pageName, "NEW CLIENT ACCOUNT");
    assert.deepEqual(p.problems, []);
  });
  it("keeps every field as written", () => {
    assert.equal(p.fields["Primary Contact"], "Jane Sample");
    assert.equal(p.fields["City, State, Zip"], "Springfield, MT 59000");
    assert.equal(Object.keys(p.fields).length, 10);
    assert.equal(p.messageId, "<abc123@mail.example>");
  });
});

describe("parsePastedEmail", () => {
  const raw = parsePastedEmail(PASTED);
  it("peels wrapped Outlook headers", () => {
    assert.equal(extractAddress(raw.from), "registrar@compliance-assurance.com");
    assert.equal(raw.subject, "Compliance Assurance New Client Account Request");
    assert.equal(extractAddress(String(raw.cc)), "client@compliance-assurance.com");
    assert.match(String(raw.text), /^A visitor on the NEW CLIENT ACCOUNT page/);
  });
  it("round-trips into the same inquiry", () => {
    const p = parseInquiryEmail(raw);
    assert.equal(p.company, "Sample Energy Co.");
    assert.equal(p.kind, "new_client_account");
    assert.equal(p.fromAddress, "registrar@compliance-assurance.com");
  });
  it("accepts a body with no headers at all", () => {
    const p = parseInquiryEmail(parsePastedEmail(BODY));
    assert.equal(p.email, "jane.sample@example.com");
    // No subject line, but the "NEW CLIENT ACCOUNT page" sentence is enough to classify.
    assert.equal(p.kind, "new_client_account");
  });
});

describe("classification of the five inquiry types", () => {
  // Only the New Client Account subject has been seen for real; the other four
  // subjects are best guesses and are matched by keyword, not exact text.
  const kind = (subject: string, page = "") =>
    parseInquiryEmail({ subject, text: `A visitor on the ${page} page has completed the form:\nEmail: a@b.co` }).kind;
  it("maps each one", () => {
    assert.equal(kind("Compliance Assurance New Client Account Request", "NEW CLIENT ACCOUNT"), "new_client_account");
    assert.equal(kind("Compliance Assurance VR Client Account Request", "VR CLIENT ACCOUNT"), "vr_client_account");
    assert.equal(kind("Compliance Assurance VR Inquiry", "VR SMOKE SCHOOL"), "vr_school");
    assert.equal(kind("Compliance Assurance Private Smoke School Request", "PRIVATE SMOKE SCHOOL"), "private_onsite");
    assert.equal(kind("Compliance Assurance Notice of Violation", "NOTICE OF VIOLATION"), "veo_nov");
  });
  it("does not mistake a VR client account for a plain client account", () => {
    assert.notEqual(kind("VR Client Account Request"), "new_client_account");
  });
  it("falls back to general", () => {
    assert.equal(kind("Hello there"), "general");
  });
});

describe("robustness", () => {
  it("falls back to the To: address when the form has no Email field", () => {
    const p = parseInquiryEmail({
      subject: "Compliance Assurance VR Inquiry",
      to: "Someone <someone@customer.org>",
      text: "A visitor on the VR page has completed the form:\nCompany: Acme\n",
    });
    assert.equal(p.email, "someone@customer.org");
    assert.ok(p.problems.some((x) => /To:/.test(x)));
  });
  it("never uses our own address as the customer", () => {
    const p = parseInquiryEmail({
      subject: "x",
      to: "client@compliance-assurance.com",
      text: "A visitor on the X page has completed the form:\nCompany: Acme\n",
    });
    assert.equal(p.email, "");
  });
  it("reads html-only bodies", () => {
    const html = "<p>A visitor on the VR page has completed the form:</p><p>Company: Acme &amp; Sons<br>Email: a@acme.com</p>";
    assert.equal(htmlToText(html).includes("Acme & Sons"), true);
    const p = parseInquiryEmail({ subject: "VR Inquiry", html });
    assert.equal(p.company, "Acme & Sons");
    assert.equal(p.email, "a@acme.com");
  });
  it("flags an email that is not a form", () => {
    const p = parseInquiryEmail({ subject: "Lunch?", text: "Want to grab lunch tomorrow?" });
    assert.ok(p.problems.length >= 1);
    assert.equal(p.company, "");
  });
  it("keeps unknown fields from other form types", () => {
    const p = parseInquiryEmail({
      subject: "Private Smoke School Request",
      text: "A visitor on the PRIVATE SMOKE SCHOOL page has completed the form:\nCompany: X\nNumber of Students: 12\nPreferred Dates: Oct 12-14\nEmail: x@x.com",
    });
    assert.equal(p.fields["Number of Students"], "12");
    assert.equal(p.fields["Preferred Dates"], "Oct 12-14");
    assert.equal(p.kind, "private_onsite");
  });
  it("does not treat URLs as fields", () => {
    const p = parseInquiryEmail({ subject: "s", text: "A visitor on the X page has completed the form:\nEmail: a@b.co\nhttps://example.com/x" });
    assert.equal(Object.keys(p.fields).length, 1);
  });
});
