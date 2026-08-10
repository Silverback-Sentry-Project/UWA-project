import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { Camera, Mic, Square, Play, ChevronDown, Image as ImageIcon } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/community/sighting")({ component: Sighting });

function Sighting() {
  const [recording, setRecording] = useState(false);
  const nav = useNavigate();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Wildlife Sighting" subtitle="Report what you saw" back="/community" />
        <div className="px-5 space-y-4 pb-6">
          <div className="bg-card rounded-2xl p-4 shadow-card space-y-3">
            <Label>Species</Label>
            <Select value="African Elephant" />
            <Label>Number observed</Label>
            <Input placeholder="e.g. 5" />
            <Label>Behavior</Label>
            <Select value="Foraging" />
            <Label>Date & time</Label>
            <Input placeholder="Today · 14:32" />
            <Label>Description</Label>
            <textarea
              rows={3}
              placeholder="What did you observe?"
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

          <div className="bg-card rounded-2xl p-4 shadow-card">
            <div className="flex items-center justify-between mb-3">
              <Label>Voice note</Label>
              <Pill tone={recording ? "danger" : "default"}>
                {recording ? "● Recording" : "Optional"}
              </Pill>
            </div>
            <div className="flex items-center gap-3 bg-secondary rounded-xl p-3">
              <button
                onClick={() => setRecording((r) => !r)}
                className={`h-12 w-12 rounded-full grid place-items-center shadow-md ${recording ? "bg-destructive text-destructive-foreground" : "bg-primary text-primary-foreground"}`}
              >
                {recording ? <Square size={18} /> : <Mic size={18} />}
              </button>
              <div className="flex-1">
                <div className="flex items-end gap-0.5 h-8">
                  {Array.from({ length: 28 }).map((_, i) => (
                    <div
                      key={i}
                      className={`flex-1 rounded-full ${recording ? "bg-destructive" : "bg-primary/40"}`}
                      style={{
                        height: `${20 + Math.abs(Math.sin(i + (recording ? Date.now() / 200 : 0))) * 80}%`,
                      }}
                    />
                  ))}
                </div>
                <div className="text-[11px] text-muted-foreground mt-1">
                  {recording ? "00:12" : "Tap to record up to 60s"}
                </div>
              </div>
              <button className="h-9 w-9 rounded-full bg-card grid place-items-center">
                <Play size={14} />
              </button>
            </div>
          </div>

          <button
            onClick={() => nav({ to: "/community/claim-prompt" })}
            className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md"
          >
            Submit sighting
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
    <div className="aspect-square rounded-xl gradient-forest grid place-items-center">
      <ImageIcon size={20} className="text-white/70" />
    </div>
  );
}
