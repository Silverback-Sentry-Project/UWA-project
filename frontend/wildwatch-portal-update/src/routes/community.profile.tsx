import { createFileRoute, Link } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { Bell, Lock, Globe, Shield, HelpCircle, LogOut, ChevronRight, MapPin, Award } from "lucide-react";
import { useUserPrefs, initials, setPrefs } from "@/lib/user-prefs";

const LANGUAGES = ["English", "Luganda", "Runyankole", "Rukiga", "Lugbara", "Swahili"];

export const Route = createFileRoute("/community/profile")({ component: Profile });

function Profile() {
  const prefs = useUserPrefs();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Profile & Settings" back="/community" />
        <div className="px-5 pb-6 space-y-4">
          <div className="bg-card rounded-2xl p-5 shadow-card text-center">
            <div className="h-20 w-20 rounded-full mx-auto gradient-forest grid place-items-center text-white text-2xl font-bold">{initials(prefs.fullName)}</div>
            <div className="mt-3 text-lg font-bold">{prefs.fullName}</div>
            <div className="text-xs text-muted-foreground flex items-center justify-center gap-1"><MapPin size={12} />{prefs.park}</div>
            <div className="flex justify-center gap-2 mt-3">
              <Pill tone="success">Verified</Pill>
              <Pill tone="info">Community member</Pill>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-2">
            <Stat label="Reports" value="24" />
            <Stat label="Confirmed" value="19" />
            <Stat label="Impact" value="A+" />
          </div>

          <Section title="Account">
            <Row icon={Bell} label="Notifications" right={<Pill tone="success">On</Pill>} />
            <Row icon={Lock} label="Password & security" />
            <Row icon={Globe} label="Language" right={
              <div className="flex items-center gap-1">
                <select
                  value={prefs.language}
                  onChange={(e) => setPrefs({ language: e.target.value })}
                  className="bg-transparent text-xs text-muted-foreground font-semibold outline-none appearance-none text-right">
                  {LANGUAGES.map((l) => <option key={l}>{l}</option>)}
                </select>
                <ChevronRight size={14} className="text-muted-foreground" />
              </div>
            } />
          </Section>

          <Section title="Conservation">
            <Row icon={Award} label="My badges" right={<span className="text-xs text-muted-foreground">5</span>} />
            <Row icon={Shield} label="Privacy controls" />
          </Section>

          <Section title="Support">
            <Row icon={HelpCircle} label="Help center" />
            <Link to="/" className="flex items-center gap-3 p-3 rounded-xl text-destructive">
              <div className="h-9 w-9 rounded-xl bg-destructive/10 grid place-items-center"><LogOut size={16} /></div>
              <span className="text-sm font-semibold flex-1">Sign out</span>
            </Link>
          </Section>
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return <div className="bg-card rounded-2xl p-3 shadow-card text-center"><div className="text-xl font-bold">{value}</div><div className="text-[11px] text-muted-foreground">{label}</div></div>;
}
function Section({ title, children }: { title: string; children: any }) {
  return <div><h2 className="text-xs font-bold text-muted-foreground uppercase tracking-wide mb-2 px-1">{title}</h2><div className="bg-card rounded-2xl shadow-card p-1">{children}</div></div>;
}
function Row({ icon: Icon, label, right }: { icon: any; label: string; right?: any }) {
  return (
    <div className="flex items-center gap-3 p-3 rounded-xl">
      <div className="h-9 w-9 rounded-xl bg-secondary grid place-items-center"><Icon size={16} className="text-foreground" /></div>
      <span className="text-sm font-semibold flex-1">{label}</span>
      {right ?? <ChevronRight size={16} className="text-muted-foreground" />}
    </div>
  );
}