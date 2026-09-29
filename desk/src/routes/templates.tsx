import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { RequireUser } from "@/components/require-user";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { listTemplates, saveTemplate } from "@/lib/crm/api";
import { kindLabel } from "@/lib/crm/labels";
import { MERGE_FIELDS } from "@/lib/crm/merge";
import type { Template } from "@/lib/crm/types";

export const Route = createFileRoute("/templates")({ component: Page });

function Page() {
  return (
    <RequireUser>
      <Templates />
    </RequireUser>
  );
}

function Templates() {
  const qc = useQueryClient();
  const q = useQuery({ queryKey: ["templates"], queryFn: () => listTemplates() });
  const [activeId, setActiveId] = useState<number | null>(null);
  const [name, setName] = useState("");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");

  const rows = q.data ?? [];
  const active = rows.find((t) => t.id === activeId) ?? rows[0];

  useEffect(() => {
    if (!active) return;
    setActiveId(active.id);
    setName(active.name);
    setSubject(active.subject);
    setBody(active.body);
  }, [active?.id]);

  const save = useMutation({
    mutationFn: () => {
      if (!active) throw new Error("No template");
      return saveTemplate({ data: { id: active.id, name, subject, body } });
    },
    onSuccess: () => {
      toast.success("Template saved");
      qc.invalidateQueries({ queryKey: ["templates"] });
    },
  });

  if (q.isLoading) return <Skeleton className="h-80" />;

  return (
    <div className="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[16rem_1fr]">
      <div>
        <p className="text-xs uppercase tracking-[0.2em] text-muted">Library</p>
        <h1 className="mt-1 font-display text-3xl">Templates</h1>
        <ul className="mt-5 space-y-1">
          {rows.map((t) => (
            <li key={t.id}>
              <button
                type="button"
                onClick={() => select(t)}
                className={`flex h-11 w-full items-center rounded-md px-3 text-left text-sm ${
                  t.id === active?.id ? "bg-elevated shadow-[var(--shadow-border)]" : "hover:bg-surface"
                }`}
              >
                {t.name}
              </button>
            </li>
          ))}
        </ul>
        <div className="mt-6 rounded-lg bg-surface p-4">
          <p className="text-xs uppercase tracking-wide text-muted">Merge fields</p>
          <ul className="mt-2 space-y-1 font-mono text-xs">
            {MERGE_FIELDS.map((f) => (
              <li key={f.token}>
                <span className="text-primary">{f.token}</span> {f.hint}
              </li>
            ))}
          </ul>
        </div>
      </div>
      {active ? (
        <form
          className="grid gap-4 rounded-xl bg-elevated p-5 shadow-[var(--shadow-border)]"
          onSubmit={(e) => {
            e.preventDefault();
            save.mutate();
          }}
        >
          <p className="text-xs uppercase tracking-wide text-subtle">
            {kindLabel(active.kind)}
          </p>
          <div className="grid gap-2">
            <Label htmlFor="tname">Name</Label>
            <Input id="tname" value={name} onChange={(e) => setName(e.target.value)} />
          </div>
          <div className="grid gap-2">
            <Label htmlFor="tsub">Subject</Label>
            <Input id="tsub" value={subject} onChange={(e) => setSubject(e.target.value)} />
          </div>
          <div className="grid gap-2">
            <Label htmlFor="tbody">Letter</Label>
            <Textarea
              id="tbody"
              className="min-h-80 font-mono text-sm"
              value={body}
              onChange={(e) => setBody(e.target.value)}
            />
          </div>
          <div className="flex justify-end">
            <Button type="submit" disabled={save.isPending}>
              {save.isPending ? "Saving…" : "Save template"}
            </Button>
          </div>
        </form>
      ) : null}
    </div>
  );

  function select(t: Template) {
    setActiveId(t.id);
    setName(t.name);
    setSubject(t.subject);
    setBody(t.body);
  }
}
