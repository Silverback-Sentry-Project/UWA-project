import { useEffect, useState } from "react";

export type UserPrefs = {
  fullName: string;
  language: string;
  park: string;
};

const KEY = "wildwatch.prefs";
const EVENT = "wildwatch:prefs";
const DEFAULTS: UserPrefs = {
  fullName: "Amara Nakato",
  language: "English",
  park: "Bwindi Impenetrable",
};

export function getPrefs(): UserPrefs {
  if (typeof window === "undefined") return DEFAULTS;
  try {
    const raw = window.localStorage.getItem(KEY);
    if (!raw) return DEFAULTS;
    return { ...DEFAULTS, ...JSON.parse(raw) };
  } catch {
    return DEFAULTS;
  }
}

export function setPrefs(partial: Partial<UserPrefs>) {
  if (typeof window === "undefined") return;
  const next = { ...getPrefs(), ...partial };
  window.localStorage.setItem(KEY, JSON.stringify(next));
  window.dispatchEvent(new CustomEvent(EVENT));
}

export function useUserPrefs(): UserPrefs {
  const [prefs, setState] = useState<UserPrefs>(DEFAULTS);
  useEffect(() => {
    setState(getPrefs());
    const onChange = () => setState(getPrefs());
    window.addEventListener(EVENT, onChange);
    window.addEventListener("storage", onChange);
    return () => {
      window.removeEventListener(EVENT, onChange);
      window.removeEventListener("storage", onChange);
    };
  }, []);
  return prefs;
}

export function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? "")
    .join("") || "U";
}