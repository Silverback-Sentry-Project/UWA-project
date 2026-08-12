import { createFileRoute, Link } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, Pill, ScreenHeader } from "@/components/ui-prototype";
import {
  Camera,
  AlertTriangle,
  Megaphone,
  Receipt,
  ChevronRight,
  MapPin,
  Leaf,
  TreePine,
  Bird,
} from "lucide-react";
import { useUserPrefs, initials } from "@/lib/user-prefs";

export const Route = createFileRoute("/community/")({
  component: Dash,
});

function Dash() {
  const prefs = useUserPrefs();
  const firstName = prefs.fullName.split(/\s+/)[0] || "Friend";
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <div className="gradient-forest text-white pb-16 rounded-b-[32px]">
          <div className="text-white">
            <StatusBarSpacer />
            <div className="flex items-center gap-3 px-5 pt-2 pb-4">
              <Link
                to="/community/profile"
                className="h-11 w-11 rounded-full bg-white/15 grid place-items-center shrink-0 ring-2 ring-white/20 text-sm font-bold"
              >
                {initials(prefs.fullName)}
              </Link>
              <div className="min-w-0">
                <h1
                  className="text-xl font-bold truncate"
                  style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
                >
                  Hi, {firstName}
                </h1>
                <p className="text-xs text-white/70">
                  {prefs.park} · {prefs.language}
                </p>
              </div>
            </div>
          </div>
          <div className="px-5 -mb-10">
            <div className="bg-card text-foreground rounded-2xl p-4 shadow-card flex items-center gap-3">
              <div className="h-12 w-12 rounded-2xl bg-accent/20 grid place-items-center">
                <TreePine className="text-accent-foreground" size={22} />
              </div>
              <div className="flex-1">
                <div className="text-xs text-muted-foreground">Community impact this month</div>
                <div className="text-lg font-bold">12 reports · 4 resolved</div>
              </div>
              <Pill tone="success">+18%</Pill>
            </div>
          </div>
        </div>

        <div className="px-5 pt-16 space-y-5 pb-6">
          <Link
            to="/community/alerts"
            className="flex items-center gap-3 bg-card rounded-2xl p-4 shadow-card"
          >
            <div className="h-12 w-12 rounded-2xl bg-gradient-to-br from-[oklch(0.55_0.12_230)] to-[oklch(0.7_0.1_220)] text-white grid place-items-center shadow-sm">
              <Megaphone size={22} />
            </div>
            <div className="flex-1 min-w-0">
              <div className="text-sm font-bold">Community Alerts</div>
              <div className="text-[11px] text-muted-foreground">3 new alerts in {prefs.park}</div>
            </div>
            <Pill tone="danger">3</Pill>
            <ChevronRight size={16} className="text-muted-foreground" />
          </Link>

          <div>
            <h2 className="text-sm font-bold mb-3">Quick report</h2>
            <div className="grid grid-cols-3 gap-3">
              <Action
                to="/community/sighting"
                icon={Camera}
                label="Wildlife Sighting"
                tone="primary"
              />
              <Action
                to="/community/conflict"
                icon={AlertTriangle}
                label="Human–Wildlife Conflict"
                tone="danger"
              />
              <Action
                to="/community/claim"
                icon={Receipt}
                label="Compensation Claim"
                tone="accent"
              />
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-sm font-bold">My recent reports</h2>
              <a className="text-xs font-semibold text-primary">See all</a>
            </div>
            <div className="space-y-2">
              <ReportRow
                icon={Bird}
                title="Grey Crowned Crane sighting"
                meta="Today · 08:12 · Buliisa"
                tone="success"
                status="Confirmed"
              />
              <ReportRow
                icon={AlertTriangle}
                title="Elephants near maize field"
                meta="Yesterday · Kichwamba"
                tone="warning"
                status="In Review"
              />
              <ReportRow
                icon={Leaf}
                title="Snare trap found"
                meta="2 days ago · Wairingo"
                tone="danger"
                status="Escalated"
              />
            </div>
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card">
            <div className="flex items-center gap-3 mb-3">
              <div className="h-9 w-9 rounded-xl bg-primary/10 grid place-items-center">
                <MapPin className="text-primary" size={16} />
              </div>
              <div className="flex-1">
                <div className="text-sm font-semibold">Nearby alert</div>
                <div className="text-xs text-muted-foreground">
                  Elephant herd movement — 3.2km west
                </div>
              </div>
              <ChevronRight size={16} className="text-muted-foreground" />
            </div>
            <div className="h-24 rounded-xl gradient-sky grid place-items-center text-xs font-semibold text-white/90">
              Live community map
            </div>
          </div>
        </div>

        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}

function Action({
  to,
  icon: Icon,
  label,
  tone,
}: {
  to: any;
  icon: any;
  label: string;
  tone: "primary" | "danger" | "info" | "accent";
}) {
  const map = {
    primary: "from-primary to-primary-glow text-white",
    danger: "from-destructive to-[oklch(0.65_0.21_25)] text-white",
    info: "from-[oklch(0.55_0.12_230)] to-[oklch(0.7_0.1_220)] text-white",
    accent: "from-accent to-[oklch(0.62_0.17_40)] text-foreground",
  } as const;
  return (
    <Link
      to={to}
      className={`relative overflow-hidden rounded-2xl p-3 bg-gradient-to-br ${map[tone]} shadow-card min-h-[120px] flex flex-col justify-between`}
    >
      <Icon size={20} />
      <div className="text-[12px] font-bold leading-tight">{label}</div>
    </Link>
  );
}

function StatusBarSpacer() {
  // Mirrors the StatusBar height used elsewhere so the gradient header keeps its rhythm.
  return <div className="h-9" />;
}

function ReportRow({
  icon: Icon,
  title,
  meta,
  tone,
  status,
}: {
  icon: any;
  title: string;
  meta: string;
  tone: "success" | "warning" | "danger";
  status: string;
}) {
  const c = {
    success: "bg-success/15 text-success",
    warning: "bg-warning/25 text-foreground",
    danger: "bg-destructive/15 text-destructive",
  }[tone];
  return (
    <div className="flex items-center gap-3 bg-card rounded-2xl p-3 shadow-card">
      <div className={`h-10 w-10 rounded-xl grid place-items-center ${c}`}>
        <Icon size={18} />
      </div>
      <div className="flex-1 min-w-0">
        <div className="text-sm font-semibold truncate">{title}</div>
        <div className="text-[11px] text-muted-foreground">{meta}</div>
      </div>
      <Pill tone={tone === "success" ? "success" : tone === "warning" ? "warning" : "danger"}>
        {status}
      </Pill>
    </div>
  );
}
