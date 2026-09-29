import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { importPastedEmail } from "@/lib/crm/api";
import { kindLabel } from "@/lib/crm/labels";

/**
 * Manual path for website-form emails until the mailbox reader is connected:
 * paste the notification (headers optional) and it is filed exactly as the
 * automatic import will file it.
 */
export function PasteEmailDialog() {
  const [open, setOpen] = useState(false);
  const [text, setText] = useState("");
  const navigate = useNavigate();
  const qc = useQueryClient();

  const mutation = useMutation({
    mutationFn: () => importPastedEmail({ data: { text } }),
    onSuccess: async (r) => {
      if (r.created) {
        toast.success(`Filed as ${kindLabel(r.kind)}`);
      } else {
        toast.message("That email was already filed — opening it");
      }
      await qc.invalidateQueries();
      setOpen(false);
      setText("");
      if (r.inquiryId) {
        await navigate({ to: "/inquiries/$inquiryId", params: { inquiryId: String(r.inquiryId) } });
      }
    },
    onError: (err) => toast.error(err instanceof Error ? err.message : "Could not read that email"),
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">Paste email</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogTitle>Paste a website email</DialogTitle>
        <DialogDescription>
          Paste a form notification from client@ — New Client Account, VR,
          Private Smoke School, or Notice of Violation. The customer is read
          from the form, not the sender.
        </DialogDescription>
        <form
          className="mt-5 grid gap-4"
          onSubmit={(e) => {
            e.preventDefault();
            mutation.mutate();
          }}
        >
          <div className="grid gap-2">
            <Label htmlFor="pasted">Email text</Label>
            <Textarea
              id="pasted"
              required
              rows={12}
              value={text}
              onChange={(e) => setText(e.target.value)}
              placeholder="Subject: … A visitor on the NEW CLIENT ACCOUNT page has completed the form: …"
            />
          </div>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={mutation.isPending || text.trim().length < 20}>
              {mutation.isPending ? "Filing…" : "File it"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
