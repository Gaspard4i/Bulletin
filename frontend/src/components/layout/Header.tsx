import { LogOut, User as UserIcon } from "lucide-react";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { Role } from "@/types";

const roleBadgeStyles: Record<Role, string> = {
  [Role.STUDENT]: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
  [Role.TEACHER]: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
  [Role.SCOLARITE]: "bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200",
  [Role.ADMIN]: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
};

const roleLabels: Record<Role, string> = {
  [Role.STUDENT]: "Étudiant",
  [Role.TEACHER]: "Enseignant",
  [Role.SCOLARITE]: "Scolarité",
  [Role.ADMIN]: "Administrateur",
};

export function Header() {
  const { user, logout } = useAuth();

  return (
    <header className="flex h-16 items-center justify-between border-b bg-card px-6">
      <div />

      <div className="flex items-center gap-4">
        {user && (
          <>
            <span
              className={cn(
                "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
                roleBadgeStyles[user.role],
              )}
            >
              {roleLabels[user.role]}
            </span>

            <div className="flex items-center gap-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                {user.avatarUrl ? (
                  <img
                    src={user.avatarUrl}
                    alt={`${user.firstName} ${user.lastName}`}
                    className="h-8 w-8 rounded-full object-cover"
                  />
                ) : (
                  <UserIcon className="h-4 w-4 text-muted-foreground" />
                )}
              </div>
              <span className="text-sm font-medium">
                {user.firstName} {user.lastName}
              </span>
            </div>

            <Button variant="ghost" size="icon" onClick={logout} title="Déconnexion">
              <LogOut className="h-4 w-4" />
            </Button>
          </>
        )}
      </div>
    </header>
  );
}
