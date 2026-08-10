import { createFileRoute, Link } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { RangerTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import {
  Navigation,
  Camera,
  AlertOctagon,
  Phone,
  MessageCircle,
  User,
  Clock,
  MapPin,
} from "lucide-react";

export const Route = createFileRoute("/ranger/incident")({ component: Incident });

function Incident() {
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Incident INC-2049" subtitle="Urgent · Kichwamba" back="/ranger" />
        <div className="px-5 pb-6 space-y-4">
          <div className="bg-card rounded-2xl p-4 shadow-card">
            <div className="flex items-center gap-3">
              <div className="h-12 w-12 rounded-xl bg-destructive/15 text-destructive grid place-items-center">
                <AlertOctagon size={22} />
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <Pill tone="danger">Urgent</Pill>
                  <Pill tone="warning">Crop raid</Pill>
                </div>
                <div className="text-sm font-bold mt-1">Elephant herd near maize fields</div>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3 mt-4 text-xs">
              <Meta icon={MapPin} label="Location" value="Kichwamba · 0.8 km" />
              <Meta icon={Clock} label="Reported" value="2 min ago" />
              <Meta icon={User} label="By" value="Amara Nakato" />
              <Meta icon={AlertOctagon} label="Species" value="Elephant ×12" />
            </div>
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card">
            <h3 className="text-sm font-bold mb-2">Description</h3>
            <p className="text-xs text-muted-foreground leading-relaxed">
              Herd moving through maize plot toward the river. No injuries reported. Community
              keeping distance. Voice note attached.
            </p>
            <div className="mt-3 flex items-center gap-2 bg-secondary rounded-xl p-2.5">
              <button className="h-8 w-8 rounded-full bg-primary text-primary-foreground grid place-items-center text-[10px]">
                ▶
              </button>
              <div className="flex-1 h-6 flex items-end gap-0.5">
                {Array.from({ length: 36 }).map((_, i) => (
                  <div
                    key={i}
                    className="flex-1 bg-primary/40 rounded-full"
                    style={{ height: `${20 + Math.abs(Math.sin(i)) * 70}%` }}
                  />
                ))}
              </div>
              <span className="text-[10px] text-muted-foreground">00:24</span>
            </div>
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card">
            <h3 className="text-sm font-bold mb-3">Evidence</h3>
            <div className="grid grid-cols-3 gap-2">
              <div className="aspect-square rounded-xl gradient-forest grid place-items-center">
                <Camera size={20} className="text-white/70" />
              </div>
              <div className="aspect-square rounded-xl gradient-sunset grid place-items-center">
                <Camera size={20} className="text-white/70" />
              </div>
              <div className="aspect-square rounded-xl gradient-sky grid place-items-center">
                <Camera size={20} className="text-white/70" />
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <button className="bg-card rounded-2xl p-3 shadow-card flex items-center gap-2 justify-center text-sm font-semibold">
              <Phone size={16} className="text-primary" />
              Call reporter
            </button>
            <button className="bg-card rounded-2xl p-3 shadow-card flex items-center gap-2 justify-center text-sm font-semibold">
              <MessageCircle size={16} className="text-primary" />
              Message
            </button>
          </div>

          <Link
            to="/ranger/map"
            className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md flex items-center justify-center gap-2"
          >
            <Navigation size={18} />
            Start GPS
          </Link>
        </div>
        <RangerTabBar />
      </div>
    </PhoneFrame>
  );
}

function Meta({ icon: Icon, label, value }: { icon: any; label: string; value: string }) {
  return (
    <div className="bg-secondary rounded-xl p-2.5">
      <div className="flex items-center gap-1 text-[10px] text-muted-foreground uppercase tracking-wide font-semibold">
        <Icon size={11} />
        {label}
      </div>
      <div className="text-xs font-semibold mt-0.5 truncate">{value}</div>
    </div>
  );
}
