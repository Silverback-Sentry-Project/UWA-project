import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader } from "@/components/ui-prototype";
import { Camera, ChevronDown, Image as ImageIcon, AlertTriangle } from "lucide-react";

export const Route = createFileRoute("/community/conflict")({ component: Conflict });

function Conflict() {
  const nav = useNavigate();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader
          title="Conflict Report"
          subtitle="Human–wildlife incident"
          back="/community"
        />
        <div className="px-5 space-y-4 pb-6">
          <div className="bg-destructive/10 border border-destructive/20 rounded-2xl p-3 flex gap-3">
            <AlertTriangle className="text-destructive shrink-0" size={20} />
            <div className="text-xs text-foreground/80">
              If anyone is in immediate danger, use the SOS tab. Stay safe and keep distance from
              the animal.
            </div>
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card space-y-3">
            <Label>Species involved</Label>
            <Select value="Elephant" />
            <Label>Type of conflict</Label>
            <Select value="Crop raiding" />
            <Label>Date & time</Label>
            <Input placeholder="Today · 18:45" />
            <Label>Affected area</Label>
            <Input placeholder="Maize field, 0.5 acre" />
            <Label>Description</Label>
            <textarea
              rows={4}
              placeholder="Describe what happened…"
              className="w-full bg-secondary rounded-xl px-3 py-2.5 text-sm outline-none"
            />
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card">
            <Label>Photo evidence</Label>
            <div className="grid grid-cols-3 gap-2 mt-2">
              <Thumb />
              <Thumb />
              <button className="aspect-square rounded-xl border-2 border-dashed border-border grid place-items-center text-muted-foreground">
                <Camera size={20} />
              </button>
            </div>
          </div>

          <button
            onClick={() => nav({ to: "/community/claim-prompt" })}
            className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md"
          >
            Submit report
          </button>
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}

function Label({ children }: { children: any }) {
  return (
    <div className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide">
      {children}
    </div>
  );
}
function Input({ placeholder }: { placeholder: string }) {
  return (
    <input
      placeholder={placeholder}
      className="w-full bg-secondary rounded-xl px-3 py-2.5 text-sm outline-none"
    />
  );
}
function Select({ value }: { value: string }) {
  return (
    <button className="w-full flex items-center justify-between bg-secondary rounded-xl px-3 py-2.5 text-sm">
      <span>{value}</span>
      <ChevronDown size={16} className="text-muted-foreground" />
    </button>
  );
}
function Thumb() {
  return (
    <div className="aspect-square rounded-xl gradient-sunset grid place-items-center">
      <ImageIcon size={20} className="text-white/70" />
    </div>
  );
}
