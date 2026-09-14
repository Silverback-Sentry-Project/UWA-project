import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { ConfidenceBadge, VerifiedAgo } from "@/components/portal/FreshnessBadge";
import { apiFetch } from "@/lib/api";
import { BadgeCheck, FileQuestion, ShieldQuestion, Database, BookMarked } from "lucide-react";

export const Route = createFileRoute("/portal/datasources")({ component: DataSources });

interface DataSourcePark {
  park_id: number;
  park_name: string;
  district: string;
  description: string | null;
  firestore_id: string | null;
  confidence: "official" | "inferred" | "unconfirmed" | null;
  last_verified_at: string | null;
}

interface DataSourceSpecies {
  species_id: number;
  common_name: string;
  scientific_name: string;
  conservation_status: string | null;
  confidence: "official" | "inferred" | "unconfirmed" | null;
  last_verified_at: string | null;
}

// The three trust tiers are explained on the page instead of living only in badge
// tooltips, so the provenance story is legible at a glance (mirrors the Wild-India-Atlas
// data-sources page this feature was borrowed from).
const TIERS = [
  {
    key: "official",
    label: "Official",
    icon: BadgeCheck,
    desc: "From an authoritative record — UWA park registry, IUCN conservation status.",
    bg: "oklch(0.95 0.05 150)",
    fg: "oklch(0.42 0.12 155)",
  },
  {
    key: "inferred",
    label: "Inferred",
    icon: ShieldQuestion,
    desc: "Derived or approximated. The conservative default for anything not explicitly verified.",
    bg: "oklch(0.95 0.04 75)",
    fg: "oklch(0.5 0.15 65)",
  },
  {
    key: "unconfirmed",
    label: "Unconfirmed",
    icon: FileQuestion,
    desc: "Surfaced in the portal but not yet checked against a source.",
    bg: "oklch(0.93 0.015 240)",
    fg: "oklch(0.45 0.04 250)",
  },
];

function DataSources() {
  const { data: parks, isLoading: parksLoading } = useQuery({
    queryKey: ["data-parks"],
    queryFn: () => apiFetch<DataSourcePark[]>("/parks"),
  });

  const { data: species, isLoading: speciesLoading } = useQuery({
    queryKey: ["data-species"],
    queryFn: () => apiFetch<DataSourceSpecies[]>("/species"),
  });

  return (
    <PortalShell
      title="Data Sources"
      subtitle="Where every reference record comes from, how confident we are in it, and when it was last verified."
    >
      {/* Provenance legend */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {TIERS.map((t) => (
          <div key={t.key} className="portal-card p-5 bg-white">
            <div className="flex items-center gap-3">
              <div
                className="h-10 w-10 rounded-2xl grid place-items-center shrink-0 border border-black/5"
                style={{ background: t.bg, color: t.fg }}
              >
                <t.icon size={18} />
              </div>
              <div>
                <div className="text-[13px] font-black portal-display text-neutral-900">
                  {t.label}
                </div>
                <ConfidenceBadge confidence={t.key} />
              </div>
            </div>
            <p className="mt-3 text-[12px] text-neutral-500 font-medium leading-relaxed">
              {t.desc}
            </p>
          </div>
        ))}
      </div>

      {/* Parks */}
      <div className="portal-card overflow-hidden mb-6">
        <div className="px-5 py-4 border-b border-neutral-100 flex items-center gap-3 bg-white">
          <div className="h-9 w-9 rounded-xl bg-[rgba(26,47,26,0.06)] grid place-items-center text-[#1A2F1A]">
            <Database size={16} />
          </div>
          <div>
            <div className="text-[14px] font-black portal-display text-neutral-900">Parks</div>
            <div className="text-[11px] text-neutral-400 font-bold">
              {parksLoading ? "Loading…" : `${parks?.length ?? 0} national park records`}
            </div>
          </div>
        </div>
        <table className="portal-table">
          <thead>
            <tr>
              <th>Park</th>
              <th>District</th>
              <th>Confidence</th>
              <th>Last verified</th>
            </tr>
          </thead>
          <tbody>
            {!parksLoading &&
              (parks ?? []).map((p) => (
                <tr key={p.park_id}>
                  <td>
                    <div className="font-bold text-neutral-800">{p.park_name}</div>
                    <div className="text-[11px] text-neutral-400 font-mono">{p.firestore_id}</div>
                  </td>
                  <td>{p.district}</td>
                  <td>
                    <ConfidenceBadge confidence={p.confidence} />
                  </td>
                  <td>
                    <VerifiedAgo lastVerifiedAt={p.last_verified_at} />
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
        {!parksLoading && (parks ?? []).length === 0 && (
          <div className="p-6 text-sm text-neutral-400 font-medium">No park records yet.</div>
        )}
      </div>

      {/* Species */}
      <div className="portal-card overflow-hidden">
        <div className="px-5 py-4 border-b border-neutral-100 flex items-center gap-3 bg-white">
          <div className="h-9 w-9 rounded-xl bg-[rgba(26,47,26,0.06)] grid place-items-center text-[#1A2F1A]">
            <BookMarked size={16} />
          </div>
          <div>
            <div className="text-[14px] font-black portal-display text-neutral-900">Species</div>
            <div className="text-[11px] text-neutral-400 font-bold">
              {speciesLoading ? "Loading…" : `${species?.length ?? 0} species records`}
            </div>
          </div>
        </div>
        <table className="portal-table">
          <thead>
            <tr>
              <th>Common name</th>
              <th>Scientific name</th>
              <th>IUCN status</th>
              <th>Confidence</th>
              <th>Last verified</th>
            </tr>
          </thead>
          <tbody>
            {!speciesLoading &&
              (species ?? []).map((s) => (
                <tr key={s.species_id}>
                  <td>
                    <div className="font-bold text-neutral-800">{s.common_name}</div>
                  </td>
                  <td className="italic text-neutral-500">{s.scientific_name}</td>
                  <td>{s.conservation_status ?? "—"}</td>
                  <td>
                    <ConfidenceBadge confidence={s.confidence} />
                  </td>
                  <td>
                    <VerifiedAgo lastVerifiedAt={s.last_verified_at} />
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
        {!speciesLoading && (species ?? []).length === 0 && (
          <div className="p-6 text-sm text-neutral-400 font-medium">No species records yet.</div>
        )}
      </div>
    </PortalShell>
  );
}
