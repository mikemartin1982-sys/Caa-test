import type { InquiryKind, InquiryStatus, SchoolKind } from "./types";

export const INQUIRY_KIND_LABEL: Record<InquiryKind, string> = {
  new_client_account: "New Client Account Request",
  vr_client_account: "VR Client Account Request",
  vr_school: "VR Inquiry",
  in_person: "In-Person Smoke School",
  private_onsite: "Private Smoke School",
  veo_nov: "Notice of Violation",
  compliance_plan: "Compliance Plan",
  readings: "Opacity Readings",
  general: "General Inquiry",
};

export const INQUIRY_STATUS_LABEL: Record<InquiryStatus, string> = {
  new: "New",
  qualified: "Qualified",
  scheduled: "Scheduled",
  waiting: "Waiting",
  won: "Won",
  lost: "Closed",
};

export const SCHOOL_KIND_LABEL: Record<SchoolKind, string> = {
  vr: "VirtualOpacity",
  public: "Public field school",
  private: "Private on-site",
};

export const SOURCE_PATHS: Record<InquiryKind, string> = {
  new_client_account: "",
  vr_client_account: "",
  vr_school: "/vr-smoke-school",
  in_person: "/in-person-smoke-schools",
  private_onsite: "/in-person-smoke-schools",
  veo_nov: "/veo-expertise",
  compliance_plan: "/veo-services-compliance-plans",
  readings: "/veo-readings",
  general: "/",
};

/** Shared mailbox copied on every reply so the whole team keeps a record. */
export const SHARED_MAILBOX = "client@compliance-assurance.com";

/**
 * A mailto: link that opens the staffer's own mail app with the reply filled in,
 * so it is sent from their own address. CRLF line breaks keep Outlook happy.
 */
export function mailtoHref(args: { to: string; subject: string; body: string; cc?: string }): string {
  const q = [
    args.cc ? `cc=${encodeURIComponent(args.cc)}` : "",
    `subject=${encodeURIComponent(args.subject)}`,
    `body=${encodeURIComponent(args.body.replace(/\r?\n/g, "\r\n"))}`,
  ].filter(Boolean);
  return `mailto:${encodeURIComponent(args.to)}?${q.join("&")}`;
}

export const SITE_ORIGIN = "https://caatest.tech";

export function siteUrl(path: string): string {
  if (!path) return SITE_ORIGIN;
  return `${SITE_ORIGIN}${path.startsWith("/") ? path : `/${path}`}`;
}

export function kindLabel(kind: string): string {
  return INQUIRY_KIND_LABEL[kind as InquiryKind] ?? kind;
}

export function statusLabel(status: string): string {
  return INQUIRY_STATUS_LABEL[status as InquiryStatus] ?? status;
}

export function schoolKindLabel(kind: string): string {
  return SCHOOL_KIND_LABEL[kind as SchoolKind] ?? kind;
}

export function formatShortDate(iso: string | null | undefined): string {
  if (!iso) return "—";
  const day = iso.slice(0, 10);
  const [y, m, d] = day.split("-").map(Number);
  if (!y || !m || !d) return day;
  return new Date(Date.UTC(y, m - 1, d)).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    timeZone: "UTC",
  });
}

export function daysUntil(iso: string | null | undefined): number | null {
  if (!iso) return null;
  const day = iso.slice(0, 10);
  const then = Date.parse(`${day}T00:00:00Z`);
  if (Number.isNaN(then)) return null;
  const now = Date.now();
  return Math.ceil((then - now) / 86_400_000);
}

export function firstNameOf(name: string): string {
  const part = name.trim().split(/\s+/)[0];
  return part || name;
}
