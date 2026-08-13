// Thin fetch wrapper around the WildWatch Admin API (Laravel + Sanctum).
// Set VITE_API_URL=auto in .env to derive the API host from window.location.hostname
// (LAN-friendly). Or set an explicit URL, e.g. VITE_API_URL=http://192.168.1.42:8000/api

import { resolveApiBase } from "./api-base";

const API_BASE = resolveApiBase();
const TOKEN_KEY = "wildwatch_admin_token";

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string | null) {
  if (typeof window === "undefined") return;
  if (token) localStorage.setItem(TOKEN_KEY, token);
  else localStorage.removeItem(TOKEN_KEY);
}

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;
  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

export async function apiFetch<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken();

  // FormData bodies (file uploads) must NOT get an explicit Content-Type - the browser sets
  // "multipart/form-data; boundary=..." itself from the FormData instance, and forcing
  // application/json here (as every JSON caller of this function needs) would corrupt the
  // upload instead of just being redundant.
  const isFormData = typeof FormData !== "undefined" && options.body instanceof FormData;

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      Accept: "application/json",
      ...(isFormData ? {} : { "Content-Type": "application/json" }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers as Record<string, string> | undefined),
    },
  });

  if (!res.ok) {
    let message = `Request failed (${res.status})`;
    let errors: Record<string, string[]> | undefined;
    try {
      const body = await res.json();
      message = body.message ?? message;
      errors = body.errors;
    } catch {
      // response wasn't JSON — keep the default message
    }
    throw new ApiError(message, res.status, errors);
  }

  if (res.status === 204) return undefined as T;
  return res.json() as Promise<T>;
}
