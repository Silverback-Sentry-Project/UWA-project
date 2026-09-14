const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, BorderStyle, ShadingType,
  PageOrientation, LevelFormat, convertInchesToTwip, VerticalAlign,
} = require("docx");

const GREEN = "1a2f1a";
const GOLD = "a9873a";
const LIGHT = "e9f0e6";
const LIGHT2 = "f6efdc";

const numbering = {
  config: [
    {
      reference: "bullets",
      levels: [
        { level: 0, format: LevelFormat.BULLET, text: "\u2022", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } },
      ],
    },
  ],
};

function h(text, level) {
  return new Paragraph({
    heading: level,
    spacing: { before: 280, after: 140 },
    children: [new TextRun({ text, bold: true, color: GREEN })],
  });
}

function p(children, opts = {}) {
  return new Paragraph({ spacing: { after: 160, ...opts.spacing }, children, ...opts });
}

function run(text, opts = {}) {
  return new TextRun({ text, ...opts });
}

function bullet(children) {
  return new Paragraph({
    numbering: { reference: "bullets", level: 0 },
    spacing: { after: 120 },
    children,
  });
}

function boldLead(lead, rest) {
  return [run(lead, { bold: true }), run(rest)];
}

function cell(text, opts = {}) {
  const { bold = false, shade = null, width = null, color = null, size = 18, align = AlignmentType.LEFT } = opts;
  return new TableCell({
    width: width ? { size: width, type: WidthType.DXA } : undefined,
    shading: shade ? { type: ShadingType.CLEAR, fill: shade } : undefined,
    verticalAlign: VerticalAlign.CENTER,
    margins: { top: 80, bottom: 80, left: 100, right: 100 },
    children: [new Paragraph({
      alignment: align,
      children: [new TextRun({ text, bold, color: color || undefined, size })],
    })],
  });
}

const CELL_BORDER = { style: BorderStyle.SINGLE, size: 2, color: "cddbc7" };
const TABLE_BORDERS = {
  top: CELL_BORDER, bottom: CELL_BORDER, left: CELL_BORDER, right: CELL_BORDER,
  insideHorizontal: CELL_BORDER, insideVertical: CELL_BORDER,
};

// ---------------------------------------------------------------------------
// TITLE PAGE
// ---------------------------------------------------------------------------
const titleBlock = [
  new Paragraph({ spacing: { before: 1600, after: 120 }, alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "SilverBack Sentry", bold: true, size: 64, color: GREEN })] }),
  new Paragraph({ spacing: { after: 400 }, alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "A Digital Platform for Human\u2013Wildlife Coexistence in Uganda's Protected-Area Buffer Zones", bold: true, size: 28, color: GOLD })] }),
  new Paragraph({ spacing: { after: 200 }, alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "Concept Document", italics: true, size: 24 })] }),
  new Paragraph({ spacing: { after: 1600 }, alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "Prepared for the CITT Product Development Grant Review Panel | Mbarara University of Science and Technology | September 2026", size: 20 })] }),
];

// ---------------------------------------------------------------------------
// SECTION 1 - PROBLEM
// ---------------------------------------------------------------------------
const section1 = [
  h("1. The Problem", HeadingLevel.HEADING_1),
  p([
    ...boldLead("The reality. ", "Effective human\u2013wildlife coexistence depends on every incursion being reported promptly, every loss compensated fairly, and emerging conflict patterns identified before they claim another harvest, herd, or life. The Uganda Wildlife Act, 2019 gives communities in protected-area buffer zones a three-day window to file an incident report, yet the nearest Uganda Wildlife Authority (UWA) office can be a day's walk away. Rangers coordinate largely through ad hoc phone calls, and compensation claims move through a Verification Committee that convenes infrequently. In Kitgum District alone, more than 4,300 claims remain unresolved, and intermittent rural connectivity leaves most communities without any digital channel to report an incursion or track a claim."),
  ]),
  p([
    ...boldLead("The consequence. ", "Every month of delay deepens poverty among the subsistence households conservation depends on most. Research on community attitudes toward wildlife authorities consistently finds that procedural opacity, not the outcome itself, is the strongest predictor of hostility. Where the process is opaque, retaliation often follows through poisoning, snaring, and habitat destruction \u2014 a response that threatens endangered species and a tourism sector worth over USD 1 billion in annual foreign exchange."),
  ]),
  p([
    ...boldLead("The gap. ", "Uganda has already built strong individual tools for parts of this chain, but it has not yet been tested whether one offline-capable platform can unify incident reporting, ranger dispatch, hotspot analysis, and compensation tracking under the connectivity constraints that define these buffer zones."),
  ]),
  p([
    ...boldLead("The hook. ", "Closing that gap turns paper into a verifiable digital workflow for UWA, gives communities a transparent channel they can trust, gives rangers spatial dispatch intelligence, and gives policymakers the pattern data to act before, rather than after, the next conflict."),
  ]),
  p([
    ...boldLead("The objective. ", "SilverBack Sentry will design and deliver that platform, enabling real-time incident reporting, coordinated ranger dispatch, and transparent compensation management, to support sustainable human\u2013wildlife coexistence across Uganda's national parks."),
  ]),
];

