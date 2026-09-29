import { useQuery } from "@tanstack/react-query";
import { createFileRoute, Link } from "@tanstack/react-router";
import { RequireUser } from "@/components/require-user";
import { CertPill, StatusPill } from "@/components/status-pill";
import { Card, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getCompany } from "@/lib/crm/api";
import { formatShortDate, kindLabel } from "@/lib/crm/labels";

export const Route = createFileRoute("/clients/$clientId")({ component: Page });

function Page() {
  const { clientId } = Route.useParams();
  return (
    <RequireUser>
      <Client id={Number(clientId)} />
    </RequireUser>
  );
}

function Client({ id }: { id: number }) {
  const q = useQuery({
    queryKey: ["company", id],
    queryFn: () => getCompany({ data: { id } }),
  });
  if (q.isLoading) return <Skeleton className="h-80" />;
  const data = q.data;
  if (!data?.company) return <p className="text-muted">Client not found.</p>;
  const { company, contacts, inquiries } = data;
  return (
    <div className="mx-auto max-w-6xl">
      <Link to="/clients" className="text-sm text-primary hover:underline">
        All clients
      </Link>
      <h1 className="mt-2 font-display text-3xl">{company.name}</h1>
      <p className="mt-1 text-sm text-muted">
        {company.industry}
        {company.city ? ` · ${company.city}, ${company.state}` : ""}
      </p>
      {company.notes ? (
        <p className="mt-4 max-w-2xl text-sm leading-relaxed">{company.notes}</p>
      ) : null}

      <div className="mt-8 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardTitle>Contacts</CardTitle>
          <ul className="mt-4 space-y-4">
            {contacts.map((c) => (
              <li key={c.id} className="flex items-start justify-between gap-3">
                <div>
                  <p className="font-medium">{c.name}</p>
                  <p className="text-sm text-muted">{c.role}</p>
                  <p className="text-sm">{c.email}</p>
                  <p className="mt-1 text-xs text-subtle">
                    Cert {formatShortDate(c.certExpiresOn)}
                  </p>
                </div>
                <CertPill expiresOn={c.certExpiresOn} />
              </li>
            ))}
          </ul>
        </Card>
        <Card>
          <CardTitle>Inquiries</CardTitle>
          <ul className="mt-4 space-y-3">
            {inquiries.map((inq) => (
              <li key={inq.id}>
                <Link
                  to="/inquiries/$inquiryId"
                  params={{ inquiryId: String(inq.id) }}
                  className="flex items-start justify-between gap-3"
                >
                  <div>
                    <p className="font-medium">{inq.subject}</p>
                    <p className="text-sm text-muted">
                      {kindLabel(inq.kind)} · {inq.contactName}
                    </p>
                  </div>
                  <StatusPill status={inq.status} />
                </Link>
              </li>
            ))}
          </ul>
        </Card>
      </div>
    </div>
  );
}
