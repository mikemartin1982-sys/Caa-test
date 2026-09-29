import type { ReactNode } from "react";
import { RedirectToSignIn } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { AppShell } from "./app-shell";
import { Skeleton } from "./ui/skeleton";

export function RequireUser({ children }: { children: ReactNode }) {
  const { user, isPending } = useCurrentUserState();
  if (isPending) {
    return (
      <AppShell>
        <div className="grid gap-4">
          <p className="font-display text-3xl">CAA Desk</p>
          <p className="text-sm text-muted">Loading the desk…</p>
          <Skeleton className="h-40 w-full" />
          <Skeleton className="h-64 w-full" />
        </div>
      </AppShell>
    );
  }
  if (!user) return <RedirectToSignIn />;
  return <AppShell>{children}</AppShell>;
}
