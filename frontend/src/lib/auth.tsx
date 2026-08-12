import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { apiFetch, getToken, setToken } from "@/lib/api";

export interface PortalUser {
  user_id: string;
  full_name: string;
  email: string;
  roles: string[];
  account_type: "uwa" | "gamepark";
  park: { park_id: string; park_name: string } | null;
}

interface AuthContextValue {
  user: PortalUser | null;
  loading: boolean;
  login: (params: {
    email?: string;
    password: string;
    accountType: "uwa" | "gamepark";
    parkId?: string;
  }) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<PortalUser | null>(null);
  const [loading, setLoading] = useState(true);

  // On first load, if a token is already stored, confirm it's still valid.
  useEffect(() => {
    const token = getToken();
    if (!token) {
      setLoading(false);
      return;
    }
    apiFetch<PortalUser>("/me")
      .then(setUser)
      .catch(() => setToken(null))
      .finally(() => setLoading(false));
  }, []);

  async function login({
    email,
    password,
    accountType,
    parkId,
  }: {
    email?: string;
    password: string;
    accountType: "uwa" | "gamepark";
    parkId?: string;
  }) {
    const data = await apiFetch<{ token: string; user: PortalUser }>("/login", {
      method: "POST",
      body: JSON.stringify({ email, password, account_type: accountType, park_id: parkId }),
    });
    setToken(data.token);
    setUser(data.user);
  }

  async function logout() {
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      // token may already be invalid/expired — clear locally regardless
    }
    setToken(null);
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>{children}</AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within an AuthProvider");
  return ctx;
}
