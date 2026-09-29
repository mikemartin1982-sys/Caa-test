import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
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
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { createInquiry, listSchools } from "@/lib/crm/api";
import { INQUIRY_KIND_LABEL } from "@/lib/crm/labels";
import { INQUIRY_KINDS, type InquiryKind } from "@/lib/crm/types";

export function IntakeDialog({ label = "New inquiry" }: { label?: string }) {
  const [open, setOpen] = useState(false);
  const [kind, setKind] = useState<InquiryKind>("vr_school");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");
  const [contactName, setContactName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [contactPhone, setContactPhone] = useState("");
  const [companyName, setCompanyName] = useState("");
  const [schoolId, setSchoolId] = useState<number | "">("");
  const navigate = useNavigate();
  const qc = useQueryClient();
  const schools = useQuery({ queryKey: ["schools"], queryFn: () => listSchools() });

  const mutation = useMutation({
    mutationFn: () =>
      createInquiry({
        data: {
          kind,
          subject,
          body,
          contactName,
          contactEmail,
          contactPhone,
          companyName,
          schoolId: schoolId === "" ? null : schoolId,
        },
      }),
    onSuccess: async (result) => {
      toast.success("Inquiry captured from the website flow");
      await qc.invalidateQueries();
      setOpen(false);
      setSubject("");
      setBody("");
      setContactName("");
      setContactEmail("");
      setContactPhone("");
      setCompanyName("");
      setSchoolId("");
      if (result.id) {
        await navigate({ to: "/inquiries/$inquiryId", params: { inquiryId: String(result.id) } });
      }
    },
    onError: (err) => {
      toast.error(err instanceof Error ? err.message : "Could not save inquiry");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>{label}</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogTitle>Website intake</DialogTitle>
        <DialogDescription>
          Same fields a caatest.tech inquiry would land with — VR school, field
          school, private on-site, or VEO support.
        </DialogDescription>
        <form
          className="mt-5 grid gap-4"
          onSubmit={(e) => {
            e.preventDefault();
            mutation.mutate();
          }}
        >
          <div className="grid gap-2">
            <Label htmlFor="kind">From page</Label>
            <select
              id="kind"
              className="h-11 rounded-md border border-border bg-elevated px-3 text-sm"
              value={kind}
              onChange={(e) => setKind(e.target.value as InquiryKind)}
            >
              {INQUIRY_KINDS.map((k) => (
                <option key={k} value={k}>
                  {INQUIRY_KIND_LABEL[k]}
                </option>
              ))}
            </select>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="subject">Subject</Label>
            <Input
              id="subject"
              required
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              placeholder="Four observers for Birmingham"
            />
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="name">Contact</Label>
              <Input
                id="name"
                required
                value={contactName}
                onChange={(e) => setContactName(e.target.value)}
                placeholder="Name"
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                value={contactEmail}
                onChange={(e) => setContactEmail(e.target.value)}
                placeholder="ehs@plant.com"
              />
            </div>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="phone">Phone</Label>
              <Input
                id="phone"
                value={contactPhone}
                onChange={(e) => setContactPhone(e.target.value)}
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="company">Company</Label>
              <Input
                id="company"
                value={companyName}
                onChange={(e) => setCompanyName(e.target.value)}
                placeholder="Plant or firm"
              />
            </div>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="school">Attach school</Label>
            <select
              id="school"
              className="h-11 rounded-md border border-border bg-elevated px-3 text-sm"
              value={schoolId}
              onChange={(e) =>
                setSchoolId(e.target.value ? Number(e.target.value) : "")
              }
            >
              <option value="">None yet</option>
              {(schools.data ?? []).map((s) => (
                <option key={s.id} value={s.id}>
                  {s.title}
                </option>
              ))}
            </select>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="body">Message</Label>
            <Textarea
              id="body"
              value={body}
              onChange={(e) => setBody(e.target.value)}
              placeholder="What they asked from the site…"
            />
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? "Saving…" : "File inquiry"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
