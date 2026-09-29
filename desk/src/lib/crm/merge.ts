import { firstNameOf, formatShortDate } from "./labels";
import type { Contact, MergeContext, School } from "./types";

const TOKEN = /\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g;

export function mergeContext(
  contact: Pick<Contact, "name" | "email" | "role" | "certExpiresOn"> & {
    companyName?: string | null;
  },
  school?: Pick<School, "title" | "city" | "state" | "startsOn" | "instructor"> | null,
): MergeContext {
  const city = [school?.city, school?.state].filter(Boolean).join(", ");
  return {
    firstName: firstNameOf(contact.name),
    fullName: contact.name,
    company: contact.companyName ?? "",
    email: contact.email,
    role: contact.role,
    schoolTitle: school?.title ?? "",
    schoolDate: formatShortDate(school?.startsOn ?? null),
    schoolCity: city,
    instructor: school?.instructor ?? "",
    certExpires: formatShortDate(contact.certExpiresOn),
  };
}

export function applyMerge(text: string, ctx: MergeContext): string {
  return text.replace(TOKEN, (_, key: string) => {
    const value = ctx[key as keyof MergeContext];
    return value ?? "";
  });
}

export const MERGE_FIELDS: { token: string; hint: string }[] = [
  { token: "{{firstName}}", hint: "First name" },
  { token: "{{fullName}}", hint: "Full name" },
  { token: "{{company}}", hint: "Company" },
  { token: "{{email}}", hint: "Email" },
  { token: "{{role}}", hint: "Role" },
  { token: "{{schoolTitle}}", hint: "School" },
  { token: "{{schoolDate}}", hint: "School date" },
  { token: "{{schoolCity}}", hint: "City, ST" },
  { token: "{{instructor}}", hint: "Instructor" },
  { token: "{{certExpires}}", hint: "Cert expiration" },
];
