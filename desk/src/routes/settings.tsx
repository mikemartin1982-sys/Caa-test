import { RequireUser } from "@/components/require-user";
import { Card, CardMeta, CardTitle } from "@/components/ui/card";
import { SHARED_MAILBOX } from "@/lib/crm/labels";

import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/settings")({ component: Page });

function Page() {
  return (
    <RequireUser>
      <Settings />
    </RequireUser>
  );
}

function Settings() {
  return (
    <div className="mx-auto max-w-3xl">
      <p className="text-xs uppercase tracking-[0.2em] text-muted">Help</p>
      <h1 className="mt-1 font-display text-3xl">How email works</h1>

      <Card className="mt-8">
        <CardTitle>Bringing an inquiry in</CardTitle>
        <CardMeta className="mt-1">
          Website forms arrive in {SHARED_MAILBOX} from the registrar address.
          Open the email, copy it, and use “Paste email” on the Inquiries page.
          The customer is read from the form, and pasting the same email twice
          never makes a duplicate.
        </CardMeta>
      </Card>

      <Card className="mt-4">
        <CardTitle>Sending a reply</CardTitle>
        <CardMeta className="mt-1">
          Pick a template on the inquiry, then “Open in my mail app”. It is sent
          from your own mailbox with {SHARED_MAILBOX} copied. Come back and tap
          “mark as sent” so the team can see who answered.
        </CardMeta>
      </Card>

      <Card className="mt-4">
        <CardTitle>Public site</CardTitle>
        <p className="mt-2 text-sm">
          Marketing and registration stay on{" "}
          <a className="text-primary hover:underline" href="https://caatest.tech" target="_blank" rel="noreferrer">
            caatest.tech
          </a>
          . This app is the staff layer behind it.
        </p>
      </Card>
    </div>
  );
}