// ---------------------------------------------------------------------------
// SECTION 2 - INNOVATION
// ---------------------------------------------------------------------------
const section2 = [
  h("2. The Intervention: Innovation", HeadingLevel.HEADING_1),
  h("2.1 Current Status: Building on What Already Works", HeadingLevel.HEADING_2),
  p([run("Uganda's conservation and community-reporting ecosystem already includes several tools that have proven their value in a specific part of the human\u2013wildlife conflict (HWC) management chain. SilverBack Sentry is designed to build on what each of them has already demonstrated works, and to connect the parts that, until now, have had no reason to talk to one another.")]),

  bullet(boldLead("Paper-based incident forms ", "remain UWA's official record of a reported incursion within the statutory three-day window, and they require no equipment, training, or connectivity to complete \u2014 an important strength in the lowest-resourced buffer zones (Mubalama & Byakagaba, 2022). Their limitation is not the form itself but what happens after filing: a paper record cannot be searched, aggregated across ranger posts, or fed into a pattern analysis.")),
  bullet(boldLead("Ushahidi and comparable open crowdsourcing platforms ", "proved that communities will report an incident when given an accessible channel, and their use in crisis mapping (Meier, 2015) opened the door for citizen reporting in low-infrastructure settings worldwide. Because the platform is intentionally general-purpose, a large share of submissions in comparable open deployments arrive without the structured fields (precise location, species, loss type) a conservation analyst needs \u2014 an opening for a domain-specific reporting schema to build directly on Ushahidi's proof-of-concept.")),
  bullet(boldLead("SMART (Spatial Monitoring and Reporting Tool) ", "is the tool of choice for ranger patrol data among WWF- and WCS-supported programmes (WWF/WCS, 2023), and its adoption across many countries shows how much value rangers place in structured spatial data. SMART was purpose-built for patrol teams with dedicated GIS capacity; extending equivalent spatial intelligence to smaller buffer-zone posts, and to the community side of reporting, is a natural next step rather than a shortfall of the tool itself.")),
  bullet(boldLead("KoboToolbox (KoboCollect) ", "has become a standard for citizen-science and field-survey data collection across East African conservation projects because it is free, offline-capable, and easy to deploy. It was built as a general survey tool, so spatial hotspot analytics, ranger dispatch, and a compensation workflow sit outside what it was designed to do.")),
  bullet(boldLead("Ad hoc phone coordination ", "is the de facto standard for most ranger units today, and it endures because it is immediate and needs no infrastructure. Its trade-off is that a call leaves no searchable record for the next shift, the next district, or the next policy review.")),
  bullet(boldLead("WildWatch-branded apps in India ", "are the closest direct precedent to this project in name and concept, and the closest evidence that the underlying idea is sound. Independent teams have shipped at least three apps under this name since 2017, most from the same Kerala university-incubator ecosystem: a Wildlife Trust of India / Amal Jyothi College of Engineering pilot with SMS alerts and early-warning lights at Periyar Tiger Reserve (Wildlife Trust of India, 2017); a private multi-module fence-and-sensor platform (WildWatch, n.d.); and the most recent, Leopard Tech Labs' WildWatch (Google Play, package com.leopard.hwc), a GIS-based public reporting app with 1-km-radius alerts, built under India's Forest-PLUS 3.0 programme \u2014 a joint initiative of India's Ministry of Environment, Forest and Climate Change and USAID \u2014 and scoped to Kerala's Forest Department (Leopard Tech Labs, 2025). A government-backed app reaching real users under this model is strong, independent validation that a mobile reporting channel is the right response to human\u2013wildlife conflict. None of the India builds, by their own public documentation, include a statutory compensation-claim workflow, an explicit offline-first design for sub-10%-connectivity settings, or any grounding in Uganda's legal or institutional framework \u2014 each was built for a single Indian state's forest department and its considerably better rural connectivity.")),

  p([run("Together, these approaches show that every building block SilverBack Sentry needs \u2014 paper compliance, community reporting, ranger-side spatial data, offline survey tools, real-time coordination, and even a shipped app under a similar name \u2014 already has a working precedent. What has not yet been tested is combining them into a single record for Uganda's buffer zones, so that a report filed by a community member, a dispatch decision made by a ranger, a hotspot flagged by the data, and a compensation claim tracked by a household all live in the same system (Baruch-Mordo et al., 2014).")]),
  p([run("Note: this SilverBack Sentry is an independently developed Ugandan platform, conceived and built without knowledge of the India-based apps discussed above; the name overlap is coincidental. A formal name-availability check is planned before any public or commercial launch, to avoid market and app-store confusion with the India-based products.", italics: true, size: 19, color: "4a4a4a" })]),

  h("2.2 The SilverBack Sentry Innovation", HeadingLevel.HEADING_2),
  p([run("SilverBack Sentry is an offline-first digital platform that carries a single incident record through its full lifecycle \u2014 report, dispatch, analysis, and compensation \u2014 so that no step has to restart on a new tool or a new form. The current working prototype (source code reviewed for this document) already implements the core of this pipeline; a smaller number of components are designed and scoped but not yet built. Both are shown below, so the panel sees the actual state of the build rather than only the end vision.")]),

  h("Built in the current prototype", HeadingLevel.HEADING_3),
  bullet(boldLead("Offline-first community and ranger reporting. ", "A structured, geotagged mobile form with camera capture lets a user log an incursion or sighting; the report queues in a local offline outbox and syncs automatically once a connection is available, so a day's walk to the nearest office is no longer the only way to file on time.")),
  bullet(boldLead("Ranger dispatch queue. ", "A warden assigns an incoming report to a named ranger from a shared, status-tracked queue (New \u2192 Assigned \u2192 in progress \u2192 resolved), replacing the ad hoc phone call with a record every shift and every district can see.")),
  bullet(boldLead("Geospatial incident mapping. ", "Every report plots to an interactive map by park, species, and time, giving wardens a live, visual picture of where incidents are concentrated \u2014 the foundation that automated hotspot detection is built on top of.")),
  bullet(boldLead("Auditable compensation workflow. ", "A compensation claim, its supporting documents, its decision, and its payment are tracked as linked records with a full status-history trail, so a claim's progress from submission to payout is visible rather than living only in a committee's paper file.")),
  bullet(boldLead("Role-scoped access. ", "Ranger, warden, UWA official, community, and administrator roles each see only the screens and data relevant to them, enforced at both the mobile and web-portal layers.")),

  h("Designed \u2014 next on the roadmap", HeadingLevel.HEADING_3),
  bullet(boldLead("Automated hotspot analysis. ", "Kernel density estimation, run continuously on incoming reports to surface an emerging hotspot without a warden having to read the map by eye, extending SMART-grade spatial intelligence to buffer-zone posts without requiring a dedicated GIS specialist on site.")),
  bullet(boldLead("GPS-routed dispatch. ", "Auto-suggesting the nearest available ranger post for a new report, on top of the existing assignment queue, so dispatch time drops further once a human warden no longer has to judge proximity manually.")),
  bullet(boldLead("SMS channel. ", "Feature-phone reporting and SMS status notifications for compensation claimants without smartphone access, closing the channel gap for the least-connected households \u2014 already identified as a near-term priority in the team's own post-prototype review.")),
  bullet(boldLead("Indigenous knowledge capture. ", "A structured, geotagged submission schema to give community ecological knowledge \u2014 where animals move, when, and why \u2014 a formal entry point into UWA's planning data for the first time (Twinamatsiko et al., 2014), extending the participatory foundation Ushahidi-style crowdsourcing established.")),
  p([run("Offline-first architecture underpins the mobile reporting and ranger experience today; the web portal used by wardens and UWA officials assumes the reliable office-based connectivity those roles already have, consistent with how UWA's own administrative work is carried out.")]),

  h("2.3 Evidence of Feasibility", HeadingLevel.HEADING_2),
  p([run("Each roadmap component above is designed to reproduce a documented result from a comparable deployment elsewhere, giving the roadmap \u2014 not only the built prototype \u2014 an evidence base going into the pilot:")]),
  bullet([run("A review of 14 ranger-support deployments found that adding geographic dispatch data cut response time by 34% "), run("(Jumabay et al., 2021)", { italics: true }), run(", the class of intelligence GPS-routed dispatch is designed to add on top of SilverBack Sentry's existing assignment queue.")]),
  bullet([run("Giving crowdsourced reports a structured schema reduced unusable submissions by 62% in the Ushahidi Haiti deployment "), run("(Heinzelman & Waters, 2010)", { italics: true }), run(", the improvement SilverBack Sentry's domain-specific reporting form is designed to carry forward.")]),
  bullet([run("Offline-first mobile tools sustain 3.2\u00d7 higher usage in comparable sub-Saharan deployments "), run("(GSMA, 2023)", { italics: true }), run(", a decisive advantage given Uganda's rural internet penetration of roughly 9% "), run("(FinScope Uganda, 2023)", { italics: true }), run(" \u2014 the constraint the built offline outbox already addresses today.")]),
  bullet([run("Digital audit trails were the single most effective trust-rebuilding intervention across 23 ICT-mediated accountability programmes reviewed globally "), run("(Peixoto & Fox, 2016)", { italics: true }), run(", directly supporting the compensation-tracking workflow already implemented in the prototype.")]),
];

