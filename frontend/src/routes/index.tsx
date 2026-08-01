import { createFileRoute, redirect } from "@tanstack/react-router";

// The mobile community-app splash screen used to live here. This project
// is being run as the UWA admin portal, so "/" now goes straight to
// "/portal" (which itself redirects to the login screen if signed out).
export const Route = createFileRoute("/")({
  beforeLoad: () => {
    throw redirect({ to: "/portal" });
  },
});
