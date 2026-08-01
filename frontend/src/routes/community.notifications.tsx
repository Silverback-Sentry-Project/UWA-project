import { createFileRoute } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader } from "@/components/ui-prototype";
import { CheckCircle2, AlertOctagon, Megaphone, Receipt } from "lucide-react";

export const Route = createFileRoute("/community/notifications")({ component: Notifications });

const ITEMS = [
  { icon: CheckCircle2, title: "Your sighting was confirmed", time: "12m", tone: "bg-success/15 text-success", unread: true },
  { icon: AlertOctagon, title: "New alert: Elephants near Kichwamba", time: "1h", tone: "bg-destructive/15 text-destructive", unread: true },
  { icon: Receipt, title: "Claim CLM-1027 approved — UGX 750,000", time: "Today", tone: "bg-accent/20 text-foreground", unread: false },
  { icon: Megaphone, title: "Ranger patrol scheduled this afternoon", time: "Yesterday", tone: "bg-info/15 text-info", unread: false },
];

function Notifications() {
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Notifications" back="/community" />
        <div className="px-5 pb-6 space-y-2">
          {ITEMS.map((n, i) => {
            const Icon = n.icon;
            return (
              <div key={i} className={`bg-card rounded-2xl p-3 shadow-card flex items-start gap-3 ${n.unread ? "ring-1 ring-primary/20" : ""}`}>
                <div className={`h-10 w-10 rounded-xl grid place-items-center ${n.tone}`}><Icon size={18} /></div>
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-semibold">{n.title}</div>
                  <div className="text-[11px] text-muted-foreground">{n.time}</div>
                </div>
                {n.unread && <div className="h-2 w-2 rounded-full bg-primary mt-2" />}
              </div>
            );
          })}
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}