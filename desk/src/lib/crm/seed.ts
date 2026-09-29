import type { Sql } from "@/lib/db";

type SeedCompany = {
  name: string;
  industry: string;
  city: string;
  state: string;
  notes: string;
};

type SeedContact = {
  company: string;
  name: string;
  email: string;
  phone: string;
  role: string;
  certExpiresOn: string | null;
  source: string;
};

type SeedSchool = {
  kind: string;
  title: string;
  city: string;
  state: string;
  startsOn: string | null;
  endsOn: string | null;
  seats: number;
  enrolled: number;
  instructor: string;
  status: string;
  notes: string;
};

type SeedInquiry = {
  contact: string;
  school?: string;
  kind: string;
  subject: string;
  body: string;
  status: string;
  sourcePath: string;
};

const COMPANIES: SeedCompany[] = [
  {
    name: "Alabama Power — Plant Miller",
    industry: "Electric utility",
    city: "Quinton",
    state: "AL",
    notes: "Long-time public school client. Recert cycle every 6 months for the EHS bench.",
  },
  {
    name: "Duke Energy — Belews Creek",
    industry: "Electric utility",
    city: "Walnut Cove",
    state: "NC",
    notes: "Prefers VR for overflow observers; still sends a cohort to Raleigh public schools.",
  },
  {
    name: "Nucor Steel Decatur",
    industry: "Steel",
    city: "Decatur",
    state: "AL",
    notes: "Private on-site school requested for Q4. Shift coverage is the constraint.",
  },
  {
    name: "Heidelberg Materials",
    industry: "Cement",
    city: "Leeds",
    state: "AL",
    notes: "Kiln opacity. Method 9 and Method 22 mix.",
  },
  {
    name: "Eastman Chemical",
    industry: "Chemical",
    city: "Kingsport",
    state: "TN",
    notes: "NOV consult last year. Keep a paper trail on every VEO reply.",
  },
  {
    name: "TVA — Kingston Fossil Plant",
    industry: "Electric utility",
    city: "Harriman",
    state: "TN",
    notes: "Large observer pool. Digital cert records matter.",
  },
];

const CONTACTS: SeedContact[] = [
  {
    company: "Alabama Power — Plant Miller",
    name: "Marisol Vega",
    email: "marisol.vega@example-ap.com",
    phone: "205-555-0142",
    role: "EHS Supervisor",
    certExpiresOn: "2026-10-18",
    source: "caatest.tech / in-person",
  },
  {
    company: "Duke Energy — Belews Creek",
    name: "Chris Lang",
    email: "chris.lang@example-duke.com",
    phone: "336-555-0194",
    role: "Environmental Coordinator",
    certExpiresOn: "2027-03-02",
    source: "caatest.tech / vr-smoke-school",
  },
  {
    company: "Nucor Steel Decatur",
    name: "Patricia Holcomb",
    email: "patricia.holcomb@example-nucor.com",
    phone: "256-555-0177",
    role: "Environmental Manager",
    certExpiresOn: "2026-09-12",
    source: "referral",
  },
  {
    company: "Heidelberg Materials",
    name: "Omar Siddiq",
    email: "omar.siddiq@example-hm.com",
    phone: "205-555-0119",
    role: "Plant Environmental Lead",
    certExpiresOn: "2026-11-04",
    source: "caatest.tech / training-map?state=AL",
  },
  {
    company: "Eastman Chemical",
    name: "Helen Cho",
    email: "helen.cho@example-eastman.com",
    phone: "423-555-0160",
    role: "Air Compliance Counsel (external)",
    certExpiresOn: null,
    source: "caatest.tech / veo-expertise",
  },
  {
    company: "TVA — Kingston Fossil Plant",
    name: "James Whitaker",
    email: "james.whitaker@example-tva.com",
    phone: "865-555-0133",
    role: "Shift Environmental Tech",
    certExpiresOn: "2026-12-09",
    source: "caatest.tech / vr-smoke-school",
  },
];

