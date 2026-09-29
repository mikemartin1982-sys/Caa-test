export const INQUIRY_KINDS = [
  "new_client_account",
  "vr_client_account",
  "vr_school",
  "in_person",
  "private_onsite",
  "veo_nov",
  "compliance_plan",
  "readings",
  "general",
] as const;

export type InquiryKind = (typeof INQUIRY_KINDS)[number];

export const INQUIRY_STATUSES = [
  "new",
  "qualified",
  "scheduled",
  "waiting",
  "won",
  "lost",
] as const;

export type InquiryStatus = (typeof INQUIRY_STATUSES)[number];

export const SCHOOL_KINDS = ["vr", "public", "private"] as const;
export type SchoolKind = (typeof SCHOOL_KINDS)[number];

export const MESSAGE_STATUSES = ["draft", "queued", "sent", "failed"] as const;
export type MessageStatus = (typeof MESSAGE_STATUSES)[number];

export type Company = {
  id: number;
  name: string;
  industry: string;
  city: string;
  state: string;
  notes: string;
  createdAt: string;
  contactCount: number;
};

export type Contact = {
  id: number;
  companyId: number | null;
  companyName: string | null;
  name: string;
  email: string;
  phone: string;
  role: string;
  certExpiresOn: string | null;
  source: string;
  createdAt: string;
};

export type School = {
  id: number;
  kind: SchoolKind;
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

export type Inquiry = {
  id: number;
  contactId: number | null;
  companyId: number | null;
  schoolId: number | null;
  kind: InquiryKind;
  subject: string;
  body: string;
  status: InquiryStatus;
  sourcePath: string;
  createdAt: string;
  updatedAt: string;
  contactName: string | null;
  contactEmail: string | null;
  companyName: string | null;
  schoolTitle: string | null;
  assignedTo: string | null;
  assignedToName: string | null;
  createdBy: string | null;
  updatedByName: string | null;
  /** "manual" (typed in the Desk) or "email" (imported from the client@ mailbox). */
  source: string;
  /** Raw From address of the source email (often a system sender, not the customer). */
  fromAddress: string;
  receivedAt: string | null;
};

export type StaffMember = { id: string; name: string };

export type Template = {
  id: number;
  slug: string;
  name: string;
  kind: string;
  subject: string;
  body: string;
};

export type Message = {
  id: number;
  contactId: number | null;
  inquiryId: number | null;
  templateId: number | null;
  toEmail: string;
  subject: string;
  body: string;
  status: MessageStatus;
  error: string;
  sentAt: string | null;
  createdAt: string;
  contactName: string | null;
  sentByName: string | null;
};

export type Activity = {
  id: number;
  contactId: number | null;
  inquiryId: number | null;
  kind: string;
  body: string;
  createdAt: string;
  authorName: string | null;
};

export type DeskSummary = {
  openInquiries: number;
  recertsDue: number;
  recertsExpired: number;
  upcomingSchools: number;
  queuedMail: number;
  wonThisMonth: number;
};

export type MergeContext = {
  firstName: string;
  fullName: string;
  company: string;
  email: string;
  role: string;
  schoolTitle: string;
  schoolDate: string;
  schoolCity: string;
  instructor: string;
  certExpires: string;
};