// ---------------------------------------------------------------------------
// SECTION 2.4 - CLAT (landscape)
// ---------------------------------------------------------------------------
const clatHeading = [
  h("2.4 Competitive Landscape Analysis Table (CLAT)", HeadingLevel.HEADING_2),
  p([run("SilverBack Sentry's own column below is split to distinguish what the current prototype already does from what is planned; \u201cplanned\u201d items carry the feasibility evidence in Section 2.3, not a claim of present capability.", italics: true, size: 19 })]),
];

const clatHeaders = ["Capability", "Paper Forms", "Ushahidi-style", "SMART", "KoboToolbox", "Ad Hoc Phone", "WildWatch (India \u2014 Leopard Tech Labs et al.)", "SilverBack Sentry (Uganda \u2014 this platform)"];
const clatWidths = [1500, 1150, 1250, 1150, 1250, 1150, 1900, 1900];

const clatRows = [
  ["Community-submitted reporting", "Yes", "Yes", "Not in scope (ranger-only)", "Yes", "Yes (unrecorded)", "Yes \u2014 GIS-based, public", "Yes \u2014 structured & offline"],
  ["Works with low / no connectivity", "Yes", "Partial", "Partial (sync required)", "Yes", "Yes (voice only)", "Not documented (India rural connectivity is much higher)", "Yes \u2014 built (offline outbox)"],
  ["Ranger dispatch & routing", "Not in scope", "Not in scope", "Partial (patrol plan)", "Not in scope", "Yes (manual)", "Not in scope (broadcast alert, not routed)", "Yes \u2014 tracked queue built; GPS auto-routing planned"],
  ["Automated hotspot analysis", "Not in scope", "Not in scope", "Yes (specialist-run)", "Not in scope", "Not in scope", "Partial (hotspot mapping; method undisclosed)", "Partial \u2014 live map built; KDE planned"],
  ["Compensation claim tracking", "Partial (paper file)", "Not in scope", "Not in scope", "Not in scope", "Not in scope", "Not in scope", "Yes \u2014 auditable workflow built; SMS planned"],
  ["Indigenous knowledge capture", "Partial", "Partial", "Not in scope", "Partial (general)", "Not in scope", "Not in scope", "Planned \u2014 schema designed, not yet built"],
  ["Built for Uganda Wildlife Act, 2019", "Yes (statutory form)", "No", "No", "No", "No", "No \u2014 scoped to Kerala Forest Department", "Yes"],
  ["Usable without a GIS specialist", "Yes", "Yes", "No", "Yes", "Yes", "Yes", "Yes"],
];

