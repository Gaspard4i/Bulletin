import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import api from "@/lib/api";
import type { Grade } from "@/types";

export function GradesPage() {
  const [selectedSubject, setSelectedSubject] = useState<string>("");

  const { data: grades, isLoading } = useQuery({
    queryKey: ["grades", selectedSubject],
    queryFn: async () => {
      const params = selectedSubject ? { subject: selectedSubject } : {};
      const { data } = await api.get<Grade[]>("/grades", { params });
      return data;
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Saisie des notes</h1>
        <Button>Nouvelle note</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtrer par matière</CardTitle>
        </CardHeader>
        <CardContent>
          <Input
            placeholder="Rechercher une matière..."
            value={selectedSubject}
            onChange={(e) => setSelectedSubject(e.target.value)}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Notes saisies</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted-foreground">Chargement...</p>
          ) : grades && grades.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b">
                    <th className="py-3 text-left font-medium">Étudiant</th>
                    <th className="py-3 text-left font-medium">Matière</th>
                    <th className="py-3 text-left font-medium">Note</th>
                    <th className="py-3 text-left font-medium">Coeff.</th>
                    <th className="py-3 text-left font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {grades.map((grade) => (
                    <tr key={grade.id} className="border-b">
                      <td className="py-3">
                        {grade.student
                          ? `${grade.student.firstName} ${grade.student.lastName}`
                          : grade.studentId}
                      </td>
                      <td className="py-3">
                        {grade.subject?.name ?? grade.subjectId}
                      </td>
                      <td className="py-3 font-mono">{grade.value}/20</td>
                      <td className="py-3">{grade.coefficient}</td>
                      <td className="py-3">
                        <Button variant="outline" size="sm">
                          Modifier
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p className="text-muted-foreground">Aucune note saisie.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
