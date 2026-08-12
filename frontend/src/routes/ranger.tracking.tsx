import { createFileRoute } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { RangerTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { CheckCircle2, Clock, AlertOctagon, Filter } from "lucide-react";

export const Route = createFileRoute("/ranger/tracking")({ component: Tracking });

const ITEMS = [
  {
    id: "INC-2049",
    title: "Elephant herd · Kichwamba",
    status: "En route",
    tone: "warning" as const,
    time: "Now",
    priority: "Urgent",
  },
  {
    id: "INC-2046",
    title: "Snare removal · Wairingo",
    status: "On site",
    tone: "info" as const,
    time: "2h",
    priority: "High",
  },
  {
    id: "INC-2041",
    title: "Buffalo · Buliisa",
    status: "Resolved",
    tone: "success" as const,
    time: "Yesterday",
    priority: "Medium",
  },
  {
    id: "INC-2039",
    title: "Crop damage · Pakwach",
    status: "Resolved",
    tone: "success" as const,
    time: "2d",
    priority: "Medium",
  },
  {
    id: "INC-2034",
    title: "Suspicious activity",
    status: "Escalated",
    tone: "danger" as const,
    time: "3d",
    priority: "Urgent",
  },
];

function Tracking() {
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Incident Tracking" subtitle="Your assigned cases" />
        <div className="px-5 pb-6 space-y-4">
          <div className="grid grid-cols-3 gap-2">
            <KPI icon={CheckCircle2} value="24" label="Resolved" tone="success" />
            <KPI icon={Clock} value="3" label="In progress" tone="warning" />
            <KPI icon={AlertOctagon} value="1" label="Escalated" tone="danger" />
          </div>

          <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-1 px-1">
            {["All", "Urgent", "In progress", "Resolved", "Escalated"].map((f, i) => (
              <button
                key={f}
                className={`px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap ${i === 0 ? "bg-primary text-primary-foreground" : "bg-secondary text-muted-foreground"}`}
              >
                {f}
              </button>
            ))}
          </div>

          <div className="space-y-2">
            {ITEMS.map((it, i) => (
              <div key={i} className="bg-card rounded-2xl p-3 shadow-card">
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-[10px] font-bold text-muted-foreground">{it.id}</span>
                  <Pill tone={it.tone}>{it.status}</Pill>
                  <div className="flex-1" />
                  <span className="text-[11px] text-muted-foreground">{it.time}</span>
                </div>
                <div className="text-sm font-semibold">{it.title}</div>
                <div className="mt-2 h-1.5 rounded-full bg-secondary overflow-hidden">
                  <div
                    className={`h-full ${it.tone === "success" ? "bg-success w-full" : it.tone === "warning" ? "bg-warning w-1/2" : it.tone === "danger" ? "bg-destructive w-3/4" : "bg-info w-2/3"}`}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
        <RangerTabBar />
      </div>
    </PhoneFrame>
  );
}

function KPI({
  icon: Icon,
  value,
  label,
  tone,
}: {
  icon: any;
  value: string;
  label: string;
  tone: "success" | "warning" | "danger";
}) {
  const c = {
    success: "bg-success/15 text-success",
    warning: "bg-warning/25 text-foreground",
    danger: "bg-destructive/15 text-destructive",
  }[tone];
  return (
    <div className="bg-card rounded-2xl p-3 shadow-card">
      <div className={`h-8 w-8 rounded-lg grid place-items-center ${c} mb-1.5`}>
        <Icon size={15} />
      </div>
      <div className="text-lg font-bold leading-tight">{value}</div>
      <div className="text-[10px] text-muted-foreground">{label}</div>
    </div>
  );
}
