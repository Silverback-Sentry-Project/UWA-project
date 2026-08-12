import { createFileRoute, Outlet } from "@tanstack/react-router";
export const Route = createFileRoute("/ranger")({ component: () => <Outlet /> });
