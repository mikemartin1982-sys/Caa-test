import { createFileRoute, Link } from "@tanstack/react-router";
import { useState, type FormEvent } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export const Route = createFileRoute("/login")({ component: Login });

function Login() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const res = await fetch("/api/auth/engine-login", {
        method: "POST",
        headers: { "content-type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ username: username.trim(), password }),
      });
      if (!res.ok) {
        let message = "Sign-in failed. Try again.";
        if (res.status === 429) {
          message = "Too many attempts. Wait a minute and try again.";
        } else {
          try {
            const body = (await res.json()) as { message?: string };
            if (body.message) message = body.message;
          } catch {
            /* keep the generic message */
          }
        }
        setError(message);
        return;
      }
      // Hard navigation so the session store and SSR both pick up the new cookie.
      window.location.href = "/";
    } catch {
      setError("Could not reach the server. Check your connection.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="grid min-h-dvh bg-bg lg:grid-cols-2">
      <section className="relative hidden overflow-hidden bg-ink text-ink-fg lg:flex lg:flex-col lg:justify-between p-12">
        <Link to="/" className="font-display text-2xl tracking-tight">
          CAA Desk
        </Link>
        <div className="max-w-md">
          <p className="text-xs uppercase tracking-[0.2em] text-ink-muted">
            Compliance Assurance Associates
          </p>
          <h1 className="mt-4 font-display text-4xl leading-tight">
            The staff book for Method 9 — not another inbox pile.
          </h1>
          <p className="mt-4 text-sm leading-relaxed text-ink-muted">
            Inquiries from caatest.tech, recert windows, smoke-school seats, and
            the templates you actually send.
          </p>
        </div>
        <p className="text-xs text-ink-muted">Since 2001 · 115,000+ observers</p>
      </section>

      <section className="grid place-items-center px-6 py-16">
        <div className="w-full max-w-sm">
          <p className="font-display text-2xl lg:hidden">CAA Desk</p>
          <h2 className="mt-2 font-display text-3xl">Sign in</h2>
          <p className="mt-2 text-sm text-muted">
            Use your CAA staff username and password — the same ones as the
            Chart Recorder.
          </p>

          <form className="mt-8 grid gap-3" onSubmit={onSubmit}>
            <div className="grid gap-1.5">
              <Label htmlFor="username">Username</Label>
              <Input
                id="username"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                autoComplete="username"
                autoCapitalize="none"
                autoCorrect="off"
                spellCheck={false}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                autoComplete="current-password"
              />
            </div>
            {error ? (
              <p role="alert" className="text-sm text-danger">
                {error}
              </p>
            ) : null}
            <Button type="submit" disabled={busy} className="w-full">
              {busy ? "Signing in…" : "Sign in"}
            </Button>
          </form>
          <p className="mt-6 text-xs text-subtle">
            Forgot your password? Ask a CAA administrator to reset it.
          </p>
        </div>
      </section>
    </main>
  );
}
