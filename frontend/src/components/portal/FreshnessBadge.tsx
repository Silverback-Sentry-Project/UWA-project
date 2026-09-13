import { BadgeCheck, FileQuestion, ShieldQuestion } from "lucide-react";
import type { LucideIcon } from "lucide-react";

// Data-provenance badges for the portal's reference data (Ranked borrowing #1 from
// Wild-India-Atlas). Three explicit confidence tiers explain where a park or species
// record came from instead of presenting every value as equally authoritative:
//   official    - from an authoritative source (UWA park registry / IUCN status)
//   inferred    - derived or approximated (the conservative default)
//   unconfirmed - surfaced but not yet verified
// Rendered in the same oklch badge language as StatusBadge so they read as part of the
// portal's visual system rather than a one-off component.

type Confidence = "official" | "inferred" | "unconfirmed";

const CONFIDENCE_MAP: Record<string, { bg: string; fg: string; icon: LucideIcon; label: string }> =
  {
    official: {
      bg: "oklch(0.95 0.05 150)",
      fg: "oklch(0.42 0.12 155)",
      icon: BadgeCheck,
      label: "Official",
    },
    inferred: {
      bg: "oklch(0.95 0.04 75)",
      fg: "oklch(0.5 0.15 65)",
      icon: ShieldQuestion,
      label: "Inferred",
    },
    unconfirmed: {
      bg: "oklch(0.93 0.015 240)",
      fg: "oklch(0.45 0.04 250)",
      icon: FileQuestion,
      label: "Unconfirmed",
    },
  };

const DEFAULT_CONFIDENCE: Confidence = "inferred";

export function ConfidenceBadge({ confidence }: { confidence?: string | null }) {
  const c = CONFIDENCE_MAP[confidence ?? ""] ?? CONFIDENCE_MAP[DEFAULT_CONFIDENCE];
  return (
    <span
      className="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-lg capitalize border border-black/5 shadow-sm transition-all"
      style={{ background: c.bg, color: c.fg }}
    >
      <c.icon size={11} />
      {c.label}
    </span>
  );
}

function relativeLabel(iso: string | null | undefined): string {
  if (!iso) return "Never verified";
  const then = new Date(iso).getTime();
  if (Number.isNaN(then)) return "Unknown";
  const diffMs = Date.now() - then;
  const minutes = Math.floor(diffMs / 60000);
  if (minutes < 1) return "Verified just now";
  if (minutes < 60) return `Verified ${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `Verified ${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 30) return `Verified ${days}d ago`;
  const months = Math.floor(days / 30);
  if (months < 12) return `Verified ${months}mo ago`;
  const years = Math.floor(months / 12);
  return `Verified ${years}y ago`;
}

export function VerifiedAgo({ lastVerifiedAt }: { lastVerifiedAt?: string | null }) {
  return (
    <span className="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
      {relativeLabel(lastVerifiedAt)}
    </span>
  );
}