function clatTable() {
  const headerRow = new TableRow({
    tableHeader: true,
    children: clatHeaders.map((t, i) => cell(t, { bold: true, shade: GREEN, color: "FFFFFF", width: clatWidths[i], size: 17 })),
  });
  const rows = clatRows.map((r, ri) => new TableRow({
    children: r.map((t, i) => cell(t, {
      bold: i === 0,
      shade: i === 0 ? LIGHT2 : (i === r.length - 1 ? LIGHT : (ri % 2 === 0 ? "FFFFFF" : "F7F5EE")),
      width: clatWidths[i],
      size: 17,
    })),
  }));
  return new Table({
    width: { size: clatWidths.reduce((a, b) => a + b, 0), type: WidthType.DXA },
    columnWidths: clatWidths,
    borders: TABLE_BORDERS,
    rows: [headerRow, ...rows],
  });
}

const clatFootnote = p([
  run("Table 1. Competitive Landscape Analysis \u2014 capability by tool. \u201cNot in scope\u201d marks a function the tool was not built to perform, not a shortcoming. WildWatch (India) entries reflect public app-store and programme documentation as of September 2026; capability details not publicly disclosed are marked \u201cnot documented\u201d rather than assumed absent.", italics: true, size: 18 }),
], { spacing: { before: 160 } });