const SCHOOLS: SeedSchool[] = [
  {
    kind: "vr",
    title: "VirtualOpacity — open enrollment",
    city: "Remote",
    state: "",
    startsOn: "2026-09-29",
    endsOn: null,
    seats: 200,
    enrolled: 64,
    instructor: "CAA VR desk",
    status: "open",
    notes: "EPA ALT-152A. $250 per enrollment; lecture $50 if needed.",
  },
  {
    kind: "public",
    title: "Birmingham public smoke school",
    city: "Birmingham",
    state: "AL",
    startsOn: "2026-10-14",
    endsOn: "2026-10-15",
    seats: 40,
    enrolled: 28,
    instructor: "Derek Mason",
    status: "open",
    notes: "Field certification. Fast digital cert after the run.",
  },
  {
    kind: "public",
    title: "Raleigh public smoke school",
    city: "Raleigh",
    state: "NC",
    startsOn: "2026-11-11",
    endsOn: "2026-11-12",
    seats: 36,
    enrolled: 11,
    instructor: "Joseph Spivey",
    status: "open",
    notes: "Joe's home school. Strong Duke / TVA draw.",
  },
  {
    kind: "private",
    title: "Nucor Decatur on-site",
    city: "Decatur",
    state: "AL",
    startsOn: "2026-12-03",
    endsOn: "2026-12-04",
    seats: 18,
    enrolled: 0,
    instructor: "Derek Mason",
    status: "hold",
    notes: "Quote out. Waiting on shift coverage confirmation.",
  },
];

const INQUIRIES: SeedInquiry[] = [
  {
    contact: "Marisol Vega",
    school: "Birmingham public smoke school",
    kind: "in_person",
    subject: "Four observers for Birmingham — Oct 14",
    body: "Need four seats for Plant Miller EHS. Two are recerts, two are new. Can we keep them on the same run?",
    status: "qualified",
    sourcePath: "/training-map?state=AL",
  },
  {
    contact: "Chris Lang",
    school: "VirtualOpacity — open enrollment",
    kind: "vr_school",
    subject: "VR overflow for Belews Creek",
    body: "We have six techs who cannot travel this quarter. Confirm headset model and lecture requirement.",
    status: "new",
    sourcePath: "/vr-smoke-school",
  },
  {
    contact: "Patricia Holcomb",
    school: "Nucor Decatur on-site",
    kind: "private_onsite",
    subject: "Private on-site school — Decatur mill",
    body: "Looking at a two-day private school on site so we don't lose a shift. 12–16 observers. Need a quote.",
    status: "waiting",
    sourcePath: "/in-person-smoke-schools",
  },
  {
    contact: "Helen Cho",
    kind: "veo_nov",
    subject: "NOV response — Method 9 documentation review",
    body: "Client received an opacity NOV. Need CAA to review observer notes, weather, and form completeness before we answer the agency.",
    status: "new",
    sourcePath: "/veo-expertise",
  },
  {
    contact: "Omar Siddiq",
    school: "Birmingham public smoke school",
    kind: "readings",
    subject: "Contract Method 9 readings — kiln 2",
    body: "Can CAA put a certified observer on kiln 2 for a two-day campaign while our bench recerts?",
    status: "scheduled",
    sourcePath: "/veo-readings",
  },
  {
    contact: "James Whitaker",
    school: "VirtualOpacity — open enrollment",
    kind: "vr_school",
    subject: "Kingston VR recert window",
    body: "Cert lapses in December. Prefer VR so we don't pull a shutdown observer off site.",
    status: "won",
    sourcePath: "/vr-smoke-school",
  },
];

