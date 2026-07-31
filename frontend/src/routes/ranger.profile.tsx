import { createFileRoute, Link } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { RangerTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { Bell, Lock, Globe, Shield, HelpCircle, LogOut, ChevronRight, MapPin, Radio, Clock } from "lucide-react";
import { useUserPrefs, setPrefs } from "@/lib/user-prefs";

const LANGUAGES = ["English", "Luganda", "Runyankole", "Rukiga", "Lugbara", "Swahili"];

export const Route = createFileRoute("/ranger/profile")({ component: RangerProfile });

function RangerProfile() {
  const prefs = useUserPrefs();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Profile & Settings" back="/ranger" />
        <div className="px-5 pb-6 space-y-4">
          <div className="bg-card rounded-2xl p-5 shadow-card text-center">
            <div className="h-20 w-20 rounded-full mx-auto gradient-forest grid place-items-center text-white text-2xl font-bold">J</div>
            <div className="mt-3 text-lg font-bold">Ranger Joseph Okello</div>
            <div className="text-xs text-muted-foreground flex items-center justify-center gap-1"><MapPin size={12} />Murchison Falls · Sector 3</div>
            <div className="flex justify-center gap-2 mt-3">
              <Pill tone="success">On duty</Pill>
              <Pill tone="info">Field Ranger</Pill>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-2">
            <Stat label="Patrols" value="48" />
            <Stat label="Resolved" value="124" />
            <Stat label="Hours" value="312" />
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

          <Section title="Field">
            <Row icon={MapPin} label="Assigned zones" right={<span className="text-xs text-muted-foreground">Sector 3, 4</span>} />
            <Row icon={Clock} label="Shift preferences" right={<span className="text-xs text-muted-foreground">06:00 – 18:00</span>} />
            <Row icon={Radio} label="Coordination radio" right={<Pill tone="info">Ch. 7</Pill>} />
          </Section>

          <Section title="Support">
            <Row icon={Shield} label="Privacy controls" />
            <Row icon={HelpCircle} label="Help center" />
            <Link to="/" className="flex items-center gap-3 p-3 rounded-xl text-destructive">
              <div className="h-9 w-9 rounded-xl bg-destructive/10 grid place-items-center"><LogOut size={16} /></div>
              <span className="text-sm font-semibold flex-1">Sign out</span>
            </Link>
          </Section>
        </div>
        <RangerTabBar />
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