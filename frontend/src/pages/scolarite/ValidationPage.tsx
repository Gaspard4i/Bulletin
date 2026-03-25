import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import api from "@/lib/api";
import type { Bulletin } from "@/types";
import { BulletinStatus } from "@/types";

export function ValidationPage() {
  const queryClient = useQueryClient();

  const { data: bulletins, isLoading } = useQuery({
    queryKey: ["bulletins", "pending"],
    queryFn: async () => {
      const { data } = await api.get<Bulletin[]>("/bulletins", {
        params: { status: BulletinStatus.PENDING_VALIDATION },
      });
      return data;
    },
  });

  const validateMutation = useMutation({
    mutationFn: async (bulletinId: string) => {
      await api.patch(`/bulletins/${bulletinId}/validate`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["bulletins"] });
    },
  });

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Validation des bulletins</h1>

      {isLoading ? (
        <p className="text-muted-foreground">Chargement...</p>
      ) : bulletins && bulletins.length > 0 ? (
        <div className="grid gap-4">
          {bulletins.map((bulletin) => (
            <Card key={bulletin.id}>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  <span>
                    {bulletin.student
                      ? `${bulletin.student.firstName} ${bulletin.student.lastName}`
                      : bulletin.studentId}
                  </span>
                  <span className="text-sm font-normal text-muted-foreground">
                    {bulletin.semester} — {bulletin.academicYear}
                  </span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">
                      {bulletin.grades.length} matière(s) • Moyenne :{" "}
                      {bulletin.averageGrade?.toFixed(2) ?? "N/A"}/20
                    </p>
                    {bulletin.comment && (
                      <p className="text-sm italic">{bulletin.comment}</p>
                    )}
                  </div>
                  <div className="flex gap-2">
                    <Button
                      variant="outline"
                      onClick={() => validateMutation.mutate(bulletin.id)}
                      disabled={validateMutation.isPending}
                    >
                      Valider
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <Card>
          <CardContent className="py-8 text-center text-muted-foreground">
            Aucun bulletin en attente de validation.
          </CardContent>
        </Card>
      )}
    </div>
  );
}
