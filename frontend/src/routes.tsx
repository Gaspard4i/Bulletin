import {
  createRootRoute,
  createRoute,
  redirect,
} from "@tanstack/react-router";
import App from "@/App";
import { DashboardLayout } from "@/components/layout/DashboardLayout";
import { LoginPage } from "@/pages/LoginPage";
import { DashboardPage } from "@/pages/DashboardPage";
import { BulletinsPage } from "@/pages/student/BulletinsPage";
import { GradesPage } from "@/pages/teacher/GradesPage";
import { ValidationPage } from "@/pages/scolarite/ValidationPage";
import { UsersPage } from "@/pages/admin/UsersPage";

const rootRoute = createRootRoute({
  component: App,
});

const loginRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: "/login",
  component: LoginPage,
});

const dashboardLayoutRoute = createRoute({
  getParentRoute: () => rootRoute,
  id: "dashboard-layout",
  component: DashboardLayout,
  beforeLoad: () => {
    const token = localStorage.getItem("access_token");
    if (!token) {
      throw redirect({ to: "/login" });
    }
  },
});

const dashboardRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/",
  component: DashboardPage,
});

const bulletinsRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/bulletins",
  component: BulletinsPage,
});

const gradesRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/grades",
  component: GradesPage,
});

const validationRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/validation",
  component: ValidationPage,
});

const usersRoute = createRoute({
  getParentRoute: () => dashboardLayoutRoute,
  path: "/users",
  component: UsersPage,
});

export const routeTree = rootRoute.addChildren([
  loginRoute,
  dashboardLayoutRoute.addChildren([
    dashboardRoute,
    bulletinsRoute,
    gradesRoute,
    validationRoute,
    usersRoute,
  ]),
]);