const TEMPLATES: { slug: string; name: string; kind: string; subject: string; body: string }[] = [
  {
    slug: "vr-enroll",
    name: "VR smoke school enrollment",
    kind: "vr_school",
    subject: "VirtualOpacity enrollment — {{company}}",
    body: `{{firstName}},

Thank you for reaching CAA about Method 9 certification. This confirms VirtualOpacity enrollment for {{fullName}} at {{company}}.

VirtualOpacity is EPA-approved ALT-152A. Students certify on their schedule, with unlimited practice time, and no travel.

Next steps:
1. Confirm headset model (we will send the current purchase note).
2. Lecture course is $50 if you do not hold a current lecture certificate.
3. Enrollment is $250. We start the record when payment is arranged.

Your Method 9 certification on file expires {{certExpires}}.

Reply to this thread or call the office at 901-381-9960.

Compliance Assurance Associates, Inc.
caatest.tech`,
  },
  {
    slug: "in-person-confirm",
    name: "In-person school confirmation",
    kind: "in_person",
    subject: "Seat confirmed — {{schoolTitle}}",
    body: `{{firstName}},

You are confirmed for {{schoolTitle}} on {{schoolDate}} in {{schoolCity}}. Instructor: {{instructor}}.

Bring:
- Government ID
- A clipboard and the Method 9 field form (we will also have blanks)
- Weather-ready clothing for the run

Digital certification is issued after the school. Your current cert expires {{certExpires}}.

If the roster changes, tell us 48 hours out so we can release the seat.

Compliance Assurance Associates, Inc.
caatest.tech`,
  },
  {
    slug: "recert-reminder",
    name: "Recertification reminder",
    kind: "general",
    subject: "Method 9 recert window — {{company}}",
    body: `{{firstName}},

This is a courtesy from CAA. The Method 9 certification we have on file for {{fullName}} expires {{certExpires}}.

You can recertify two ways:
- VirtualOpacity (100% online, ALT-152A) — caatest.tech/vr-smoke-school
- In-person public or private school — caatest.tech/in-person-smoke-schools

Reply with names and we will hold seats.

Compliance Assurance Associates, Inc.`,
  },
  {
    slug: "private-quote",
    name: "Private on-site quote",
    kind: "private_onsite",
    subject: "On-site smoke school quote — {{company}}",
    body: `{{firstName}},

Thank you for asking about a private Method 9 school for {{company}}.

A typical on-site run is two days with a CAA instructor. We certify your observers on your property so you do not lose a shift to travel.

To lock a date we need:
- Headcount (and whether lecture is still current)
- Preferred window
- A staging area with a clear line of sight

Proposed school: {{schoolTitle}} on {{schoolDate}} in {{schoolCity}}, instructor {{instructor}}.

I will follow with the written quote. Office: 901-381-9960.

Compliance Assurance Associates, Inc.`,
  },
  {
    slug: "nov-intake",
    name: "NOV / VEO intake",
    kind: "veo_nov",
    subject: "VEO file opened — {{company}}",
    body: `{{firstName}},

CAA has opened a visible-emissions file for {{company}}.

Please send, as you have them:
- The notice of violation and any agency cover letter
- Observer field forms, weather, and sun angle notes
- Related permit conditions or prior correspondence

Our team will review Method 9 / Method 22 completeness and flag anything that will not survive scrutiny. Do not send a response to the agency until we have walked the file with you.

Compliance Assurance Associates, Inc.
Professional services — caatest.tech/professional-services`,
  },
  {
    slug: "readings-confirm",
    name: "Field opacity readings",
    kind: "readings",
    subject: "CAA observer scheduled — {{company}}",
    body: `{{firstName}},

A CAA certified observer is scheduled for Method 9 / Method 22 readings at {{company}}.

School / run: {{schoolTitle}}
Date: {{schoolDate}}
Site: {{schoolCity}}
Instructor / observer: {{instructor}}

We will deliver documented readings suitable for permit and agency files.

Compliance Assurance Associates, Inc.`,
  },
];

let templatesSeeded = false;

