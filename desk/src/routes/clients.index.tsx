import { useQuery } from "@tanstack/react-query";
import { createFileRoute, Link } from "@tanstack/react-router";
import { RequireUser } from "@/components/require-user";
import { Skeleton } from "@/components/ui/skeleton";
import { listCompanies } from "@/lib/crm/api";

export const Route = createFileRoute("/clients/")({ component: Page });

function Page() {
  return (
    <RequireUser>
      <Clients />
    </RequireUser>
  );
}

function Clients() {
  const q = useQuery({ queryKey: ["companies"], queryFn: () => listCompanies() });
  if (q.isLoading) return <Skeleton className="h-80" />;
  const rows = q.data ?? [];
  return (
    <div className="mx-auto max-w-6xl">
      <p className="text-xs uppercase tracking-[0.2em] text-muted">Book</p>
      <h1 className="mt-1 font-display text-3xl">Clients</h1>
      <p className="mt-2 max-w-xl text-sm text-muted">
        Plants and firms that come through caatest.tech — utilities, steel,
        cement, chemical.
      </p>
      <ul className="mt-6 grid gap-3 sm:grid-cols-2">
        {rows.map((c) => (
          <li key={c.id}>
            <Link
              to="/clients/$clientId"
              params={{ clientId: String(c.id) }}
              className="block rounded-xl bg-elevated p-5 shadow-[var(--shadow-border)] transition-transform duration-150 hover:-translate-y-0.5"
            >
              <p className="font-medium">{c.name}</p>
              <p className="mt-1 text-sm text-muted">
                {c.industry}
                {c.city ? ` · ${c.city}, ${c.state}` : ""}
              </p>
              <p className="mt-3 text-xs uppercase tracking-wide text-subtle">
                {c.contactCount} {c.contactCount === 1 ? "contact" : "contacts"}
              </p>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
