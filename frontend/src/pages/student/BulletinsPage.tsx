import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { FileText, Download, Eye } from "lucide-react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import api from "@/lib/api";
import type { Bulletin } from "@/types";
import { BulletinStatus } from "@/types";

const statusLabels: Record<BulletinStatus, string> = {
  [BulletinStatus.DRAFT]: "Brouillon",
  [BulletinStatus.PENDING_VALIDATION]: "En attente",
  [BulletinStatus.VALIDATED]: "Validé",
  [BulletinStatus.PUBLISHED]: "Publié",
};

const statusStyles: Record<BulletinStatus, string> = {
  [BulletinStatus.DRAFT]: "bg-gray-100 text-gray-800",
  [BulletinStatus.PENDING_VALIDATION]: "bg-amber-100 text-amber-800",
  [BulletinStatus.VALIDATED]: "bg-blue-100 text-blue-800",
  [BulletinStatus.PUBLISHED]: "bg-green-100 text-green-800",
};

export function BulletinsPage() {
  const [selectedBulletin, setSelectedBulletin] = useState<Bulletin | null>(null);

  const { data: bulletins, isLoading } = useQuery({
    queryKey: ["student", "bulletins"],
    queryFn: async () => {
      const { data } = await api.get<Bulletin[]>("/bulletins/me");
      return data;
    },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Mes bulletins</h1>
        <p className="text-muted-foreground">
          Consultez et téléchargez vos bulletins de notes
        </p>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <p className="text-muted-foreground">Chargement...</p>
        </div>
      ) : !bulletins?.length ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <FileText className="mb-4 h-12 w-12 text-muted-foreground" />
            <p className="text-lg font-medium">Aucun bulletin disponible</p>
            <p className="text-sm text-muted-foreground">
              Vos bulletins apparaîtront ici une fois publiés
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {bulletins.map((bulletin) => (
            <Card key={bulletin.id}>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-lg">
                    {bulletin.semester}
                  </CardTitle>
                  <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${statusStyles[bulletin.status]}`}
                  >
                    {statusLabels[bulletin.status]}
                  </span>
                </div>
                <CardDescription>{bulletin.academicYear}</CardDescription>
              </CardHeader>
              <CardContent>
                {bulletin.averageGrade !== undefined && (
                  <p className="mb-4 text-2xl font-bold">
                    {bulletin.averageGrade.toFixed(2)}/20
                  </p>
                )}
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setSelectedBulletin(bulletin)}
                  >
                    <Eye className="mr-1 h-4 w-4" />
                    Voir
                  </Button>
                  {bulletin.status === BulletinStatus.PUBLISHED && (
                    <Button variant="outline" size="sm">
                      <Download className="mr-1 h-4 w-4" />
                      PDF
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {selectedBulletin && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>
                Détail - {selectedBulletin.semester} {selectedBulletin.academicYear}
              </CardTitle>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setSelectedBulletin(null)}
              >
                Fermer
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {selectedBulletin.grades.length === 0 ? (
              <p className="text-muted-foreground">Aucune note enregistrée</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="pb-2 text-left font-medium">Matière</th>
                      <th className="pb-2 text-right font-medium">Note</th>
                      <th className="pb-2 text-right font-medium">Coef.</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selectedBulletin.grades.map((grade) => (
                      <tr key={grade.id} className="border-b last:border-0">
                        <td className="py-2">{grade.subject?.name ?? "N/A"}</td>
                        <td className="py-2 text-right">{grade.value}/20</td>
                        <td className="py-2 text-right">{grade.coefficient}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            {selectedBulletin.comment && (
              <div className="mt-4 rounded-md bg-muted p-3">
                <p className="text-sm font-medium">Appréciation</p>
                <p className="text-sm text-muted-foreground">
                  {selectedBulletin.comment}
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