/**
 * Shared-team seeding.
 *  - Email templates are seeded once for the whole team (slug is unique, so a
 *    race between two staffers' first page loads is harmless).
 *  - Demo companies/contacts/schools/inquiries are seeded ONLY when
 *    CAA_DESK_DEMO_SEED=true and the book is empty. Never enable in production:
 *    the sample records are fictional.
 */
export async function seedDeskIfEmpty(sql: Sql, userId: string): Promise<void> {
  if (!templatesSeeded) {
    for (const template of TEMPLATES) {
      await sql`
        insert into templates (created_by, slug, name, kind, subject, body)
        values (
          ${userId},
          ${template.slug},
          ${template.name},
          ${template.kind},
          ${template.subject},
          ${template.body}
        )
        on conflict (slug) do nothing
      `;
    }
    templatesSeeded = true;
  }

  if (process.env.CAA_DESK_DEMO_SEED?.trim() !== "true") return;
  const existing = await sql<{ n: number }>`
    select count(*)::int as n from companies
  `;
  if ((existing[0]?.n ?? 0) > 0) return;

  const companyIds = new Map<string, number>();
  for (const company of COMPANIES) {
    const rows = await sql<{ id: number }>`
      insert into companies (created_by, name, industry, city, state, notes)
      values (
        ${userId},
        ${company.name},
        ${company.industry},
        ${company.city},
        ${company.state},
        ${company.notes}
      )
      returning id
    `;
    const id = rows[0]?.id;
    if (id) companyIds.set(company.name, id);
  }

  const contactIds = new Map<string, number>();
  for (const contact of CONTACTS) {
    const companyId = companyIds.get(contact.company) ?? null;
    const rows = await sql<{ id: number }>`
      insert into contacts (
        created_by, company_id, name, email, phone, role, cert_expires_on, source
      )
      values (
        ${userId},
        ${companyId},
        ${contact.name},
        ${contact.email},
        ${contact.phone},
        ${contact.role},
        ${contact.certExpiresOn},
        ${contact.source}
      )
      returning id
    `;
    const id = rows[0]?.id;
    if (id) contactIds.set(contact.name, id);
  }

  const schoolIds = new Map<string, number>();
  for (const school of SCHOOLS) {
    const rows = await sql<{ id: number }>`
      insert into schools (
        created_by, kind, title, city, state, starts_on, ends_on,
        seats, enrolled, instructor, status, notes
      )
      values (
        ${userId},
        ${school.kind},
        ${school.title},
        ${school.city},
        ${school.state},
        ${school.startsOn},
        ${school.endsOn},
        ${school.seats},
        ${school.enrolled},
        ${school.instructor},
        ${school.status},
        ${school.notes}
      )
      returning id
    `;
    const id = rows[0]?.id;
    if (id) schoolIds.set(school.title, id);
  }

  for (const inquiry of INQUIRIES) {
    const contactId = contactIds.get(inquiry.contact) ?? null;
    const schoolId = inquiry.school ? (schoolIds.get(inquiry.school) ?? null) : null;
    const companyName = CONTACTS.find((c) => c.name === inquiry.contact)?.company;
    const companyId = companyName ? (companyIds.get(companyName) ?? null) : null;
    const inquiryRows = await sql<{ id: number }>`
      insert into inquiries (
        created_by, contact_id, company_id, school_id, kind, subject, body, status, source_path
      )
      values (
        ${userId},
        ${contactId},
        ${companyId},
        ${schoolId},
        ${inquiry.kind},
        ${inquiry.subject},
        ${inquiry.body},
        ${inquiry.status},
        ${inquiry.sourcePath}
      )
      returning id
    `;
    const inquiryId = inquiryRows[0]?.id;
    await sql`
      insert into activities (created_by, contact_id, inquiry_id, kind, body)
      values (
        ${userId},
        ${contactId},
        ${inquiryId ?? null},
        'intake',
        ${`Captured from caatest.tech${inquiry.sourcePath}`}
      )
    `;
  }

}
