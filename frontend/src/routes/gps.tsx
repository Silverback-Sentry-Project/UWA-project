import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { PhoneFrame, StatusBar } from "@/components/PhoneFrame";
import { MapPin, Navigation, Shield } from "lucide-react";

export const Route = createFileRoute("/gps")({
  component: Gps,
});

function Gps() {
  const nav = useNavigate();
  const dest = "/community" as const;

  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <StatusBar />
        <div className="flex-1 flex flex-col items-center justify-center px-8 text-center">
          <div className="relative mb-8">
            <div className="absolute inset-0 rounded-full bg-primary/15 animate-ping" />
            <div className="absolute inset-3 rounded-full bg-primary/20" />
            <div className="relative h-32 w-32 rounded-full gradient-forest grid place-items-center shadow-float">
              <MapPin size={56} className="text-white" />
            </div>
          </div>
          <h1 className="text-2xl font-extrabold" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
            Enable Location Access
          </h1>
          <p className="text-muted-foreground text-sm mt-3 leading-relaxed">
            Wildwatch uses your GPS to accurately tag wildlife sightings and incident reports — helping rangers respond faster.
          </p>

          <div className="mt-8 w-full space-y-3">
            <Row icon={Navigation} title="Precise reporting" desc="Tag sightings to exact coordinates" />
            <Row icon={Shield} title="Faster response" desc="Rangers see incidents in real time" />
          </div>
        </div>

        <div className="px-6 pb-10 space-y-3">
          <button onClick={() => nav({ to: dest })} className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md">
            Allow While Using App
          </button>
          <Link to={dest} className="block text-center text-muted-foreground text-sm font-medium py-2">
            Not now
          </Link>
        </div>
      </div>
    </PhoneFrame>
  );
}

function Row({ icon: Icon, title, desc }: { icon: any; title: string; desc: string }) {
  return (
    <div className="flex items-start gap-3 bg-card rounded-2xl p-3 shadow-card text-left">
      <div className="h-10 w-10 rounded-xl bg-primary/10 grid place-items-center shrink-0"><Icon size={18} className="text-primary" /></div>
      <div className="min-w-0">
        <div className="text-sm font-semibold">{title}</div>
        <div className="text-xs text-muted-foreground">{desc}</div>
      </div>
    </div>
  );
}