// ---------------------------------------------------------------------------
// SECTION 3 - COMMERCIAL VIABILITY
// ---------------------------------------------------------------------------
function bmcRow(label, text) {
  return new TableRow({
    children: [
      cell(label, { bold: true, shade: LIGHT2, width: 2200 }),
      new TableCell({
        width: { size: 7300, type: WidthType.DXA },
        margins: { top: 80, bottom: 80, left: 100, right: 100 },
        children: [new Paragraph({ children: [new TextRun({ text, size: 19 })] })],
      }),
    ],
  });
}

const bmcData = [
  ["Customer Segments", "Primary: Uganda Wildlife Authority (institutional operator). Secondary: buffer-zone community households (free end users), district local governments and Verification Committees. Tertiary: conservation NGOs and research/donor programmes."],
  ["Value Proposition", "For UWA: a verifiable digital workflow that replaces paper, reduces claim backlog, and produces policy-ready hotspot data. For communities: a transparent channel to report and track claims without travel. For rangers: spatial dispatch intelligence without needing a GIS specialist."],
  ["Channels", "Direct onboarding through UWA ranger posts and Verification Committees; SMS short-code for feature-phone users (planned); community sensitisation through existing Local Council (LC1) structures."],
  ["Customer Relationships", "Structured onboarding and training for ranger posts; SMS-based community help-desk (planned); ongoing account management with UWA headquarters."],
  ["Revenue Streams", "Institutional subscription/licence paid by UWA (or donor-funded during the pilot); optional per-district service fee for expanded modules; opt-in, anonymised data-sharing agreements with research or NGO partners."],
  ["Key Resources", "Mobile and web application (built), offline-sync engine (built), SMS gateway (planned), hosted hotspot-analytics engine (planned), trained deployment team."],
  ["Key Activities", "Platform development and quality assurance, ranger and community training, data-quality review, spatial-model tuning."],
  ["Key Partners", "Prospective only at this stage \u2014 see Section 4.2. No partnership agreements are yet in place."],
  ["Cost Structure", "Software development and QA, cloud hosting and SMS gateway fees, field training and deployment logistics, ongoing support and maintenance, data-protection compliance."],
];

function bmcTable() {
  return new Table({
    width: { size: 9500, type: WidthType.DXA },
    columnWidths: [2200, 7300],
    borders: TABLE_BORDERS,
    rows: bmcData.map(([l, t]) => bmcRow(l, t)),
  });
}

function tamRow(tier, def, basis) {
  return new TableRow({
    children: [
      cell(tier, { bold: true, shade: GREEN, color: "FFFFFF", width: 1200 }),
      new TableCell({ width: { size: 4400, type: WidthType.DXA }, margins: { top: 80, bottom: 80, left: 100, right: 100 },
        children: [new Paragraph({ children: [new TextRun({ text: def, size: 19 })] })] }),
      new TableCell({ width: { size: 3900, type: WidthType.DXA }, margins: { top: 80, bottom: 80, left: 100, right: 100 },
        children: [new Paragraph({ children: [new TextRun({ text: basis, size: 19 })] })] }),
    ],
  });
}

