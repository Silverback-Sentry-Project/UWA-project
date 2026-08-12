import { ReactNode, useState } from "react";
import { Link, useRouterState, useNavigate } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import {
  LayoutDashboard,
  FileWarning,
  Flame,
  UserCheck,
  ScrollText,
  Settings,
  Bell,
  ChevronDown,
  LogOut,
  ChevronRight,
  HelpCircle,
  CheckCircle2,
  Clock,
  AlertTriangle,
  Play,
  Zap,
} from "lucide-react";
import { useAuth } from "@/lib/auth";
import { usePark } from "@/lib/park-context";
import { apiFetch } from "@/lib/api";
import { Logo } from "./Logo";

type NavItem = { to: string; label: string; icon: any; hint: string };

const NAV_SECTIONS: { title: string; items: NavItem[] }[] = [
  {
    title: "Monitor",
    items: [
      { to: "/portal/dashboard", label: "Dashboard", icon: LayoutDashboard, hint: "Live overview" },
      { to: "/portal/incidents", label: "Incidents", icon: FileWarning, hint: "All reports" },
      { to: "/portal/hotspots", label: "Hotspots", icon: Flame, hint: "Conflict maps" },
    ],
  },
  {
    title: "Act",
    items: [
      {
        to: "/portal/assignments",
        label: "Assignments",
        icon: UserCheck,
        hint: "Dispatch rangers",
      },
      {
        to: "/portal/conflicts",
        label: "Conflict Alerts",
        icon: ScrollText,
        hint: "Emergency response",
      },
    ],
  },
];

