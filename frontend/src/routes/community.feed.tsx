import { createFileRoute } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { Heart, MessageCircle, Share2, Leaf } from "lucide-react";
import { useUserPrefs } from "@/lib/user-prefs";

export const Route = createFileRoute("/community/feed")({ component: Feed });

const ARTICLES = [
  { tag: "Conservation", title: "How community sightings doubled crane counts in 2026", excerpt: "Citizen reports from 14 villages helped UWA map nesting grounds with 92% accuracy…", time: "5 min read", grad: "gradient-forest" },
  { tag: "Safety", title: "Living with elephants: 7 field-tested tips", excerpt: "Practical advice from rangers in Murchison Falls on protecting crops without harming wildlife.", time: "8 min read", grad: "gradient-sunset" },
  { tag: "Stories", title: "From farmer to community ranger", excerpt: "A profile of Joseph, who turned conflict into stewardship for his entire sub-county.", time: "6 min read", grad: "gradient-sky" },
];

function Feed() {
  const prefs = useUserPrefs();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Feed & Articles" subtitle={`${prefs.park} community`} back="/community" />
        <div className="px-5 pb-6 space-y-4">
          <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-1 px-1">
            {["For you", "Conservation", "Safety", "Stories", "Policy"].map((f, i) => (
              <button key={f} className={`px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap ${i === 0 ? "bg-primary text-primary-foreground" : "bg-secondary text-muted-foreground"}`}>{f}</button>
            ))}
          </div>

          {ARTICLES.map((a, i) => (
            <article key={i} className="bg-card rounded-2xl shadow-card overflow-hidden">
              <div className={`h-32 ${a.grad} relative`}>
                <div className="absolute top-3 left-3"><Pill tone="default">{a.tag}</Pill></div>
                <Leaf className="absolute bottom-3 right-3 text-white/40" size={42} />
              </div>
              <div className="p-4">
                <h3 className="font-bold text-base leading-snug">{a.title}</h3>
                <p className="text-xs text-muted-foreground mt-1.5">{a.excerpt}</p>
                <div className="flex items-center justify-between mt-3 text-muted-foreground">
                  <span className="text-[11px]">{a.time} · UWA News</span>
                  <div className="flex items-center gap-3">
                    <button className="flex items-center gap-1 text-[11px]"><Heart size={14} />128</button>
                    <button className="flex items-center gap-1 text-[11px]"><MessageCircle size={14} />24</button>
                    <button><Share2 size={14} /></button>
                  </div>
                </div>
              </div>
            </article>
          ))}
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}