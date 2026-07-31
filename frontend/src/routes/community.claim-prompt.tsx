import { createFileRoute, Link } from "@tanstack/react-router";
import { PhoneFrame, StatusBar } from "@/components/PhoneFrame";
import { CheckCircle2, Receipt, ArrowRight, Home } from "lucide-react";

export const Route = createFileRoute("/community/claim-prompt")({ component: ClaimPrompt });

function ClaimPrompt() {
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <StatusBar />
        <div className="flex-1 flex flex-col items-center justify-center px-7 text-center">
          <div className="relative mb-6">
            <div className="absolute inset-0 rounded-full bg-success/20 animate-ping" />
            <div className="relative h-24 w-24 rounded-full bg-success/15 grid place-items-center">
              <CheckCircle2 size={56} className="text-success" />
            </div>
          </div>
          <h1 className="text-2xl font-extrabold" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
            Report submitted
          </h1>
          <p className="text-muted-foreground text-sm mt-2 leading-relaxed">
            Thank you for helping protect wildlife. Rangers have been notified and will follow up shortly.
          </p>

          <div className="mt-8 w-full bg-card rounded-2xl p-4 shadow-card text-left">
            <div className="flex items-start gap-3">
              <div className="h-11 w-11 rounded-2xl bg-accent/20 grid place-items-center shrink-0">
                <Receipt size={20} className="text-accent-foreground" />
              </div>
              <div className="min-w-0">
                <div className="text-sm font-bold">Did this incident cause loss or damage?</div>
                <p className="text-xs text-muted-foreground mt-1">
                  You can file a compensation claim now or anytime later from the dashboard. This step is optional.
                </p>
              </div>
            </div>
          </div>
        </div>

        <div className="px-6 pb-10 pt-4 space-y-3">
          <Link to="/community/claim" className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md flex items-center justify-center gap-2">
            File a compensation claim <ArrowRight size={16} />
          </Link>
          <Link to="/community" className="w-full bg-secondary text-foreground py-4 rounded-2xl font-semibold flex items-center justify-center gap-2">
            <Home size={16} /> Skip & return home
          </Link>
        </div>
      </div>
    </PhoneFrame>
  );
}