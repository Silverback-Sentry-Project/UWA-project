import { ReactNode, useState } from "react";
import { Link, useRouterState, useNavigate } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import {
  LayoutDashboard, FileWarning, Flame, UserCheck, Receipt,
  ScrollText, ShieldCheck, Settings, Search, Bell, ChevronDown, LogOut,
  ChevronRight, HelpCircle, Command, FileText, Inbox,
} from "lucide-react";
import { useAuth } from "@/lib/auth";
import { apiFetch } from "@/lib/api";
import { Logo } from "./Logo";

type NavItem = { to: string; label: string; icon: any; hint: string };

const UWA_NAV_SECTIONS: { title: string; items: NavItem[] }[] = [
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
      { to: "/portal/claims", label: "Compensation", icon: Receipt, hint: "Approve & pay" },
      { to: "/portal/verified-forms", label: "Verified Forms", icon: FileText, hint: "Forwarded from gameparks" },
      { to: "/portal/conflicts", label: "Conflict Records", icon: ScrollText, hint: "Archive" },
    ],
  },
  {
    title: "Govern",
    items: [
      { to: "/portal/audit", label: "Audit Log", icon: ShieldCheck, hint: "Traceability" },
      { to: "/portal/settings", label: "Personnel", icon: Settings, hint: "Access control" },
    ],
  },
];

const GAMEPARK_NAV_SECTIONS: { title: string; items: NavItem[] }[] = [
  {
    title: "Monitor",
    items: [
      { to: "/portal/dashboard", label: "Dashboard", icon: LayoutDashboard, hint: "Live overview" },
      { to: "/portal/incidents", label: "Incidents", icon: FileWarning, hint: "Reports at this park" },
      { to: "/portal/hotspots", label: "Hotspots", icon: Flame, hint: "Conflict maps" },
    ],
  },
  {
    title: "Act",
    items: [
      { to: "/portal/assignments", label: "Assignments", icon: UserCheck, hint: "Dispatch rangers" },
      { to: "/portal/forms", label: "Forms", icon: FileText, hint: "Create, view & edit evidence forms" },
      { to: "/portal/submissions", label: "Submissions", icon: Inbox, hint: "Verify & forward to UWA" },
      { to: "/portal/conflicts", label: "Conflict Records", icon: ScrollText, hint: "Archive" },
    ],
  },
  {
    title: "Govern",
    items: [
      { to: "/portal/audit", label: "Audit Log", icon: ShieldCheck, hint: "Traceability" },
      { to: "/portal/personnel", label: "Personnel", icon: Settings, hint: "This park's field staff" },
    ],
  },
];

