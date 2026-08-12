import { createFileRoute } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { AlertOctagon, Megaphone, Bell, MapPin } from "lucide-react";
import { useUserPrefs } from "@/lib/user-prefs";

export const Route = createFileRoute("/community/alerts")({ component: Alerts });

const ALERTS = [
  {
    icon: AlertOctagon,
    title: "Elephant herd movement",
    body: "Herd of ~12 elephants heading toward Kichwamba village. Keep distance.",
    time: "10 min ago",
    zone: "Kichwamba",
    tone: "danger" as const,
  },
  {
    icon: Megaphone,
    title: "Buffalo sighting",
    body: "Single buffalo seen near the river crossing.",
    time: "2 hr ago",
    zone: "Buliisa",
    tone: "warning" as const,
  },
  {
    icon: Bell,
    title: "Ranger patrol scheduled",
    body: "Patrol team in your area today from 14:00 to 18:00.",
    time: "Today",
    zone: "Pakwach",
    tone: "info" as const,
  },
  {
    icon: AlertOctagon,
    title: "Snare trap warning",
    body: "Multiple snares discovered. Report any you find.",
    time: "Yesterday",
    zone: "Wairingo",
    tone: "danger" as const,
  },
];

function Alerts() {
  const prefs = useUserPrefs();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader
          title="Community Alerts"
          subtitle={`Alerts in ${prefs.park}`}
          back="/community"
        />
        <div className="px-5 pb-6 space-y-3">
          <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-1 px-1">
            {["All", "Wildlife", "Safety", "Patrols", "Trapping"].map((f, i) => (
              <button
                key={f}
                className={`px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap ${i === 0 ? "bg-primary text-primary-foreground" : "bg-secondary text-muted-foreground"}`}
              >
                {f}
              </button>
            ))}
          </div>
          {ALERTS.map((a, i) => {
            const Icon = a.icon;
            const c = {
              danger: "bg-destructive/15 text-destructive",
              warning: "bg-warning/25 text-foreground",
              info: "bg-info/15 text-info",
            }[a.tone];
            return (
              <div key={i} className="bg-card rounded-2xl p-4 shadow-card">
                <div className="flex items-start gap-3">
                  <div className={`h-10 w-10 rounded-xl grid place-items-center shrink-0 ${c}`}>
                    <Icon size={18} />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                      <div className="text-sm font-semibold truncate">{a.title}</div>
                      <Pill tone={a.tone}>
                        {a.tone === "danger" ? "Urgent" : a.tone === "warning" ? "Caution" : "Info"}
                      </Pill>
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">{a.body}</p>
                    <div className="flex items-center gap-3 mt-2 text-[11px] text-muted-foreground">
                      <span className="flex items-center gap-1">
                        <MapPin size={11} />
                        {a.zone}
                      </span>
                      <span>·</span>
                      <span>{a.time}</span>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}
