import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute, Link } from "@tanstack/react-router";
import { toast } from "sonner";
import { RequireUser } from "@/components/require-user";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { listMessages, markMessageSent } from "@/lib/crm/api";
import { SHARED_MAILBOX, formatShortDate, mailtoHref } from "@/lib/crm/labels";
import type { MessageStatus } from "@/lib/crm/types";

export const Route = createFileRoute("/mail")({ component: Page });

const TONE: Record<MessageStatus, "muted" | "warn" | "ok" | "danger"> = {
  draft: "warn",
  queued: "warn",
  sent: "ok",
  failed: "danger",
};

function Page() {
  return (
    <RequireUser>
      <Replies />
    </RequireUser>
  );
}

function Replies() {
  const qc = useQueryClient();
  const q = useQuery({ queryKey: ["messages"], queryFn: () => listMessages() });
  const sent = useMutation({
    mutationFn: (m: { id: number; subject: string; body: string }) =>
      markMessageSent({ data: m }),
    onSuccess: () => {
      toast.success("Recorded as sent from you");
      qc.invalidateQueries();
    },
    onError: (err) => toast.error(err instanceof Error ? err.message : "Could not record that"),
  });
  if (q.isLoading) return <Skeleton className="h-80" />;
  const rows = q.data ?? [];
  return (
    <div className="mx-auto max-w-6xl">
      <p className="text-xs uppercase tracking-[0.2em] text-muted">Email</p>
      <h1 className="mt-1 font-display text-3xl">Replies</h1>
      <p className="mt-2 max-w-2xl text-sm text-muted">
        Drafts wait here until someone sends them. Replies go out from your own
        mail app, with {SHARED_MAILBOX} copied. Come back and mark them sent.
      </p>
      <ul className="mt-6 divide-y divide-border overflow-hidden rounded-xl bg-elevated shadow-[var(--shadow-border)]">
        {rows.length === 0 ? (
          <li className="px-5 py-8 text-sm text-muted">
            No replies yet. Open an inquiry and pick a template.
          </li>
        ) : (
          rows.map((m) => (
            <li key={m.id} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <Badge tone={TONE[m.status]}>{m.status === "draft" ? "not sent" : m.status}</Badge>
                  <span className="text-xs text-subtle">
                    {formatShortDate((m.sentAt ?? m.createdAt).slice(0, 10))}
                    {m.status === "sent" && m.sentByName ? ` · sent by ${m.sentByName}` : ""}
                  </span>
                </div>
                <p className="mt-2 font-medium">{m.subject}</p>
                <p className="text-sm text-muted">
                  To {m.contactName ?? m.toEmail} · {m.toEmail}
                </p>
                {m.inquiryId ? (
                  <Link
                    to="/inquiries/$inquiryId"
                    params={{ inquiryId: String(m.inquiryId) }}
                    className="mt-1 inline-block text-xs text-primary hover:underline"
                  >
                    Open inquiry
                  </Link>
                ) : null}
              </div>
              {m.status !== "sent" ? (
                <div className="flex shrink-0 flex-wrap gap-2">
                  {m.toEmail ? (
                    <Button asChild size="sm">
                      <a href={mailtoHref({ to: m.toEmail, subject: m.subject, body: m.body, cc: SHARED_MAILBOX })}>
                        Open in my mail app
                      </a>
                    </Button>
                  ) : null}
                  <Button
                    size="sm"
                    variant="secondary"
                    disabled={sent.isPending}
                    onClick={() => sent.mutate({ id: m.id, subject: m.subject, body: m.body })}
                  >
                    Mark as sent
                  </Button>
                </div>
              ) : null}
            </li>
          ))
        )}
      </ul>
    </div>
  );
}
