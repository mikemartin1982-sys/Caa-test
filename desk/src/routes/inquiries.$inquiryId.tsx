import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute, Link } from "@tanstack/react-router";
import { useState } from "react";
import { toast } from "sonner";
import { RequireUser } from "@/components/require-user";
import { CertPill, StatusPill } from "@/components/status-pill";
import { Button } from "@/components/ui/button";
import { Card, CardMeta, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import {
  addNote,
  assignInquiry,
  composeFromTemplate,
  getInquiry,
  listStaff,
  markMessageSent,
  saveDraft,
  updateInquiryStatus,
} from "@/lib/crm/api";
import { SHARED_MAILBOX, formatShortDate, kindLabel, mailtoHref, siteUrl } from "@/lib/crm/labels";
import { INQUIRY_STATUSES, type InquiryStatus } from "@/lib/crm/types";
import { useCurrentUser } from "@/lib/auth/use-current-user";

export const Route = createFileRoute("/inquiries/$inquiryId")({
  component: Page,
});

function Page() {
  const { inquiryId } = Route.useParams();
  return (
    <RequireUser>
      <InquiryDetail id={Number(inquiryId)} />
    </RequireUser>
  );
}

function InquiryDetail({ id }: { id: number }) {
  const qc = useQueryClient();
  const q = useQuery({
    queryKey: ["inquiry", id],
    queryFn: () => getInquiry({ data: { id } }),
  });
  const me = useCurrentUser();
  const staffQ = useQuery({ queryKey: ["staff"], queryFn: () => listStaff() });
  const [note, setNote] = useState("");
  const [draftId, setDraftId] = useState<number | null>(null);
  const [draftSubject, setDraftSubject] = useState("");
  const [draftBody, setDraftBody] = useState("");
  const [draftTo, setDraftTo] = useState("");

  const statusMut = useMutation({
    mutationFn: (status: InquiryStatus) =>
      updateInquiryStatus({ data: { id, status } }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["inquiry", id] }),
  });
  const assignMut = useMutation({
    mutationFn: (assignedTo: string | null) =>
      assignInquiry({ data: { id, assignedTo } }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["inquiry", id] });
      qc.invalidateQueries({ queryKey: ["inquiries"] });
    },
    onError: (err) => toast.error(err instanceof Error ? err.message : "Could not assign"),
  });
  const noteMut = useMutation({
    mutationFn: () =>
      addNote({
        data: {
          inquiryId: id,
          contactId: q.data?.inquiry?.contactId ?? null,
          body: note,
        },
      }),
    onSuccess: () => {
      setNote("");
      qc.invalidateQueries({ queryKey: ["inquiry", id] });
    },
  });
  const composeMut = useMutation({
    mutationFn: (templateId: number) =>
      composeFromTemplate({ data: { inquiryId: id, templateId } }),
    onSuccess: (draft) => {
      setDraftId(draft.id);
      setDraftSubject(draft.subject);
      setDraftBody(draft.body);
      setDraftTo(draft.toEmail);
      toast.success("Draft filled from the template");
      qc.invalidateQueries({ queryKey: ["inquiry", id] });
    },
    onError: (err) => toast.error(err instanceof Error ? err.message : "Could not compose"),
  });
  const saveMut = useMutation({
    mutationFn: () => {
      if (!draftId) throw new Error("No draft");
      return saveDraft({ data: { id: draftId, subject: draftSubject, body: draftBody } });
    },
  });
  const sentMut = useMutation({
    mutationFn: () => {
      if (!draftId) throw new Error("No draft");
      return markMessageSent({ data: { id: draftId, subject: draftSubject, body: draftBody } });
    },
    onSuccess: () => {
      toast.success("Recorded as sent from you");
      setDraftId(null);
      setDraftSubject("");
      setDraftBody("");
      setDraftTo("");
      qc.invalidateQueries();
    },
    onError: (err) => toast.error(err instanceof Error ? err.message : "Could not record that"),
  });

  if (q.isLoading) return <Skeleton className="h-96" />;
  const data = q.data;
  if (!data?.inquiry) {
    return <p className="text-muted">That inquiry is not on this desk.</p>;
  }
  const { inquiry, contact, school, activities, templates, messages } = data;
  const mailto = draftTo
    ? mailtoHref({ to: draftTo, subject: draftSubject, body: draftBody, cc: SHARED_MAILBOX })
    : "";

  return (
    <div className="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1.15fr_0.85fr]">
      <div>
        <Link to="/inquiries" className="text-sm text-primary hover:underline">
          All inquiries
        </Link>
        <div className="mt-3 flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="text-xs uppercase tracking-[0.2em] text-muted">
              {kindLabel(inquiry.kind)}
            </p>
            <h1 className="mt-1 font-display text-3xl">{inquiry.subject}</h1>
          </div>
          <StatusPill status={inquiry.status} />
        </div>
        <p className="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-muted">
          {inquiry.body || "No message body."}
        </p>
        <p className="mt-3 text-xs">
          Source{" "}
          {inquiry.source === "email" ? (
            <span>
              Email
              {inquiry.fromAddress ? ` via ${inquiry.fromAddress}` : ""}
              {inquiry.receivedAt ? ` · ${formatShortDate(inquiry.receivedAt.slice(0, 10))}` : ""}
            </span>
          ) : (
            <a
              className="text-primary hover:underline"
              href={siteUrl(inquiry.sourcePath)}
              target="_blank"
              rel="noreferrer"
            >
              caatest.tech{inquiry.sourcePath}
            </a>
          )}
        </p>

        <div className="mt-4 flex flex-wrap items-center gap-2 text-sm">
          <Label htmlFor="assignee" className="text-xs uppercase tracking-wide text-subtle">
            Assigned to
          </Label>
          <select
            id="assignee"
            className="rounded-md border border-border bg-elevated px-2 py-1.5 text-sm"
            value={inquiry.assignedTo ?? ""}
            disabled={assignMut.isPending}
            onChange={(e) => assignMut.mutate(e.target.value || null)}
          >
            <option value="">Unassigned</option>
            {(staffQ.data ?? []).map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </select>
          {me && inquiry.assignedTo !== me.id ? (
            <Button
              size="sm"
              variant="outline"
              disabled={assignMut.isPending}
              onClick={() => assignMut.mutate(me.id)}
            >
              Assign to me
            </Button>
          ) : null}
          {inquiry.updatedByName ? (
            <span className="text-xs text-subtle">Last touched by {inquiry.updatedByName}</span>
          ) : null}
        </div>

        <div className="mt-6 flex flex-wrap gap-2">
          {INQUIRY_STATUSES.map((st) => (
            <Button
              key={st}
              size="sm"
              variant={inquiry.status === st ? "default" : "outline"}
              onClick={() => statusMut.mutate(st)}
            >
              {st}
            </Button>
          ))}
        </div>

        <Card className="mt-8">
          <CardTitle>Reply from a template</CardTitle>
          <CardMeta className="mt-1">
            Merge fields fill from this contact and attached school. The reply
            opens in your own mail app, so it comes from you, with{" "}
            {SHARED_MAILBOX} copied.
          </CardMeta>
          <div className="mt-4 flex flex-wrap gap-2">
            {templates.map((t) => (
              <Button
                key={t.id}
                size="sm"
                variant="secondary"
                onClick={() => composeMut.mutate(t.id)}
              >
                {t.name}
              </Button>
            ))}
          </div>
          {draftId ? (
            <div className="mt-5 grid gap-3">
              <p className="text-xs uppercase tracking-wide text-muted">
                To {draftTo || "—"}
              </p>
              <input
                className="h-11 rounded-md border border-border bg-elevated px-3 text-sm"
                value={draftSubject}
                onChange={(e) => setDraftSubject(e.target.value)}
              />
              <Textarea
                className="min-h-56 font-mono text-sm"
                value={draftBody}
                onChange={(e) => setDraftBody(e.target.value)}
              />
              {mailto ? (
                <div className="flex flex-wrap gap-2">
                  <Button asChild>
                    <a href={mailto} onClick={() => saveMut.mutate()}>
                      Open in my mail app
                    </a>
                  </Button>
                  <Button
                    variant="secondary"
                    onClick={() => sentMut.mutate()}
                    disabled={sentMut.isPending}
                  >
                    {sentMut.isPending ? "Recording…" : "I sent it — mark as sent"}
                  </Button>
                </div>
              ) : (
                <p className="text-sm text-danger">
                  This contact has no email address. Add one before replying.
                </p>
              )}
              <p className="text-xs text-subtle">
                Send it from your mail app, then come back here and tap “mark as
                sent” so the team can see it was answered.
              </p>
            </div>
          ) : null}
        </Card>

        <Card className="mt-6">
          <CardTitle>Activity</CardTitle>
          <form
            className="mt-4 grid gap-2"
            onSubmit={(e) => {
              e.preventDefault();
              if (note.trim()) noteMut.mutate();
            }}
          >
            <Label htmlFor="note">Internal note</Label>
            <Textarea
              id="note"
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Call back after shift change…"
            />
            <div className="flex justify-end">
              <Button type="submit" size="sm" disabled={noteMut.isPending || !note.trim()}>
                Add note
              </Button>
            </div>
          </form>
          <ul className="mt-5 space-y-3">
            {activities.map((a) => (
              <li key={a.id} className="border-t border-border pt-3 first:border-0 first:pt-0">
                <p className="text-xs uppercase tracking-wide text-subtle">
                  {a.kind}
                  {a.authorName ? ` · ${a.authorName}` : ""} ·{" "}
                  {formatShortDate(a.createdAt.slice(0, 10))}
                </p>
                <p className="mt-1 whitespace-pre-wrap text-sm">{a.body}</p>
              </li>
            ))}
          </ul>
        </Card>
      </div>

      <aside className="grid gap-4 self-start">
        <Card>
          <CardTitle>Contact</CardTitle>
          {contact ? (
            <div className="mt-3 space-y-1 text-sm">
              <p className="font-medium">{contact.name}</p>
              <p className="text-muted">{contact.role}</p>
              {contact.companyId ? (
                <Link
                  to="/clients/$clientId"
                  params={{ clientId: String(contact.companyId) }}
                  className="text-primary hover:underline"
                >
                  {contact.companyName}
                </Link>
              ) : (
                <p>{contact.companyName}</p>
              )}
              <p>{contact.email}</p>
              <p>{contact.phone}</p>
              <div className="pt-2">
                <CertPill expiresOn={contact.certExpiresOn} />
                <p className="mt-2 text-xs text-muted">
                  Cert {formatShortDate(contact.certExpiresOn)}
                </p>
              </div>
            </div>
          ) : (
            <p className="mt-2 text-sm text-muted">No contact on file.</p>
          )}
        </Card>
        <Card>
          <CardTitle>School</CardTitle>
          {school ? (
            <div className="mt-3 text-sm">
              <p className="font-medium">{school.title}</p>
              <p className="text-muted">
                {formatShortDate(school.startsOn)}
                {school.city ? ` · ${school.city}, ${school.state}` : ""}
              </p>
              <p className="mt-1">{school.instructor}</p>
            </div>
          ) : (
            <p className="mt-2 text-sm text-muted">Not attached to a run.</p>
          )}
        </Card>
        <Card>
          <CardTitle>Letters</CardTitle>
          <ul className="mt-3 space-y-2 text-sm">
            {messages.length === 0 ? (
              <li className="text-muted">None yet.</li>
            ) : (
              messages.map((m) => (
                <li key={m.id}>
                  <span className="text-xs uppercase tracking-wide text-subtle">{m.status}</span>
                  <p className="font-medium">{m.subject}</p>
                </li>
              ))
            )}
          </ul>
        </Card>
      </aside>
    </div>
  );
}
