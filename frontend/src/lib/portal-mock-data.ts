export type IncidentStatus = "active" | "pending" | "resolved";
export type IncidentType = "Conflict" | "Sighting" | "Emergency" | "Poaching";
export type Park = "Bwindi Impenetrable" | "Mgahinga Gorilla" | "Murchison Falls" | "Queen Elizabeth" | "Kibale";

export const PARKS: Park[] = [
  "Bwindi Impenetrable",
  "Mgahinga Gorilla",
  "Murchison Falls",
  "Queen Elizabeth",
  "Kibale",
];

export const SPECIES = ["Mountain Gorilla", "African Elephant", "Buffalo", "Chimpanzee", "Leopard", "Hippopotamus", "Baboon"];

export interface Incident {
  id: string;
  type: IncidentType;
  status: IncidentStatus;
  park: Park;
  community: string;
  species: string;
  severity: "Low" | "Medium" | "High" | "Critical";
  reporter: string;
  assignedTo?: string;
  reportedAt: string;
  summary: string;
  lat: number;
  lng: number;
}

export interface Ranger {
  id: string; name: string; park: Park; status: "Available" | "On patrol" | "Off-duty"; load: number; rank: string;
}

export interface Claim {
  id: string; claimant: string; park: Park; incidentId: string; amountUGX: number;
  status: "Submitted" | "Under Verification" | "Approved" | "Rejected" | "Paid";
  submittedAt: string; species: string; damage: string;
}

export interface AuditEntry {
  id: string; actor: string; role: string; action: string; target: string; at: string;
}

const reporters = ["Asiimwe John", "Nakato Sarah", "Tumusiime Paul", "Birungi Grace", "Owino David", "Akello Mary"];
const rangerNames = ["Ranger Mugisha", "Ranger Atuhaire", "Ranger Okello", "Warden Nabwire", "Ranger Kato", "Warden Ssali"];
const communities = ["Buhoma", "Ruhija", "Nkuringo", "Ntebeko", "Paraa", "Mweya", "Bigodi"];
const summaries = [
  "Elephant herd damaged 2 acres of maize overnight.",
  "Gorilla group sighted near community trail — guides notified.",
  "Buffalo charged livestock; one cow injured.",
  "Suspected snare found near forest edge.",
  "Chimpanzee raided banana plantation.",
  "Leopard prints reported near homestead.",
  "Hippo blocked main path to water source.",
];

function pick<T>(arr: T[], i: number) { return arr[i % arr.length]; }

export const INCIDENTS: Incident[] = Array.from({ length: 28 }).map((_, i) => {
  const types: IncidentType[] = ["Conflict", "Sighting", "Emergency", "Poaching"];
  const statuses: IncidentStatus[] = ["active", "pending", "resolved", "active", "pending"];
  const sevs = ["Low", "Medium", "High", "Critical"] as const;
  return {
    id: `WW-${(2401 + i).toString()}`,
    type: pick(types, i),
    status: pick(statuses, i),
    park: pick(PARKS, i),
    community: pick(communities, i),
    species: pick(SPECIES, i),
    severity: pick([...sevs], i + (i % 3)),
    reporter: pick(reporters, i),
    assignedTo: i % 4 === 0 ? undefined : pick(rangerNames, i),
    reportedAt: new Date(Date.now() - i * 3600_000 * 5).toISOString(),
    summary: pick(summaries, i),
    lat: -1.05 + (i % 7) * 0.04,
    lng: 29.65 + (i % 5) * 0.05,
  };
});

export const RANGERS: Ranger[] = rangerNames.map((name, i) => ({
  id: `R-${100 + i}`,
  name,
  park: pick(PARKS, i),
  status: pick(["Available", "On patrol", "Off-duty", "Available", "On patrol"] as const, i),
  load: (i * 2 + 1) % 6,
  rank: i % 2 === 0 ? "Senior Ranger" : "Game Warden",
}));

export const CLAIMS: Claim[] = Array.from({ length: 14 }).map((_, i) => {
  const statuses = ["Submitted", "Under Verification", "Approved", "Rejected", "Paid"] as const;
  return {
    id: `CL-${(901 + i).toString()}`,
    claimant: pick(reporters, i),
    park: pick(PARKS, i),
    incidentId: `WW-${2401 + i}`,
    amountUGX: 250_000 + (i * 175_000),
    status: pick([...statuses], i),
    submittedAt: new Date(Date.now() - i * 86_400_000).toISOString(),
    species: pick(SPECIES, i),
    damage: pick(["Crop damage", "Livestock loss", "Property damage", "Injury"], i),
  };
});

export const AUDIT: AuditEntry[] = Array.from({ length: 18 }).map((_, i) => ({
  id: `A-${5000 + i}`,
  actor: pick(["N. Kintu", "J. Mubiru", "R. Apio", "F. Otim"], i),
  role: pick(["Super Admin", "Reviewer", "Finance", "Warden Coordinator"], i),
  action: pick(["Approved claim", "Assigned ranger", "Rejected claim", "Marked paid", "Signed in", "Updated incident"], i),
  target: pick(["CL-902", "WW-2410", "CL-905", "CL-901", "—", "WW-2415"], i),
  at: new Date(Date.now() - i * 1800_000).toISOString(),
}));

export function fmtDate(iso: string) {
  const d = new Date(iso);
  return d.toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
}

export function fmtUGX(n: number) {
  return "UGX " + n.toLocaleString("en-UG");
}