import { createFileRoute, Link } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { RangerTabBar, ScreenHeader, Pill, StatCard } from "@/components/ui-prototype";
import {
  AlertOctagon,
  AlertTriangle,
  Filter,
  Calendar,
  CheckCircle2,
  Clock,
  MapPin,
  Megaphone,
  User,
} from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/ranger/")({ component: RangerDash });

const INCIDENTS = [
  {
    id: "INC-2049",
    title: "Elephant herd near maize fields",
    zone: "Kichwamba",
    time: "Just now",
    priority: "Urgent" as const,
    icon: AlertOctagon,
  },
  {
    id: "INC-2048",
    title: "Snare trap reported",
    zone: "Wairingo",
    time: "12 min",
    priority: "High" as const,
    icon: AlertTriangle,
  },
  {
    id: "INC-2047",
    title: "Buffalo blocking road",
    zone: "Buliisa",
    time: "32 min",
    priority: "Medium" as const,
    icon: AlertTriangle,
  },
];

const ALERTS = [
  {
    who: "Amara N.",
    text: "Grey crowned crane sighting",
    zone: "Pakwach",
    time: "5m",
    assigned: false,
  },
  {
    who: "Ranger Joseph",
    text: "Patrol heading north sector",
    zone: "Sector 4",
    time: "18m",
    assigned: true,
  },
  {
    who: "Community",
    text: "Suspicious activity near boundary",
    zone: "Wairingo",
    time: "1h",
    assigned: false,
  },
];

function RangerDash() {
  const [assigned, setAssigned] = useState<Record<number, boolean>>({});
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <div className="gradient-forest text-white pb-6 rounded-b-[28px]">
          <ScreenHeader
            dark
            title="Ranger Console"
            subtitle="Sector 3 · On duty"
            right={
              <Link
                to="/ranger/profile"
                className="h-9 w-9 rounded-full bg-white/15 grid place-items-center"
              >
                <User size={16} />
              </Link>
            }
          />
          <div className="px-5 flex gap-3">
            <StatCard icon={CheckCircle2} label="Resolved (7d)" value="24" tone="success" />
            <StatCard icon={Clock} label="Pending" value="6" tone="danger" />
            <StatCard icon={MapPin} label="Active zones" value="3" tone="accent" />
          </div>
        </div>

        <div className="px-5 pt-5 pb-6 space-y-5">
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-2 bg-card rounded-full px-3 py-1.5 shadow-card text-xs font-semibold">
              <Filter size={13} />
              Zone: All
            </button>
            <button className="flex items-center gap-2 bg-card rounded-full px-3 py-1.5 shadow-card text-xs font-semibold">
              <Calendar size={13} />
              Today
            </button>
            <div className="flex-1" />
            <Pill tone="success">Live</Pill>
          </div>

          <div>
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-sm font-bold">Active incidents</h2>
              <span className="text-xs text-muted-foreground">{INCIDENTS.length} new</span>
            </div>
            <div className="space-y-2">
              {INCIDENTS.map((inc) => {
                const Icon = inc.icon;
                const tone =
                  inc.priority === "Urgent"
                    ? "danger"
                    : inc.priority === "High"
                      ? "warning"
                      : "info";
                const c = {
                  danger: "bg-destructive/15 text-destructive",
                  warning: "bg-warning/25 text-foreground",
                  info: "bg-info/15 text-info",
                }[tone];
                return (
                  <div key={inc.id} className="bg-card rounded-2xl p-3 shadow-card">
                    <div className="flex items-start gap-3">
                      <div className={`h-10 w-10 rounded-xl grid place-items-center shrink-0 ${c}`}>
                        <Icon size={18} />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="text-[10px] font-bold text-muted-foreground">
                            {inc.id}
                          </span>
                          <Pill tone={tone}>{inc.priority}</Pill>
                        </div>
                        <div className="text-sm font-semibold mt-0.5 truncate">{inc.title}</div>
                        <div className="text-[11px] text-muted-foreground flex items-center gap-2 mt-0.5">
                          <MapPin size={11} />
                          {inc.zone} · {inc.time}
                        </div>
                      </div>
                      <Link
                        to="/ranger/incident"
                        className="bg-primary text-primary-foreground px-3 py-2 rounded-xl text-[11px] font-bold shrink-0 shadow-sm"
                      >
                        Attend To
                      </Link>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-sm font-bold">Active alerts</h2>
              <a className="text-xs font-semibold text-primary">See all</a>
            </div>
            <div className="space-y-2">
              {ALERTS.map((a, i) => {
                const isAssigned = a.assigned || assigned[i];
                return (
                  <div
                    key={i}
                    className="bg-card rounded-2xl p-3 shadow-card flex items-center gap-3"
                  >
                    <div className="h-9 w-9 rounded-xl bg-secondary grid place-items-center">
                      <Megaphone size={16} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="text-sm font-semibold truncate">{a.text}</div>
                      <div className="text-[11px] text-muted-foreground">
                        {a.who} · {a.zone} · {a.time}
                      </div>
                    </div>
                    {isAssigned ? (
                      <Pill tone="success">Assigned</Pill>
                    ) : (
                      <button
                        onClick={() => setAssigned((s) => ({ ...s, [i]: true }))}
                        className="bg-secondary text-foreground px-3 py-1.5 rounded-lg text-[11px] font-bold"
                      >
                        Take
                      </button>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
        <RangerTabBar />
      </div>
    </PhoneFrame>
  );
}
