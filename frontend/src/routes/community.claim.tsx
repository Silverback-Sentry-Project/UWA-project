import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { Receipt, ChevronDown, Upload, FileText, CheckCircle2, Clock } from "lucide-react";
import { useUserPrefs } from "@/lib/user-prefs";
import { toast } from "sonner";

export const Route = createFileRoute("/community/claim")({ component: Claim });

function Claim() {
  const nav = useNavigate();
  const prefs = useUserPrefs();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader
          title="Compensation Claim"
          subtitle="File and track your claim"
          back="/community"
        />
        <div className="px-5 pb-6 space-y-4">
          <div className="text-[11px] text-muted-foreground -mt-1">
            Filing as {prefs.fullName} · {prefs.park}
          </div>
          <div className="bg-card rounded-2xl p-4 shadow-card">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-xl bg-accent/20 grid place-items-center">
                <Receipt className="text-accent-foreground" size={18} />
              </div>
              <div className="flex-1">
                <div className="text-sm font-semibold">UGX 1,200,000</div>
                <div className="text-[11px] text-muted-foreground">Total claimed this year</div>
              </div>
              <Pill tone="success">2 paid</Pill>
            </div>
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card space-y-3">
            <Label>Linked incident</Label>
            <Select value="INC-2041 · Elephants near maize field" />
            <Label>Claim type</Label>
            <Select value="Crop damage" />
            <Label>Estimated loss (UGX)</Label>
            <Input placeholder="450,000" />
            <Label>Bank / mobile money</Label>
            <Select value="MTN Mobile Money · ****0421" />
            <Label>Supporting documents</Label>
            <div className="grid grid-cols-2 gap-2">
              <DocCard title="Receipts.pdf" />
              <button className="rounded-xl border-2 border-dashed border-border h-20 grid place-items-center text-muted-foreground">
                <div className="text-center">
                  <Upload size={18} className="mx-auto" />
                  <div className="text-[11px] mt-1">Upload</div>
                </div>
              </button>
            </div>
          </div>

          <button
            onClick={() => {
              toast.success("Claim submitted", { description: "We'll notify you with updates." });
              nav({ to: "/community" });
            }}
            className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md"
          >
            Submit claim
          </button>

          <div>
            <h2 className="text-sm font-bold mb-3">My claims</h2>
            <div className="space-y-2">
              <ClaimRow
                id="CLM-1031"
                amount="UGX 450,000"
                status="In review"
                icon={Clock}
                tone="warning"
              />
              <ClaimRow
                id="CLM-1027"
                amount="UGX 750,000"
                status="Approved"
                icon={CheckCircle2}
                tone="success"
              />
            </div>
          </div>
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}

function Label({ children }: { children: any }) {
  return (
    <div className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide">
      {children}
    </div>
  );
}
function Input({ placeholder }: { placeholder: string }) {
  return (
    <input
      placeholder={placeholder}
      className="w-full bg-secondary rounded-xl px-3 py-2.5 text-sm outline-none"
    />
  );
}
function Select({ value }: { value: string }) {
  return (
    <button className="w-full flex items-center justify-between bg-secondary rounded-xl px-3 py-2.5 text-sm text-left">
      <span className="truncate">{value}</span>
      <ChevronDown size={16} className="text-muted-foreground shrink-0" />
    </button>
  );
}
function DocCard({ title }: { title: string }) {
  return (
    <div className="rounded-xl bg-secondary p-3 flex items-center gap-2">
      <FileText size={18} className="text-primary" />
      <div className="text-xs font-semibold truncate">{title}</div>
    </div>
  );
}
function ClaimRow({
  id,
  amount,
  status,
  icon: Icon,
  tone,
}: {
  id: string;
  amount: string;
  status: string;
  icon: any;
  tone: "warning" | "success";
}) {
  return (
    <div className="bg-card rounded-2xl p-3 shadow-card flex items-center gap-3">
      <div
        className={`h-10 w-10 rounded-xl grid place-items-center ${tone === "warning" ? "bg-warning/25" : "bg-success/15 text-success"}`}
      >
        <Icon size={18} />
      </div>
      <div className="flex-1 min-w-0">
        <div className="text-sm font-semibold">{id}</div>
        <div className="text-[11px] text-muted-foreground">{amount}</div>
      </div>
      <Pill tone={tone}>{status}</Pill>
    </div>
  );
}
