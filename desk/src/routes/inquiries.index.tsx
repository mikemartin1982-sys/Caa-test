import { useQuery } from "@tanstack/react-query";
import { createFileRoute, Link } from "@tanstack/react-router";
import { IntakeDialog } from "@/components/intake-dialog";
import { PasteEmailDialog } from "@/components/paste-email-dialog";
import { RequireUser } from "@/components/require-user";
import { StatusPill } from "@/components/status-pill";
import { Skeleton } from "@/components/ui/skeleton";
import { listInquiries } from "@/lib/crm/api";
import { formatShortDate, kindLabel } from "@/lib/crm/labels";

export const Route = createFileRoute("/inquiries/")({ component: Page });

function Page() {
  return (
    <RequireUser>
      <Inquiries />
    </RequireUser>
  );
}

function Inquiries() {
  const q = useQuery({ queryKey: ["inquiries"], queryFn: () => listInquiries() });
  if (q.isLoading) {
    return (
      <div className="grid gap-3">
        <Skeleton className="h-10 w-56" />
        <Skeleton className="h-96" />
      </div>
    );
  }
  const rows = q.data ?? [];
  return (
    <div className="mx-auto max-w-6xl">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.2em] text-muted">Pipeline</p>
          <h1 className="mt-1 font-display text-3xl">Inquiries</h1>
        </div>
        <div className="flex flex-wrap gap-2">
          <PasteEmailDialog />
          <IntakeDialog />
        </div>
      </div>
      <div className="mt-6 overflow-hidden rounded-xl bg-elevated shadow-[var(--shadow-border)]">
        <ul className="divide-y divide-border">
          {rows.map((inq) => (
            <li key={inq.id}>
              <Link
                to="/inquiries/$inquiryId"
                params={{ inquiryId: String(inq.id) }}
                className="flex flex-col gap-2 px-5 py-4 hover:bg-surface sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="min-w-0">
                  <p className="font-medium">{inq.subject}</p>
                  <p className="truncate text-sm text-muted">
                    {inq.contactName ?? "No contact"} · {inq.companyName ?? "No company"} ·{" "}
                    {kindLabel(inq.kind)} ·{" "}
                    {inq.source === "email" ? "Email" : `caatest.tech${inq.sourcePath}`}
                  </p>
                  <p className="text-xs text-subtle">
                    {inq.assignedToName ? `Assigned to ${inq.assignedToName}` : "Unassigned"}
                    {inq.updatedByName ? ` · last touched by ${inq.updatedByName}` : ""}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <span className="hidden text-xs text-subtle sm:inline">
                    {formatShortDate(inq.createdAt.slice(0, 10))}
                  </span>
                  <StatusPill status={inq.status} />
                </div>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
