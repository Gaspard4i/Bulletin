import { useEffect } from "react";
import { useNavigate, useSearch } from "@tanstack/react-router";

export function AuthCallbackPage() {
  const navigate = useNavigate();
  const search = useSearch({ from: "/auth/callback" });

  useEffect(() => {
    const token = (search as Record<string, string>).token;

    if (token) {
      localStorage.setItem("access_token", token);
      navigate({ to: "/" });
    } else {
      navigate({ to: "/login" });
    }
  }, [navigate, search]);

  return (
    <div className="flex min-h-screen items-center justify-center">
      <p className="text-muted-foreground">Connexion en cours...</p>
    </div>
  );
}
