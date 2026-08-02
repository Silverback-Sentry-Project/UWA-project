/**
 * Resolve the Laravel API base URL for browser clients.
 *
 * Set VITE_API_URL in .env to an explicit URL, or use `auto` (default) to
 * derive http://<current-hostname>:8000/api — works for localhost and LAN IP
 * access without rebuilding when your machine's IP changes.
 */
export function resolveApiBase(): string {
  const configured = import.meta.env.VITE_API_URL as string | undefined;

  if (configured && configured !== "auto") {
    return configured.replace(/\/$/, "");
  }

  if (typeof window !== "undefined") {
    return `http://${window.location.hostname}:8000/api`;
  }

  return "http://localhost:8000/api";
}
