import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { RequireUser } from "@/components/require-user";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { listSchools } from "@/lib/crm/api";
import { formatShortDate, schoolKindLabel, siteUrl } from "@/lib/crm/labels";

export const Route = createFileRoute("/schools")({ component: Page });

function Page() {
  return (
    <RequireUser>
      <Schools />
    </RequireUser>
  );
}

function Schools() {
  const q = useQuery({ queryKey: ["schools"], queryFn: () => listSchools() });
  if (q.isLoading) return <Skeleton className="h-80" />;
  const rows = q.data ?? [];
  return (
    <div className="mx-auto max-w-6xl">
      <p className="text-xs uppercase tracking-[0.2em] text-muted">Calendar</p>
      <h1 className="mt-1 font-display text-3xl">Smoke schools</h1>
      <p className="mt-2 max-w-xl text-sm text-muted">
        VirtualOpacity (ALT-152A), public field runs, and private on-site
        schools. Public pages live on{" "}
        <a className="text-primary hover:underline" href={siteUrl("/in-person-smoke-schools")} target="_blank" rel="noreferrer">
          caatest.tech
        </a>
        .
      </p>
      <ul className="mt-6 grid gap-3">
        {rows.map((s) => {
          const fill = s.seats > 0 ? Math.round((s.enrolled / s.seats) * 100) : 0;
          return (
            <li
              key={s.id}
              className="rounded-xl bg-elevated p-5 shadow-[var(--shadow-border)] md:grid md:grid-cols-[1fr_auto] md:items-center md:gap-6"
            >
              <div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge tone="ink">{schoolKindLabel(s.kind)}</Badge>
                  <Badge tone={s.status === "open" ? "ok" : "warn"}>{s.status}</Badge>
                </div>
                <h2 className="mt-3 font-display text-xl">{s.title}</h2>
                <p className="mt-1 text-sm text-muted">
                  {formatShortDate(s.startsOn)}
                  {s.endsOn ? ` – ${formatShortDate(s.endsOn)}` : ""}
                  {s.city ? ` · ${s.city}${s.state ? `, ${s.state}` : ""}` : ""}
                </p>
                <p className="mt-1 text-sm">Instructor {s.instructor}</p>
                {s.notes ? <p className="mt-3 text-sm text-muted">{s.notes}</p> : null}
              </div>
              <div className="mt-4 md:mt-0 md:w-44">
                <p className="font-mono text-sm tabular-nums text-primary">
                  {s.enrolled}/{s.seats} seats
                </p>
                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-border">
                  <div
                    className="h-full bg-primary"
                    style={{ width: `${Math.min(fill, 100)}%` }}
                  />
                </div>
              </div>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
