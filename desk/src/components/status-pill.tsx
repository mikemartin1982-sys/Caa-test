import { Badge } from "@/components/ui/badge";
import { statusLabel } from "@/lib/crm/labels";
import type { InquiryStatus } from "@/lib/crm/types";

const TONE: Record<InquiryStatus, "default" | "muted" | "ok" | "warn" | "danger" | "ink"> = {
  new: "default",
  qualified: "ink",
  scheduled: "ok",
  waiting: "warn",
  won: "ok",
  lost: "muted",
};

export function StatusPill({ status }: { status: string }) {
  const tone = TONE[status as InquiryStatus] ?? "muted";
  return <Badge tone={tone}>{statusLabel(status)}</Badge>;
}

export function CertPill({ expiresOn }: { expiresOn: string | null }) {
  if (!expiresOn) return <Badge tone="muted">No cert on file</Badge>;
  const day = expiresOn.slice(0, 10);
  const then = Date.parse(`${day}T00:00:00Z`);
  const days = Math.ceil((then - Date.now()) / 86_400_000);
  if (days < 0) return <Badge tone="danger">Expired</Badge>;
  if (days <= 45) return <Badge tone="warn">Due in {days}d</Badge>;
  return <Badge tone="ok">Current</Badge>;
}