function tamTable() {
  const header = new TableRow({
    tableHeader: true,
    children: [
      cell("Tier", { bold: true, shade: GREEN, color: "FFFFFF", width: 1200 }),
      cell("Definition", { bold: true, shade: GREEN, color: "FFFFFF", width: 4400 }),
      cell("Basis", { bold: true, shade: GREEN, color: "FFFFFF", width: 3900 }),
    ],
  });
  return new Table({
    width: { size: 9500, type: WidthType.DXA },
    columnWidths: [1200, 4400, 3900],
    borders: TABLE_BORDERS,
    rows: [
      header,
      tamRow("TAM", "Wildlife and protected-area authorities managing human\u2013wildlife conflict at the buffer-zone level across East Africa.", "Regional digital-conservation opportunity beyond Uganda, subject to future market validation."),
      tamRow("SAM", "Uganda Wildlife Authority's managed protected-area network (Uganda's national parks and wildlife reserves) and their surrounding buffer-zone districts.", "UWA is the single institutional buyer for the national roll-out; figures to be confirmed with UWA's planning department."),
      tamRow("SOM", "Pilot deployment in Kitgum District and 2\u20133 adjoining high-conflict buffer-zone districts within the first 24 months.", "Directly matches the backlog and connectivity conditions documented in Section 1; the realistic first foothold."),
    ],
  });
}

const section3 = [
  h("3. Commercial Viability", HeadingLevel.HEADING_1),
  h("3.1 Business Model Canvas", HeadingLevel.HEADING_2),
  p([run("SilverBack Sentry's business model is built around a public-institution buyer (UWA) and a free community-facing service, so that no subsistence household is ever asked to pay to report a loss.")]),
  bmcTable(),

  h("3.2 Cost Structure & Phased Plan", HeadingLevel.HEADING_2),
  p([run("Costs are organised in two phases so spending stays tied to evidence gathered at each stage, in line with the stage-gate approach used across the product-development workshop:")]),
  bullet(boldLead("Phase 1 \u2014 Pilot build & deploy (Kitgum District). ", "Completing the roadmap items in Section 2.2 (KDE hotspot analysis, GPS-routed dispatch, SMS channel), field training for ranger posts and Verification Committee staff, and a monitoring plan to test the assumptions in Section 2.3 against real usage.")),
  bullet(boldLead("Phase 2 \u2014 Scale to adjoining buffer-zone districts. ", "Extending onboarding and hosting capacity, refining the compensation workflow against Phase 1 evidence, and preparing an institutional handover model with UWA.")),
  bullet(boldLead("Note on figures. ", "Detailed unit costs (hosting, SMS-gateway fees, device provisioning, training days) should be quoted and validated with vendors and UWA's planning department before submission; this document presents the cost structure, not final budget figures, so the panel sees the true basis for later costing.")),

  h("3.3 Pricing Strategy", HeadingLevel.HEADING_2),
  p([run("Community incident reporting and claim-status tracking remain free at the point of use; charging a subsistence household to report a loss would undermine the platform's entire purpose. Revenue instead comes from an institutional subscription or grant-funded licence paid by UWA (or a pilot donor) that covers hosting, dispatch, and analytics modules, with a possible tiered structure by district population or park size once the pilot has produced verified usage data, and an optional, opt-in data-access arrangement for research and NGO partners.")]),

  h("3.4 Market Sizing (TAM \u2013 SAM \u2013 SOM)", HeadingLevel.HEADING_2),
  p([run("Sizing is presented as a logical funnel from region to pilot; precise monetary values require validation against UWA's planning department, Uganda Bureau of Statistics district data, and conservation-technology donor budgets, and are flagged below as a due-diligence step rather than stated as fixed figures.")]),
  tamTable(),
];

// ---------------------------------------------------------------------------
// SECTION 4 - TEAM & PARTNERSHIPS
// ---------------------------------------------------------------------------
const teamNames = [
  "Mandre Samson", "Lubega Mark", "Ayebare Hillary Bahati", "Birungi Angella Miriam",
  "Emarot Emmanuel", "Natukunda Jovita", "Murungi Kevin Tumaini",
];

function teamRow(name) {
  return new TableRow({
    children: [
      cell(name, { width: 2600 }),
      cell("[Confirm role \u2014 e.g. mobile lead, backend lead, web portal, design, QA]", { width: 3200, size: 17 }),
      cell("[Confirm relevant expertise / background]", { width: 3700, size: 17 }),
    ],
  });
}

function teamTable() {
  const header = new TableRow({
    tableHeader: true,
    children: [
      cell("Name", { bold: true, shade: GREEN, color: "FFFFFF", width: 2600 }),
      cell("Role on SilverBack Sentry", { bold: true, shade: GREEN, color: "FFFFFF", width: 3200 }),
      cell("Relevant Expertise", { bold: true, shade: GREEN, color: "FFFFFF", width: 3700 }),
    ],
  });
  return new Table({
    width: { size: 9500, type: WidthType.DXA },
    columnWidths: [2600, 3200, 3700],
    borders: TABLE_BORDERS,
    rows: [header, ...teamNames.map(teamRow)],
  });
}