export function PortalShell({
  children,
  title,
  subtitle,
  actions,
  helpText,
}: {
  children: ReactNode;
  title: string;
  subtitle?: string;
  actions?: ReactNode;
  helpText?: string;
}) {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const { user, logout } = useAuth();
  const { selectedParkId, setSelectedParkId } = usePark();
  const ALL_NAV: NavItem[] = NAV_SECTIONS.flatMap((s) => s.items);
  const current = ALL_NAV.find(
    (n) => pathname === n.to || (n.to !== "/portal/dashboard" && pathname.startsWith(n.to)),
  );
  const section = NAV_SECTIONS.find((s) => s.items.some((i) => i === current));
  const navigate = useNavigate();
  const initials = user?.full_name
    ? user.full_name
        .split(" ")
        .map((p) => p[0])
        .slice(0, 2)
        .join("")
        .toUpperCase()
    : "—";
  const roleLabel = user?.roles?.[0] ?? "WildWatch Official";
  const [notifOpen, setNotifOpen] = useState(false);
  const queryClient = useQueryClient();

  interface Park {
    park_id: string;
    park_name: string;
  }
  const { data: parks } = useQuery({
    queryKey: ["parks"],
    queryFn: () => apiFetch<Park[]>("/public/parks"),
  });

  interface NotificationRow {
    notification_id: number;
    title: string;
    message: string;
    is_read: boolean;
    created_at: string;
  }
  const { data: notifications } = useQuery({
    queryKey: ["notifications"],
    queryFn: () => apiFetch<NotificationRow[]>("/notifications"),
    refetchInterval: 30000,
  });
  const unreadCount = (notifications ?? []).filter((n) => !n.is_read).length;

  async function markRead(id: number) {
    await apiFetch(`/notifications/${id}/read`, { method: "PATCH" });
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
  }

  async function handleSignOut() {
    await logout();
    navigate({ to: "/portal" });
  }

  return (
    <div className="portal h-screen w-full flex overflow-hidden">
      {/* Sidebar */}
      <aside className="w-64 shrink-0 bg-[#1A2F1A] text-white flex flex-col h-full border-r border-white/5">
        <div className="px-6 py-6 border-b border-white/5 flex items-center gap-3">
          <div className="h-10 w-10 rounded-xl bg-white/10 backdrop-blur-md grid place-items-center shrink-0 border border-white/10 shadow-lg">
            <Logo size={26} />
          </div>
          <div>
            <div className="font-bold portal-display text-[16px] leading-tight tracking-tight">
              WildWatch
            </div>
            <div className="text-[10px] uppercase tracking-[0.1em] text-white/50 font-semibold">
              HQ Control
            </div>
          </div>
        </div>
        <nav className="flex-1 px-3 py-6 space-y-6 overflow-auto custom-scrollbar">
          {NAV_SECTIONS.map((sec) => (
            <div key={sec.title}>
              <div className="px-3 mb-2 text-[10px] uppercase tracking-[0.2em] text-white/30 font-bold">
                {sec.title}
              </div>
              <div className="space-y-1">
                {sec.items.map((n) => {
                  const active =
                    pathname === n.to ||
                    (n.to !== "/portal/dashboard" && pathname.startsWith(n.to));
                  return (
                    <Link
                      key={n.to}
                      to={n.to}
                      title={n.hint}
                      className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all duration-200 group ${
                        active
                          ? "bg-white/10 text-white shadow-inner"
                          : "text-white/60 hover:bg-white/5 hover:text-white"
                      }`}
                    >
                      <n.icon
                        size={18}
                        className={`transition-colors ${active ? "text-[#C4A456]" : "text-white/40 group-hover:text-white/60"}`}
                      />
                      <span className="flex-1">{n.label}</span>
                      {active && (
                        <div className="h-1.5 w-1.5 rounded-full bg-[#C4A456] shadow-[0_0_8px_rgba(196,164,86,0.6)]" />
                      )}
                    </Link>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
        <div className="border-t border-white/5 p-4 space-y-2 bg-black/10">
          <Link
            to="/portal/settings"
            className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-white/50 hover:bg-white/5 hover:text-white transition-all"
          >
            <Settings size={18} className="text-white/30" />
            <span>Settings</span>
          </Link>

          <button
            onClick={handleSignOut}
            className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-[#FF8A80] hover:bg-white/5 transition-all"
          >
            <LogOut size={18} className="opacity-70" />
            <span>Sign out</span>
          </button>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0 h-full min-h-0 bg-[#F8F9FA]">
        <header className="h-16 shrink-0 bg-white border-b border-neutral-200 flex items-center justify-between px-8 shadow-sm z-10">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2 px-3 py-1.5 bg-neutral-100 rounded-lg border border-neutral-200">
              <span className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">
                Scoping
              </span>
              <select
                className="bg-transparent border-none text-[13px] font-bold text-neutral-700 focus:ring-0 cursor-pointer pr-8"
                value={selectedParkId || ""}
                onChange={(e) => setSelectedParkId(e.target.value || null)}
              >
                <option value="">All Regions</option>
                {(parks ?? []).map((p) => (
                  <option key={p.park_id} value={p.park_id}>
                    {p.park_name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="flex items-center gap-4">
            <div className="relative">
              <button
                onClick={() => setNotifOpen((v) => !v)}
                className="relative h-10 w-10 grid place-items-center rounded-xl border border-neutral-200 hover:bg-neutral-50 transition-colors"
              >
                <Bell size={20} className="text-neutral-500" />
                {unreadCount > 0 && (
                  <span className="absolute top-2 right-2.5 h-2 w-2 rounded-full bg-[#D32F2F] border-2 border-white shadow-sm" />
                )}
              </button>
              {notifOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setNotifOpen(false)} />
                  <div className="absolute right-0 mt-3 w-80 bg-white border border-neutral-200 rounded-2xl shadow-2xl z-50 max-h-[480px] overflow-hidden flex flex-col text-[#1A1A1A] animate-in fade-in zoom-in-95 duration-200">
                    <div className="px-4 py-3 border-b border-neutral-100 flex items-center justify-between bg-neutral-50/50">
                      <span className="text-[13px] font-bold portal-display">Notifications</span>
                      {unreadCount > 0 && (
                        <span className="text-[10px] font-bold bg-[#D32F2F] text-white px-2 py-0.5 rounded-full">
                          {unreadCount}
                        </span>
                      )}
                    </div>
                    <div className="flex-1 overflow-auto custom-scrollbar">
                      {(notifications ?? []).length === 0 && (
                        <div className="px-4 py-12 text-center text-[12px] text-neutral-400 font-medium">
                          All caught up.
                        </div>
                      )}
                      {(notifications ?? []).map((n) => (
                        <button
                          key={n.notification_id}
                          onClick={() => {
                            markRead(n.notification_id);
                            setNotifOpen(false);
                          }}
                          className={`w-full text-left px-4 py-3.5 border-b border-neutral-50 last:border-b-0 hover:bg-neutral-50 transition ${n.is_read ? "" : "bg-blue-50/30"}`}
                        >
                          <div className="flex items-start gap-3">
                            {!n.is_read && (
                              <div className="mt-1.5 h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0" />
                            )}
                            <div className="flex-1 min-w-0">
                              <div className="text-[12px] font-bold text-neutral-800 leading-tight">
                                {n.title}
                              </div>
                              <div className="text-[11px] text-neutral-500 mt-1 line-clamp-2 leading-relaxed">
                                {n.message}
                              </div>
                              <div className="text-[10px] text-neutral-400 mt-2 font-bold uppercase tracking-wider">
                                {new Date(n.created_at).toLocaleTimeString([], {
                                  hour: "2-digit",
                                  minute: "2-digit",
                                })}
                              </div>
                            </div>
                          </div>
                        </button>
                      ))}
                    </div>
                  </div>
                </>
              )}
            </div>

            <div className="flex items-center gap-3 pl-4 border-l border-neutral-100">
              <div className="h-10 w-10 rounded-full bg-gradient-to-br from-[#1A2F1A] to-[#2E4D2E] text-white flex items-center justify-center text-[13px] font-bold shadow-md border-2 border-white">
                {initials}
              </div>
            </div>
          </div>
        </header>

        <div className="px-8 pt-8 pb-6 shrink-0">
          <div className="flex items-center gap-2 text-[11px] text-neutral-400 font-bold uppercase tracking-[0.1em] mb-2">
            <Link to="/portal/dashboard" className="hover:text-[#1A2F1A] transition-colors">
              HQ
            </Link>
            {section && (
              <>
                <ChevronRight size={10} className="text-neutral-300" />
                <span>{section.title}</span>
              </>
            )}
          </div>

          <div className="flex items-center justify-between gap-4">
            <div className="min-w-0">
              <h1 className="text-[28px] font-black portal-display text-neutral-900 tracking-tight leading-tight">
                {title}
              </h1>
              {subtitle && (
                <p className="text-[14px] text-neutral-500 font-medium mt-1">{subtitle}</p>
              )}
            </div>
            <div className="flex items-center gap-3 shrink-0">{actions}</div>
          </div>
        </div>

        <main className="flex-1 min-h-0 px-8 pb-8 overflow-auto custom-scrollbar">
          {children}
          {helpText && (
            <div className="mt-8 flex items-start gap-3 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
              <HelpCircle size={18} className="text-blue-500 shrink-0" />
              <p className="text-[12px] text-blue-700 font-medium leading-relaxed">{helpText}</p>
            </div>
          )}
        </main>
      </div>
    </div>
  );
}

export function StatCard({
  label,
  value,
  delta,
  tone = "olive",
  icon: Icon,
  to,
  hint,
}: {
  label: string;
  value: string | number;
  delta?: string;
  tone?: "olive" | "gold" | "danger" | "info";
  icon?: any;
  to?: string;
  hint?: string;
}) {
  const tones: Record<string, { main: string; bg: string }> = {
    olive: { main: "#1A2F1A", bg: "rgba(26, 47, 26, 0.05)" },
    gold: { main: "#C4A456", bg: "rgba(196, 164, 86, 0.05)" },
    danger: { main: "#D32F2F", bg: "rgba(211, 47, 47, 0.05)" },
    info: { main: "#1976D2", bg: "rgba(25, 118, 210, 0.05)" },
  };
  const t = tones[tone];
  const inner = (
    <div className="flex flex-col h-full justify-between">
      <div className="flex items-center justify-between">
        <span className="text-[11px] font-black uppercase tracking-[0.15em] text-neutral-400">
          {label}
        </span>
        {Icon && (
          <div
            className="h-10 w-10 rounded-2xl flex items-center justify-center transition-all duration-300 group-hover:scale-110"
            style={{ background: t.bg, color: t.main }}
          >
            <Icon size={18} />
          </div>
        )}
      </div>
      <div className="mt-4">
        <div className="flex items-baseline gap-2">
          <span className="text-4xl font-black portal-display tracking-tight text-neutral-900">
            {value}
          </span>
          {delta && (
            <span className="text-[12px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">
              {delta}
            </span>
          )}
        </div>
        {hint && (
          <div className="mt-2 text-[12px] text-neutral-400 font-medium leading-tight">{hint}</div>
        )}
      </div>
      {to && (
        <div className="mt-6 flex items-center gap-2 text-[11px] font-bold text-neutral-400 group-hover:text-neutral-900 transition-colors uppercase tracking-widest">
          Detailed Analysis <ChevronRight size={12} />
        </div>
      )}
    </div>
  );
  if (to) {
    return (
      <Link
        to={to}
        className="portal-card p-6 block hover:shadow-xl hover:border-neutral-300 transition-all duration-300 group bg-white"
      >
        {inner}
      </Link>
    );
  }
  return <div className="portal-card p-6 bg-white shadow-sm border-neutral-100">{inner}</div>;
}

export function StatusBadge({ status }: { status: string }) {
  const map: Record<string, { bg: string; fg: string; icon: any }> = {
    active: { bg: "oklch(0.95 0.05 60)", fg: "oklch(0.5 0.15 50)", icon: Zap },
    pending: { bg: "oklch(0.95 0.03 90)", fg: "oklch(0.5 0.1 90)", icon: Clock },
    resolved: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)", icon: CheckCircle2 },
    New: { bg: "oklch(0.95 0.02 230)", fg: "oklch(0.42 0.1 230)", icon: Play },
    Assigned: { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)", icon: UserCheck },
    "In Progress": { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)", icon: Zap },
    Resolved: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)", icon: CheckCircle2 },
    Pending: { bg: "oklch(0.95 0.03 90)", fg: "oklch(0.5 0.1 90)", icon: Clock },
    Responding: { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)", icon: Zap },
    Available: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)", icon: CheckCircle2 },
    "On patrol": { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)", icon: Zap },
    "Off-duty": { bg: "oklch(0.94 0.005 120)", fg: "oklch(0.45 0.01 120)", icon: Clock },
    Low: { bg: "oklch(0.95 0.02 150)", fg: "oklch(0.4 0.08 150)", icon: CheckCircle2 },
    Medium: { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)", icon: Clock },
    High: { bg: "oklch(0.95 0.05 60)", fg: "oklch(0.5 0.15 50)", icon: AlertTriangle },
    Critical: { bg: "oklch(0.94 0.05 27)", fg: "oklch(0.5 0.18 27)", icon: Flame },
  };
  const c = map[status] ?? { bg: "var(--p-olive-soft)", fg: "var(--p-olive-deep)", icon: Zap };
  return (
    <span
      className="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-lg capitalize border border-black/5 shadow-sm transition-all"
      style={{ background: c.bg, color: c.fg }}
    >
      <c.icon size={11} />
      {status}
    </span>
  );
}

// Escalation is an orthogonal flag, not a status value, so it renders as its
// own badge alongside StatusBadge rather than as one of its color variants.
export function EscalatedBadge() {
  return (
    <span
      className="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-lg capitalize border border-black/5 shadow-sm transition-all"
      style={{ background: "oklch(0.94 0.05 27)", color: "oklch(0.5 0.18 27)" }}
    >
      <AlertTriangle size={11} />
      Escalated
    </span>
  );
}
