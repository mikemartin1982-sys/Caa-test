import { Link, useRouterState } from "@tanstack/react-router";
import {
  BookOpen,
  Building2,
  CalendarDays,
  Inbox,
  LayoutDashboard,
  Mail,
  Menu,
  Settings2,
} from "lucide-react";
import { type ReactNode, useState } from "react";
import { UserButton } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { cn } from "@/lib/utils";
import { Button } from "./ui/button";
import { Sheet, SheetContent } from "./ui/sheet";
import { Skeleton } from "./ui/skeleton";

const NAV = [
  { to: "/", label: "Desk", icon: LayoutDashboard },
  { to: "/inquiries", label: "Inquiries", icon: Inbox },
  { to: "/clients", label: "Clients", icon: Building2 },
  { to: "/schools", label: "Schools", icon: CalendarDays },
  { to: "/templates", label: "Templates", icon: BookOpen },
  { to: "/mail", label: "Replies", icon: Mail },
  { to: "/settings", label: "Help", icon: Settings2 },
] as const;

function NavLinks({ onNavigate }: { onNavigate?: () => void }) {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  return (
    <nav className="flex flex-col gap-1">
      {NAV.map((item) => {
        const active =
          item.to === "/"
            ? pathname === "/"
            : pathname === item.to || pathname.startsWith(`${item.to}/`);
        const Icon = item.icon;
        return (
          <Link
            key={item.to}
            to={item.to}
            onClick={onNavigate}
            className={cn(
              "flex h-11 items-center gap-3 rounded-md px-3 text-sm transition-colors duration-150",
              active
                ? "bg-white/10 text-ink-fg"
                : "text-ink-muted hover:bg-white/5 hover:text-ink-fg",
            )}
          >
            <Icon className="size-4 shrink-0" />
            {item.label}
          </Link>
        );
      })}
    </nav>
  );
}

function Brand() {
  return (
    <Link to="/" className="block px-3 py-4">
      <p className="font-display text-xl tracking-tight text-ink-fg">CAA Desk</p>
      <p className="mt-1 text-xs tracking-wide text-ink-muted uppercase">
        Staff CRM · caatest.tech
      </p>
    </Link>
  );
}

export function AppShell({ children }: { children: ReactNode }) {
  const { user, isPending } = useCurrentUserState();
  const [open, setOpen] = useState(false);

  return (
    <div className="min-h-dvh bg-bg text-fg">
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col bg-ink text-ink-fg md:flex">
        <Brand />
        <div className="flex-1 overflow-y-auto px-2 pb-4">
          <NavLinks />
        </div>
        <p className="px-5 pb-6 text-xs text-ink-muted">
          Compliance Assurance Associates
        </p>
      </aside>

      <div className="md:pl-60">
        <header className="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-border bg-bg/90 px-4 backdrop-blur-sm">
          <Sheet open={open} onOpenChange={setOpen}>
            <Button
              variant="ghost"
              size="icon"
              className="md:hidden"
              onClick={() => setOpen(true)}
              aria-label="Open menu"
            >
              <Menu className="size-5" />
            </Button>
            <SheetContent>
              <Brand />
              <NavLinks onNavigate={() => setOpen(false)} />
            </SheetContent>
          </Sheet>
          <p className="font-display text-base md:hidden">CAA Desk</p>
          <div className="ml-auto flex items-center">
            {isPending ? (
              <Skeleton className="h-8 w-28" />
            ) : user ? (
              <UserButton />
            ) : null}
          </div>
        </header>
        <div className="px-4 py-6 md:px-8 md:py-8">{children}</div>
      </div>
    </div>
  );
}
