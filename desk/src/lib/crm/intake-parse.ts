/**
 * Parse the website-form notification emails that land in client@.
 *
 * The site mails each submission from the system sender (registrar@) with the
 * real customer inside the message body, so the customer is read from the form
 * fields, never from the From line:
 *
 *   Subject: Compliance Assurance New Client Account Request
 *   A visitor on the NEW CLIENT ACCOUNT page has completed the form:
 *   Company: ...
 *   Primary Contact: ...
 *   Email: ...
 *   Newsletter: 1 | Class Confirms: 1 | Cert Reminders: 1
 *
 * Pure functions, no I/O, no framework imports: safe to unit test and to call
 * from the mailbox worker.
 */
import type { InquiryKind } from "./types.ts";

export type RawEmail = {
  subject?: string;
  /** Raw From header, e.g. `Compliance Assurance Associates, Inc. <registrar@...>` */
  from?: string;
  to?: string | string[];
  cc?: string | string[];
  /** ISO timestamp the mailbox received it. */
  receivedAt?: string;
  /** RFC 5322 Message-ID; the dedupe key. */
  messageId?: string;
  text?: string;
  html?: string;
};

export type ParsedInquiry = {
  kind: InquiryKind;
  /** Page the visitor used, as written, e.g. "NEW CLIENT ACCOUNT". */
  pageName: string;
  subject: string;
  company: string;
  contactName: string;
  email: string;
  phone: string;
  address: string;
  city: string;
  state: string;
  zip: string;
  howHeard: string;
  newsletter: boolean | null;
  classConfirms: boolean | null;
  certReminders: boolean | null;
  /** Every label/value pair exactly as written, in order. */
  fields: Record<string, string>;
  fromAddress: string;
  messageId: string | null;
  receivedAt: string | null;
  /** Human-readable notes about anything that looked off. */
  problems: string[];
};

const INTERNAL_DOMAIN = "compliance-assurance.com";

export function extractAddress(value: string | undefined | null): string {
  if (!value) return "";
  const m = value.match(/<([^<>\s]+@[^<>\s]+)>/) ?? value.match(/([^\s<>",;]+@[^\s<>",;]+)/);
  return (m?.[1] ?? "").trim().toLowerCase();
}

function addressList(value: string | string[] | undefined): string[] {
  if (!value) return [];
  const parts = Array.isArray(value) ? value : value.split(/[;,]\s*(?=[^;,]*@)|[;]\s*/);
  return parts.map(extractAddress).filter(Boolean);
}

/** Minimal HTML -> text for html-only bodies. */
export function htmlToText(html: string): string {
  return html
    .replace(/<(script|style)[\s\S]*?<\/\1>/gi, "")
    .replace(/<br\s*\/?>/gi, "\n")
    .replace(/<\/(p|div|tr|li|h[1-6])>/gi, "\n")
    .replace(/<[^>]+>/g, "")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'");
}

function keyOf(label: string): string {
  return label
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "");
}

function toBool(value: string): boolean | null {
  const v = value.trim().toLowerCase();
  if (/^(1|true|yes|y|on|checked)$/.test(v)) return true;
  if (/^(0|false|no|n|off|unchecked)$/.test(v)) return false;
  return null;
}

