import { Link, useRouterState } from "@tanstack/react-router";
import {
  Home,
  Newspaper,
  AlertOctagon,
  MapPin,
  Shield,
  Bell,
  ChevronLeft,
  Activity,
} from "lucide-react";
import { ReactNode } from "react";
import { StatusBar } from "./PhoneFrame";

export function ScreenHeader({
  title,
  subtitle,
  back,
  right,
  dark = false,
}: {
  title: string;
  subtitle?: string;
  back?: string;
  right?: ReactNode;
  dark?: boolean;
}) {
  return (
    <div className={dark ? "gradient-forest text-white" : "bg-background text-foreground"}>
      <StatusBar dark={dark} />
      <div className="flex items-center justify-between px-5 pt-2 pb-4">
        <div className="flex items-center gap-3 min-w-0">
          {back && (
            <Link
              to={back}
              className={`grid place-items-center h-9 w-9 rounded-full ${dark ? "bg-white/15" : "bg-secondary"}`}
            >
              <ChevronLeft size={18} />
            </Link>
          )}
          <div className="min-w-0">
            <h1
              className="text-xl font-bold truncate"
              style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
            >
              {title}
            </h1>
            {subtitle && (
              <p className={`text-xs ${dark ? "text-white/70" : "text-muted-foreground"}`}>
                {subtitle}
              </p>
            )}
          </div>
        </div>
        {right}
      </div>
    </div>
  );
}

export function CommunityTabBar() {
  const path = useRouterState({ select: (s) => s.location.pathname });
  const tabs = [
    { to: "/community", icon: Home, label: "Home" },
    { to: "/community/feed", icon: Newspaper, label: "Feed" },
    { to: "/community/sos", icon: AlertOctagon, label: "SOS" },
  ];
  return (
    <div className="sticky bottom-0 bg-card/95 backdrop-blur border-t border-border px-2 pt-2 pb-5">
      <div className="flex items-center justify-around">
        {tabs.map((t) => {
          const active = path === t.to;
          const Icon = t.icon;
          const isSOS = t.label === "SOS";
          return (
            <Link key={t.to} to={t.to} className="flex flex-col items-center gap-1 px-4 py-1.5">
              <div
                className={`grid place-items-center h-11 w-11 rounded-2xl transition ${
                  isSOS
                    ? "bg-destructive text-destructive-foreground shadow-md"
                    : active
                      ? "bg-primary text-primary-foreground"
                      : "text-muted-foreground"
                }`}
              >
                <Icon size={20} />
              </div>
              <span
                className={`text-[10px] font-semibold ${active ? "text-primary" : "text-muted-foreground"}`}
              >
                {t.label}
              </span>
            </Link>
          );
        })}
      </div>
    </div>
  );
}

export function RangerTabBar() {
  const path = useRouterState({ select: (s) => s.location.pathname });
  const tabs = [
    { to: "/ranger", icon: Home, label: "Dashboard" },
    { to: "/ranger/map", icon: MapPin, label: "Map" },
    { to: "/ranger/tracking", icon: Activity, label: "Tracking" },
  ];
  return (
    <div className="sticky bottom-0 bg-card/95 backdrop-blur border-t border-border px-2 pt-2 pb-5">
      <div className="flex items-center justify-around">
        {tabs.map((t) => {
          const active = path === t.to;
          const Icon = t.icon;
          return (
            <Link key={t.to} to={t.to} className="flex flex-col items-center gap-1 px-4 py-1.5">
              <div
                className={`grid place-items-center h-11 w-11 rounded-2xl transition ${active ? "bg-primary text-primary-foreground" : "text-muted-foreground"}`}
              >
                <Icon size={20} />
              </div>
              <span
                className={`text-[10px] font-semibold ${active ? "text-primary" : "text-muted-foreground"}`}
              >
                {t.label}
              </span>
            </Link>
          );
        })}
      </div>
    </div>
  );
}

// UwaTabBar removed — UWA Official is now a separate web portal at /portal.

export function Pill({
  children,
  tone = "default",
}: {
  children: ReactNode;
  tone?: "default" | "success" | "warning" | "danger" | "info";
}) {
  const map = {
    default: "bg-secondary text-secondary-foreground",
    success: "bg-success/15 text-success",
    warning: "bg-warning/20 text-foreground",
    danger: "bg-destructive/15 text-destructive",
    info: "bg-info/15 text-info",
  } as const;
  return (
    <span
      className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold ${map[tone]}`}
    >
      {children}
    </span>
  );
}

export function StatCard({
  icon: Icon,
  label,
  value,
  tone = "primary",
}: {
  icon: any;
  label: string;
  value: string;
  tone?: "primary" | "accent" | "danger" | "success";
}) {
  const bg = {
    primary: "bg-primary/10 text-primary",
    accent: "bg-accent/20 text-foreground",
    danger: "bg-destructive/10 text-destructive",
    success: "bg-success/10 text-success",
  }[tone];
  return (
    <div className="flex-1 bg-card rounded-2xl p-3 shadow-card">
      <div className={`grid place-items-center h-9 w-9 rounded-xl ${bg} mb-2`}>
        <Icon size={18} />
      </div>
      <div className="text-xl font-bold leading-tight">{value}</div>
      <div className="text-[11px] text-muted-foreground">{label}</div>
    </div>
  );
}

export function PageWrap({ children }: { children: ReactNode }) {
  return <div className="min-h-full flex flex-col">{children}</div>;
}

export { Shield, Bell };
