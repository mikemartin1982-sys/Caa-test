import { useQuery } from "@tanstack/react-query";
import { createFileRoute, Link } from "@tanstack/react-router";
import { ArrowUpRight, Cloud, Inbox, School, ShieldCheck } from "lucide-react";
import { IntakeDialog } from "@/components/intake-dialog";
import { RequireUser } from "@/components/require-user";
import { CertPill, StatusPill } from "@/components/status-pill";
import { Button } from "@/components/ui/button";
import { Card, CardMeta, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { loadDesk } from "@/lib/crm/api";
import { formatShortDate, kindLabel, siteUrl } from "@/lib/crm/labels";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

export const Route = createFileRoute("/")({ component: Home });

function Home() {
  const { sessionUser } = Route.useRouteContext();
  const { user } = useCurrentUserState();
  if (user || sessionUser) {
    return (
      <RequireUser>
        <Desk />
      </RequireUser>
    );
  }
  return <Landing />;
}

function Landing() {
  return (
    <main className="min-h-dvh bg-bg">
      <header className="flex items-center justify-between px-6 py-5 md:px-10">
        <div>
          <p className="font-display text-xl">CAA Desk</p>
          <p className="text-xs uppercase tracking-wide text-muted">
            Staff CRM for caatest.tech
          </p>
        </div>
        <Button asChild>
          <Link to="/login">Sign in</Link>
        </Button>
      </header>

      <section className="mx-auto grid max-w-6xl gap-12 px-6 py-12 md:grid-cols-[1.1fr_0.9fr] md:px-10 md:py-20">
        <div>
          <p className="text-xs uppercase tracking-[0.22em] text-primary">
            Compliance Assurance Associates
          </p>
          <h1 className="mt-4 font-display text-4xl leading-tight md:text-6xl">
            From the website inquiry to a Method 9 reply — one desk.
          </h1>
          <p className="mt-5 max-w-xl text-base text-muted md:text-lg">
            CAA Desk sits behind caatest.tech. Capture VR and field-school
            demand, track recert windows, and answer from templates — sent from
            your own mail app, so replies come from you.
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Button asChild size="lg">
              <Link to="/login">Open the desk</Link>
            </Button>
            <Button asChild variant="secondary" size="lg">
              <a href="https://caatest.tech" target="_blank" rel="noreferrer">
                View caatest.tech
                <ArrowUpRight className="size-4" />
              </a>
            </Button>
          </div>
        </div>
        <ul className="grid gap-3 self-center">
          {[
            {
              icon: Inbox,
              title: "Website intake",
              body: "VR school, in-person, private on-site, NOV, readings — tagged with the caatest.tech path they came from.",
            },
            {
              icon: School,
              title: "Schools and recerts",
              body: "Public runs, VirtualOpacity, and the 45-day recert window before a cert lapses.",
            },
            {
              icon: Cloud,
              title: "Templates, then your mail app",
              body: "Draft the reply in the CRM, open it in your own mail app, send it, and mark it sent.",
            },
          ].map((item) => (
            <li key={item.title} className="rounded-xl bg-elevated p-5 shadow-[var(--shadow-border)]">
              <item.icon className="size-5 text-primary" />
              <p className="mt-3 font-medium">{item.title}</p>
              <p className="mt-1 text-sm text-muted">{item.body}</p>
            </li>
          ))}
        </ul>
      </section>

      <section className="border-t border-border px-6 py-10 md:px-10">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-6 text-sm text-muted">
          <ShieldCheck className="size-4 text-primary" />
          <span>EPA ALT-152A VirtualOpacity</span>
          <span>115,000+ observers since 2001</span>
          <span>Harvest, AL · Raleigh, NC</span>
        </div>
      </section>
    </main>
  );
}

function Desk() {
  const desk = useQuery({ queryKey: ["desk"], queryFn: () => loadDesk() });
  if (desk.isLoading) {
    return (
      <div className="grid gap-4">
        <Skeleton className="h-10 w-64" />
        <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
        <Skeleton className="h-72" />
      </div>
    );
  }
  if (desk.error) {
    return (
      <p className="text-danger">
        {desk.error instanceof Error ? desk.error.message : "Could not load the desk"}
      </p>
    );
  }
  const data = desk.data;
  if (!data) return null;
  const s = data.summary;

  return (
    <div className="mx-auto max-w-6xl">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.2em] text-muted">Today</p>
          <h1 className="mt-1 font-display text-3xl md:text-4xl">The desk</h1>
        </div>
        <IntakeDialog />
      </div>

      <div className="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4">
        <Stat label="Open inquiries" value={s.openInquiries} to="/inquiries" />
        <Stat label="Recerts in 45 days" value={s.recertsDue} warn={s.recertsDue > 0} to="/clients" />
        <Stat label="Expired certs" value={s.recertsExpired} danger={s.recertsExpired > 0} to="/clients" />
        <Stat label="Unsent replies" value={s.queuedMail} to="/mail" />
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-5">
        <Card className="lg:col-span-3">
          <div className="flex items-center justify-between gap-3">
            <CardTitle>Inquiries from the site</CardTitle>
            <Link to="/inquiries" className="text-sm text-primary hover:underline">
              All
            </Link>
          </div>
          <CardMeta className="mt-1">Newest first, live work on top.</CardMeta>
          <ul className="mt-5 divide-y divide-border">
            {data.inquiries.map((inq) => (
              <li key={inq.id} className="py-3 first:pt-0">
                <Link
                  to="/inquiries/$inquiryId"
                  params={{ inquiryId: String(inq.id) }}
                  className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div>
                    <p className="font-medium">{inq.subject}</p>
                    <p className="text-sm text-muted">
                      {inq.contactName ?? "Unknown"} · {kindLabel(inq.kind)}
                    </p>
                  </div>
                  <StatusPill status={inq.status} />
                </Link>
              </li>
            ))}
          </ul>
        </Card>

        <Card className="lg:col-span-2">
          <CardTitle>Recert window</CardTitle>
          <CardMeta className="mt-1">Certs inside 60 days, plus lapses.</CardMeta>
          <ul className="mt-5 space-y-3">
            {data.recerts.map((c) => (
              <li key={c.id} className="flex items-start justify-between gap-3">
                <div>
                  <p className="font-medium">{c.name}</p>
                  <p className="text-sm text-muted">
                    {c.companyName} · {formatShortDate(c.certExpiresOn)}
                  </p>
                </div>
                <CertPill expiresOn={c.certExpiresOn} />
              </li>
            ))}
          </ul>
        </Card>
      </div>

      <Card className="mt-6">
        <div className="flex items-center justify-between">
          <CardTitle>Smoke schools</CardTitle>
          <Link to="/schools" className="text-sm text-primary hover:underline">
            Calendar
          </Link>
        </div>
        <div className="mt-5 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
          {data.schools.map((school) => (
            <div key={school.id} className="rounded-lg bg-surface p-4">
              <p className="text-xs uppercase tracking-wide text-muted">{school.kind}</p>
              <p className="mt-1 font-medium">{school.title}</p>
              <p className="mt-1 text-sm text-muted">
                {formatShortDate(school.startsOn)}
                {school.city ? ` · ${school.city}` : ""}
              </p>
              <p className="mt-2 font-mono text-sm tabular-nums text-primary">
                {school.enrolled}/{school.seats} seats
              </p>
            </div>
          ))}
        </div>
        <p className="mt-4 text-sm text-muted">
          Public site of record:{" "}
          <a className="text-primary hover:underline" href={siteUrl("/")} target="_blank" rel="noreferrer">
            caatest.tech
          </a>
        </p>
      </Card>
    </div>
  );
}

function Stat({
  label,
  value,
  to,
  warn,
  danger,
}: {
  label: string;
  value: number;
  to: string;
  warn?: boolean;
  danger?: boolean;
}) {
  return (
    <Link
      to={to}
      className="rounded-xl bg-elevated p-4 shadow-[var(--shadow-border)] transition-transform duration-150 hover:-translate-y-0.5"
    >
      <p className="text-xs uppercase tracking-wide text-muted">{label}</p>
      <p
        className={`mt-2 font-display text-3xl tabular-nums ${
          danger ? "text-danger" : warn ? "text-warn" : "text-fg"
        }`}
      >
        {value}
      </p>
    </Link>
  );
}