const LABEL_LINE = /^([A-Za-z][A-Za-z0-9 ,/&'().-]{0,58}?)\s*:\s*(.*)$/;

function pairsFromLine(line: string): Array<[string, string]> {
  const segments = line.split(/\s+\|\s+/);
  if (segments.length > 1) {
    const pairs: Array<[string, string]> = [];
    for (const seg of segments) {
      const m = seg.match(LABEL_LINE);
      if (!m) return [];
      pairs.push([m[1].trim(), m[2].trim()]);
    }
    return pairs;
  }
  const m = line.match(LABEL_LINE);
  if (!m) return [];
  if (/^https?$/i.test(m[1].trim())) return [];
  return [[m[1].trim(), m[2].trim()]];
}

function classify(subject: string, pageName: string): InquiryKind {
  const t = `${subject} ${pageName}`.toLowerCase();
  if (/notice of violation|\bnov\b/.test(t)) return "veo_nov";
  const vr = /\bvr\b|virtual/.test(t);
  if (vr && /client account/.test(t)) return "vr_client_account";
  if (vr) return "vr_school";
  if (/private/.test(t)) return "private_onsite";
  if (/client account/.test(t)) return "new_client_account";
  return "general";
}

function first(fields: Record<string, string>, keys: string[]): string {
  for (const k of keys) {
    const v = fields[k];
    if (v && v.trim()) return v.trim();
  }
  return "";
}

const CSZ = /^(.*?),?\s+([A-Za-z]{2})\.?,?\s+(\d{5}(?:-\d{4})?)$/;

export function parseInquiryEmail(raw: RawEmail): ParsedInquiry {
  const problems: string[] = [];
  const subject = (raw.subject ?? "").trim();
  const bodyRaw = (raw.text && raw.text.trim() ? raw.text : raw.html ? htmlToText(raw.html) : "")
    .replace(/\r\n?/g, "\n")
    .replace(/ /g, " ");
  const lines = bodyRaw.split("\n").map((l) => l.trim());

  const introIdx = lines.findIndex((l) => /has completed the form/i.test(l));
  const introMatch = introIdx >= 0 ? lines[introIdx].match(/visitor on the (.+?) page/i) : null;
  const pageName = introMatch?.[1]?.trim() ?? "";
  if (introIdx < 0) problems.push("Did not find the 'has completed the form' line.");

  const labelByKey: Record<string, string> = {};
  const fields: Record<string, string> = {};
  const values: Record<string, string> = {};
  for (let i = introIdx + 1; i < lines.length; i += 1) {
    const line = lines[i];
    if (!line) continue;
    if (/^(thank you|thanks|regards|sincerely)\b/i.test(line)) break;
    for (const [label, value] of pairsFromLine(line)) {
      const key = keyOf(label);
      if (!key || key in values) continue;
      values[key] = value;
      labelByKey[key] = label;
      fields[label] = value;
    }
  }

  const company = first(values, ["company", "company_name", "organization", "business", "business_name"]);
  let contactName = first(values, ["primary_contact", "contact", "contact_name", "name", "full_name", "your_name"]);
  if (!contactName) {
    contactName = [values.first_name, values.last_name].filter(Boolean).join(" ").trim();
  }
  let email = extractAddress(first(values, ["email", "email_address", "e_mail", "contact_email"]));
  const phone = first(values, ["phone", "phone_number", "telephone", "mobile", "contact_phone"]);
  const address = first(values, ["company_address", "address", "street_address", "mailing_address"]);
  const howHeard =
    first(values, ["how_they_heard_about_caa", "how_did_you_hear_about_us"]) ||
    values[Object.keys(values).find((k) => k.includes("heard")) ?? ""] ||
    "";

  let city = first(values, ["city"]);
  let state = first(values, ["state"]);
  let zip = first(values, ["zip", "zip_code", "postal_code"]);
  const csz = first(values, ["city_state_zip", "city_state_zip_code"]);
  if (csz) {
    const m = csz.match(CSZ);
    if (m) {
      city = city || m[1].trim();
      state = state || m[2].toUpperCase();
      zip = zip || m[3];
    } else {
      city = city || csz;
      problems.push(`Could not split "City, State, Zip" value: ${csz}`);
    }
  }

  const fromAddress = extractAddress(raw.from);
  const recipients = [...addressList(raw.to), ...addressList(raw.cc)];
  if (!email) {
    // Fallback: the site also addresses the customer directly in To:.
    const external = addressList(raw.to).find((a) => !a.endsWith(`@${INTERNAL_DOMAIN}`));
    if (external) {
      email = external;
      problems.push("No Email field in the form; used the To: address.");
    }
  }
  if (!email && !contactName && !company) {
    problems.push("Found no customer details (no company, contact, or email).");
  }
  if (email && recipients.length && fromAddress && email === fromAddress) {
    problems.push("Customer email equals the sender; check this is not a system message.");
  }

  return {
    kind: classify(subject, pageName),
    pageName,
    subject,
    company,
    contactName,
    email,
    phone,
    address,
    city,
    state,
    zip,
    howHeard,
    newsletter: toBoolOrNull(values.newsletter),
    classConfirms: toBoolOrNull(values.class_confirms),
    certReminders: toBoolOrNull(values.cert_reminders),
    fields,
    fromAddress,
    messageId: raw.messageId?.trim() || null,
    receivedAt: raw.receivedAt ?? null,
    problems,
  };
}

function toBoolOrNull(v: string | undefined): boolean | null {
  return v === undefined ? null : toBool(v);
}

/**
 * Turn a pasted email (Outlook "From/Sent/To/Cc/Subject" header block followed
 * by the body, or just the body) into a RawEmail.
 */
export function parsePastedEmail(pasted: string): RawEmail {
  const text = pasted.replace(/\r\n?/g, "\n").replace(/ /g, " ");
  const lines = text.split("\n");
  const out: RawEmail = {};
  let i = 0;
  while (i < lines.length && !lines[i].trim()) i += 1;
  const headerRe = /^(From|Sent|Date|To|Cc|Subject)\s*:\s*(.*)$/i;
  let sawHeader = false;
  let current: string | null = null;
  for (; i < lines.length; i += 1) {
    const line = lines[i];
    const m = line.match(headerRe);
    if (m) {
      sawHeader = true;
      current = m[1].toLowerCase();
      assign(out, current, m[2].trim());
      continue;
    }
    if (!sawHeader) break;
    if (!line.trim() || /^a visitor on|has completed the form/i.test(line)) break;
    // Wrapped header continuation (long addresses, subjects).
    if (current) assign(out, current, line.trim(), true);
  }
  out.text = lines.slice(i).join("\n").trim();
  return out;
}

function assign(out: RawEmail, key: string, value: string, append = false) {
  // A wrapped line that broke after a hyphen (e.g. "client@Compliance-" / "Assurance.com")
  // continues the same token, so join without a space.
  const join = (prev: string | undefined) =>
    append && prev ? (prev.endsWith("-") ? `${prev}${value}` : `${prev} ${value}`) : value;
  if (key === "from") out.from = join(out.from);
  else if (key === "subject") out.subject = join(out.subject);
  else if (key === "to") out.to = join(Array.isArray(out.to) ? out.to.join("; ") : out.to);
  else if (key === "cc") out.cc = join(Array.isArray(out.cc) ? out.cc.join("; ") : out.cc);
  // "Sent:" / "Date:" are deliberately ignored for pasted mail: the text carries
  // no time zone. Pasted imports are stamped with the time they were pasted.
}
