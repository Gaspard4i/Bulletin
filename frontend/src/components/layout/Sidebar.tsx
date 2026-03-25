import { useCallback } from "react";
import { Link, useMatchRoute } from "@tanstack/react-router";
import {
  LayoutDashboard,
  FileText,
  ClipboardEdit,
  CheckSquare,
  Users,
  Settings,
  GraduationCap,
  type LucideIcon,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuth } from "@/lib/auth";
import { Role } from "@/types";

interface NavItem {
  label: string;
  href: string;
  icon: LucideIcon;
  roles: Role[];
}

const navItems: NavItem[] = [
  {
    label: "Tableau de bord",
    href: "/dashboard",
    icon: LayoutDashboard,
    roles: [Role.STUDENT, Role.TEACHER, Role.SCOLARITE, Role.ADMIN],
  },
  {
    label: "Mes bulletins",
    href: "/student/bulletins",
    icon: FileText,
    roles: [Role.STUDENT],
  },
  {
    label: "Saisie des notes",
    href: "/teacher/grades",
    icon: ClipboardEdit,
    roles: [Role.TEACHER],
  },
  {
    label: "Validation",
    href: "/scolarite/validation",
    icon: CheckSquare,
    roles: [Role.SCOLARITE],
  },
  {
    label: "Utilisateurs",
    href: "/admin/users",
    icon: Users,
    roles: [Role.ADMIN],
  },
  {
    label: "Paramètres",
    href: "/admin/settings",
    icon: Settings,
    roles: [Role.ADMIN],
  },
];

export function Sidebar() {
  const { user, hasRole } = useAuth();
  const matchRoute = useMatchRoute();

  const filteredItems = navItems.filter((item) => hasRole(item.roles));

  const isActive = useCallback(
    (href: string) => {
      return !!matchRoute({ to: href, fuzzy: true });
    },
    [matchRoute],
  );

  return (
    <aside className="flex h-full w-64 flex-col border-r bg-card">
      <div className="flex h-16 items-center gap-2 border-b px-6">
        <GraduationCap className="h-6 w-6 text-primary" />
        <span className="text-lg font-bold">Bulletin</span>
      </div>

      <nav className="flex-1 space-y-1 p-4">
        {filteredItems.map((item) => {
          const Icon = item.icon;
          const active = isActive(item.href);

          return (
            <Link
              key={item.href}
              to={item.href}
              className={cn(
                "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors",
                active
                  ? "bg-primary text-primary-foreground"
                  : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
              )}
            >
              <Icon className="h-4 w-4" />
              {item.label}
            </Link>
          );
        })}
      </nav>

      {user && (
        <div className="border-t p-4">
          <p className="truncate text-sm font-medium">
            {user.firstName} {user.lastName}
          </p>
          <p className="truncate text-xs text-muted-foreground">{user.email}</p>
        </div>
      )}
    </aside>
  );
}
