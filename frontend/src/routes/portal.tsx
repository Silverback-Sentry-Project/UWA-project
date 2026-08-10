import { createFileRoute, Outlet, useNavigate, useRouterState } from "@tanstack/react-router";
import { useEffect } from "react";
import { AuthProvider, useAuth } from "@/lib/auth";
import { ParkProvider } from "@/lib/park-context";

export const Route = createFileRoute("/portal")({
  head: () => ({
    title: "WildWatch Portal",
    meta: [
      {
        name: "description",
        content: "Administrative portal for wildlife protection and data management.",
      },
    ],
  }),
  component: () => (
    <AuthProvider>
      <ParkProvider>
        <PortalGuard />
      </ParkProvider>
    </AuthProvider>
  ),
});

function PortalGuard() {
  const { user, loading } = useAuth();
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const navigate = useNavigate();
  const isLoginPage = pathname === "/portal" || pathname === "/portal/";

  useEffect(() => {
    if (loading) return;
    if (!user && !isLoginPage) navigate({ to: "/portal" });
    if (user && isLoginPage) navigate({ to: "/portal/dashboard" });
  }, [user, loading, isLoginPage, navigate]);

  if (loading) {
    return (
      <div className="min-h-screen grid place-items-center text-sm text-[var(--p-ink-soft,#666)]">
        Checking session…
      </div>
    );
  }

  // While a redirect is pending (wrong page for auth state), render nothing to avoid a flash.
  // if ((!user && !isLoginPage) || (user && isLoginPage)) return null;

  return <Outlet />;
}