const section4 = [
  h("4. Team & Partnerships", HeadingLevel.HEADING_1),
  h("4.1 Team", HeadingLevel.HEADING_2),
  p([run("Team member names below are drawn from the project's own prior materials. Please confirm and complete each person's role and expertise before submission \u2014 the panel evaluates delivery capacity partly through this section, and it should reflect the actual team presenting.", italics: true )]),
  teamTable(),

  h("4.2 Prospective Partnerships", HeadingLevel.HEADING_2),
  p([run("SilverBack Sentry has not yet entered any formal partnership agreements. The organisations below are prospective partners the team intends to approach during the pilot phase, listed to show a credible engagement pathway rather than a confirmed relationship:")]),
  bullet(boldLead("Uganda Wildlife Authority (UWA) \u2014 ", "prospective institutional adopter and primary data owner; formal engagement to begin with a pilot-scope discussion.")),
  bullet(boldLead("Kitgum District and neighbouring buffer-zone local governments \u2014 ", "prospective pilot sites, given the backlog already documented there.")),
  bullet(boldLead("Mobile network operators \u2014 ", "prospective SMS-gateway partners to enable the feature-phone reporting channel.")),
  bullet(boldLead("Conservation NGOs and donor programmes (e.g., WWF- and WCS-affiliated projects) \u2014 ", "prospective pilot co-funders and technical reviewers, given their existing investment in HWC tools such as SMART.")),
];

// ---------------------------------------------------------------------------
// REFERENCES
// ---------------------------------------------------------------------------
const refs = [
  "Baruch-Mordo, S. et al. (2014).",
  "FinScope Uganda (2023).",
  "GSMA (2023).",
  "Heinzelman, J. & Waters, C. (2010).",
  "Hill, C. M. (2015).",
  "Jumabay, K. et al. (2021).",
  "Kansky, R. & Knight, A. T. (2014).",
  "Leopard Tech Labs (2025). WildWatch \u2014 Google Play Store listing (com.leopard.hwc), built under India's Forest-PLUS 3.0 programme (MoEFCC / USAID).",
  "Meier, P. (2015).",
  "Mubalama, L. & Byakagaba, P. (2022).",
  "Peixoto, T. & Fox, J. (2016).",
  "Twinamatsiko, M. et al. (2014).",
  "Wildlife Trust of India & Amal Jyothi College of Engineering (2017). WildWatch pilot, Periyar Tiger Reserve.",
  "WWF/WCS (2023).",
];

const section5 = [
  h("References", HeadingLevel.HEADING_1),
  p([run("The citations below are listed as author and year, as supplied for this concept document. Full bibliographic details (publisher, journal, volume, DOI) should be added from the team's original source records before final submission, so every figure can be traced and defended if a panel member asks for the underlying study.", italics: true )]),
  ...refs.map((r) => bullet([run(r)])),
];

// ---------------------------------------------------------------------------
// DOCUMENT ASSEMBLY (3 sections: portrait / landscape CLAT / portrait)
// ---------------------------------------------------------------------------
const portraitPage = {
  size: { width: 12240, height: 15840 },
  margin: { top: 1080, bottom: 1080, left: 1080, right: 1080 },
};
const landscapePage = {
  size: { width: 15840, height: 12240 },
  orientation: PageOrientation.LANDSCAPE,
  margin: { top: 900, bottom: 900, left: 720, right: 720 },
};

const doc = new Document({
  numbering,
  styles: {
    default: {
      document: { run: { font: "Calibri", size: 21 }, paragraph: { spacing: { line: 276 } } },
    },
  },
  sections: [
    {
      properties: { page: portraitPage },
      children: [...titleBlock, ...section1, ...section2],
    },
    {
      properties: { page: landscapePage },
      children: [...clatHeading, clatTable(), clatFootnote],
    },
    {
      properties: { page: portraitPage },
      children: [...section3, ...section4, ...section5],
    },
  ],
});

Packer.toBuffer(doc).then((buf) => {
  require("fs").writeFileSync("/home/claude/work/SilverBack_Sentry_Concept_Document.docx", buf);
  console.log("written");
});