export function PortalShell({ children, title, subtitle, actions, helpText }: { children: ReactNode; title: string; subtitle?: string; actions?: ReactNode; helpText?: string }) {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const { user, logout } = useAuth();
  const isGamepark = user?.account_type === "gamepark";
  const NAV_SECTIONS = isGamepark ? GAMEPARK_NAV_SECTIONS : UWA_NAV_SECTIONS;
  const ALL_NAV: NavItem[] = NAV_SECTIONS.flatMap((s) => s.items);
  const current = ALL_NAV.find((n) => pathname === n.to || (n.to !== "/portal/dashboard" && pathname.startsWith(n.to)));
  const section = NAV_SECTIONS.find((s) => s.items.some((i) => i === current));
  const navigate = useNavigate();
  const initials = user?.full_name
    ? user.full_name.split(" ").map((p) => p[0]).slice(0, 2).join("").toUpperCase()
    : "—";
  const roleLabel = isGamepark ? (user?.park?.park_name ?? "Gamepark") : (user?.roles?.[0] ?? "");
  const [notifOpen, setNotifOpen] = useState(false);
  const queryClient = useQueryClient();
  interface NotificationRow { notification_id: number; title: string; message: string; is_read: boolean; created_at: string }
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
      <aside className="w-64 shrink-0 bg-[var(--p-olive-deep)] text-white flex flex-col h-full">
        <div className="px-5 py-5 border-b border-white/10 flex items-center gap-2.5">
          <div className="h-9 w-9 rounded-lg bg-white overflow-hidden grid place-items-center shrink-0">
            <Logo size={36} />
          </div>
          <div>
            <div className="font-bold portal-display text-[15px] leading-tight">{isGamepark ? "Gamepark Portal" : "UWA Portal"}</div>
            <div className="text-[10px] uppercase tracking-widest text-white/60">{isGamepark ? (user?.park?.park_name ?? "Wildwatch Gamepark") : "Wildwatch Admin"}</div>
          </div>
        </div>
        <nav className="flex-1 px-3 py-4 space-y-4 overflow-auto">
          {NAV_SECTIONS.map((sec) => (
            <div key={sec.title}>
              <div className="px-3 mb-1.5 text-[10px] uppercase tracking-[0.14em] text-white/40 font-semibold">{sec.title}</div>
              <div className="space-y-0.5">
                {sec.items.map((n) => {
                  const active = pathname === n.to || (n.to !== "/portal/dashboard" && pathname.startsWith(n.to));
                  return (
                    <Link key={n.to} to={n.to} title={n.hint}
                      className={`flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition group ${
                        active ? "bg-white/10 text-white border-l-2 border-[var(--p-gold)] pl-[10px]" : "text-white/70 hover:bg-white/5 hover:text-white"
                      }`}>
                      <n.icon size={16} />
                      <span className="flex-1">{n.label}</span>
                      {active && <span className="text-[9px] uppercase tracking-wider text-[var(--p-gold)] font-bold">●</span>}
                    </Link>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
        <div className="border-t border-white/10 p-4">
          <div className="mb-3 flex items-center justify-between text-[10px] text-white/50">
            <span className="flex items-center gap-1"><Command size={10} /> + K to search</span>
          </div>
          <button onClick={handleSignOut} className="flex items-center gap-2 text-white/70 hover:text-white text-[12px]">
            <LogOut size={14} /> Sign out
          </button>
          <div className="mt-3 text-[10px] text-white/50">Secured · TLS 1.3 · Audit-logged</div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0 h-full min-h-0">
        <header className="h-16 shrink-0 bg-white border-b border-[var(--p-olive-line)] flex items-center justify-between px-6">
          <div className="flex items-center gap-3">
            <div className="relative">
              <Search size={15} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--p-ink-soft)]" />
              <input placeholder="Search incidents, claims, rangers…  (⌘K)" className="portal-input pl-8 w-80" />
            </div>
            <select className="portal-input w-44">
              <option>All parks</option>
              <option>Bwindi Impenetrable</option>
              <option>Mgahinga Gorilla</option>
              <option>Murchison Falls</option>
              <option>Queen Elizabeth</option>
              <option>Kibale</option>
            </select>
          </div>
          <div className="flex items-center gap-3">
            <button title="Help & shortcuts" className="h-9 w-9 grid place-items-center rounded-lg border border-[var(--p-olive-line)] hover:bg-[var(--p-olive-soft)]">
              <HelpCircle size={15} />
            </button>
            <div className="relative">
              <button
                onClick={() => setNotifOpen((v) => !v)}
                className="relative h-9 w-9 grid place-items-center rounded-lg border border-[var(--p-olive-line)] hover:bg-[var(--p-olive-soft)]"
              >
                <Bell size={15} />
                {unreadCount > 0 && <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-[var(--p-danger)]" />}
              </button>
              {notifOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setNotifOpen(false)} />
                  <div className="absolute right-0 mt-2 w-80 bg-white border border-[var(--p-olive-line)] rounded-lg shadow-lg z-50 max-h-96 overflow-auto">
                    <div className="px-3 py-2.5 border-b border-[var(--p-olive-line)] text-[12px] font-bold portal-display">Notifications</div>
                    {(notifications ?? []).length === 0 && (
                      <div className="px-3 py-6 text-center text-[12px] text-[var(--p-ink-soft)]">Nothing yet.</div>
                    )}
                    {(notifications ?? []).map((n) => (
                      <button
                        key={n.notification_id}
                        onClick={() => markRead(n.notification_id)}
                        className={`w-full text-left px-3 py-2.5 border-b border-[var(--p-olive-line)] last:border-b-0 hover:bg-[var(--p-olive-soft)] transition ${n.is_read ? "" : "bg-[var(--p-olive-soft)]/50"}`}
                      >
                        <div className="flex items-center gap-1.5">
                          {!n.is_read && <span className="h-1.5 w-1.5 rounded-full bg-[var(--p-gold)] shrink-0" />}
                          <span className="text-[12px] font-semibold">{n.title}</span>
                        </div>
                        <div className="text-[11px] text-[var(--p-ink-soft)] mt-0.5">{n.message}</div>
                        <div className="text-[10px] text-[var(--p-ink-soft)] mt-1">{new Date(n.created_at).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" })}</div>
                      </button>
                    ))}
                  </div>
                </>
              )}
            </div>
            <div className="flex items-center gap-2 pl-3 border-l border-[var(--p-olive-line)]">
              <div className="h-8 w-8 rounded-full bg-[var(--p-olive)] text-white grid place-items-center text-xs font-bold">{initials}</div>
              <div className="text-right">
                <div className="text-[12px] font-semibold leading-tight">{user?.full_name ?? "—"}</div>
                <div className="text-[10px] text-[var(--p-ink-soft)] uppercase tracking-wider">{roleLabel}</div>
              </div>
              <ChevronDown size={14} className="text-[var(--p-ink-soft)]" />
            </div>
          </div>
        </header>

        <div className="px-6 pt-5 pb-4 border-b border-[var(--p-olive-line)] bg-white/50 shrink-0">
          {/* Breadcrumb */}
          <nav className="flex items-center gap-1.5 text-[11px] text-[var(--p-ink-soft)] mb-2" aria-label="Breadcrumb">
            <Link to="/portal/dashboard" className="hover:text-[var(--p-olive-deep)]">{isGamepark ? "Gamepark Portal" : "UWA Portal"}</Link>
            {section && <><ChevronRight size={11} /><span>{section.title}</span></>}
            {current && <><ChevronRight size={11} /><span className="text-[var(--p-ink)] font-semibold">{current.label}</span></>}
          </nav>
          <div className="flex items-end justify-between gap-4">
            <div className="min-w-0">
              <h1 className="text-[22px] font-bold portal-display text-[var(--p-ink)] leading-tight">{title}</h1>
              {subtitle && <p className="text-[13px] text-[var(--p-ink-soft)] mt-1">{subtitle}</p>}
              {helpText && (
                <div className="mt-2 inline-flex items-start gap-1.5 text-[11px] text-[var(--p-olive-deep)] bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)] rounded-md px-2.5 py-1.5">
                  <HelpCircle size={12} className="mt-px shrink-0" /> <span>{helpText}</span>
                </div>
              )}
            </div>
            <div className="flex items-center gap-2 shrink-0">{actions}</div>
          </div>
        </div>

        <main className="flex-1 min-h-0 p-6 overflow-auto">{children}</main>
      </div>
    </div>
  );
}

export function StatCard({ label, value, delta, tone = "olive", icon: Icon, to, hint }: { label: string; value: string | number; delta?: string; tone?: "olive" | "gold" | "danger" | "info"; icon?: any; to?: string; hint?: string }) {
  const tones: Record<string, string> = {
    olive: "var(--p-olive)",
    gold: "var(--p-gold-deep)",
    danger: "var(--p-danger)",
    info: "var(--p-info)",
  };
  const inner = (
    <>
      <div className="flex items-center justify-between">
        <span className="text-[11px] uppercase tracking-wider font-semibold text-[var(--p-ink-soft)]">{label}</span>
        {Icon && <div className="h-7 w-7 rounded-md grid place-items-center" style={{ background: "var(--p-olive-soft)", color: tones[tone] }}><Icon size={14} /></div>}
      </div>
      <div className="mt-2 flex items-baseline gap-2">
        <span className="text-2xl font-bold portal-display" style={{ color: tones[tone] }}>{value}</span>
        {delta && <span className="text-[11px] font-semibold text-[var(--p-ink-soft)]">{delta}</span>}
      </div>
      {hint && <div className="mt-2 text-[11px] text-[var(--p-ink-soft)]">{hint}</div>}
      {to && <div className="mt-2 text-[11px] font-semibold text-[var(--p-olive-deep)] flex items-center gap-1">View details <ChevronRight size={11} /></div>}
    </>
  );
  if (to) {
    return <Link to={to} className="portal-card p-4 block hover:border-[var(--p-olive)] hover:shadow-md transition">{inner}</Link>;
  }
  return <div className="portal-card p-4">{inner}</div>;
}

export function StatusBadge({ status }: { status: string }) {
  const map: Record<string, { bg: string; fg: string }> = {
    active: { bg: "oklch(0.95 0.05 60)", fg: "oklch(0.5 0.15 50)" },
    pending: { bg: "oklch(0.95 0.03 90)", fg: "oklch(0.5 0.1 90)" },
    resolved: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)" },
    New: { bg: "oklch(0.95 0.02 230)", fg: "oklch(0.42 0.1 230)" },
    Assigned: { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    "In Progress": { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    Resolved: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)" },
    Escalated: { bg: "oklch(0.94 0.05 27)", fg: "oklch(0.5 0.18 27)" },
    Pending: { bg: "oklch(0.95 0.03 90)", fg: "oklch(0.5 0.1 90)" },
    Responding: { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    Approved: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)" },
    Rejected: { bg: "oklch(0.95 0.04 30)", fg: "oklch(0.5 0.15 30)" },
    Paid: { bg: "oklch(0.92 0.06 150)", fg: "oklch(0.32 0.07 150)" },
    Submitted: { bg: "oklch(0.95 0.02 230)", fg: "oklch(0.42 0.1 230)" },
    "Under Verification": { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    "Under Review": { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    Available: { bg: "oklch(0.95 0.04 150)", fg: "oklch(0.4 0.1 150)" },
    "On patrol": { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    "Off-duty": { bg: "oklch(0.94 0.005 120)", fg: "oklch(0.45 0.01 120)" },
    Low: { bg: "oklch(0.95 0.02 150)", fg: "oklch(0.4 0.08 150)" },
    Medium: { bg: "oklch(0.96 0.03 85)", fg: "oklch(0.45 0.13 85)" },
    High: { bg: "oklch(0.95 0.05 60)", fg: "oklch(0.5 0.15 50)" },
    Critical: { bg: "oklch(0.94 0.05 27)", fg: "oklch(0.5 0.18 27)" },
  };
  const c = map[status] ?? { bg: "var(--p-olive-soft)", fg: "var(--p-olive-deep)" };
  return (
    <span className="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full capitalize"
      style={{ background: c.bg, color: c.fg }}>
      <span className="h-1.5 w-1.5 rounded-full" style={{ background: c.fg }} />
      {status}
    </span>
  );
}