import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import ProfessionalReferenceController from '@/actions/App/Http/Controllers/Admin/ProfessionalReferenceController';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import type { ProfessionalReference } from '@/types';

export default function ProfessionalReferencesIndex({
    professionalReferences,
}: {
    professionalReferences: ProfessionalReference[];
}) {
    return (
        <>
            <Head title="Références professionnelles" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Références professionnelles"
                        description="Visibilité contrôlée par champ, jamais publiques par défaut"
                    />
                    <Button asChild>
                        <Link href={ProfessionalReferenceController.create()}>
                            <Plus /> Nouvelle référence
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom</TableHead>
                            <TableHead>Société</TableHead>
                            <TableHead>Projet</TableHead>
                            <TableHead>Publique</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {professionalReferences.map((reference) => (
                            <TableRow key={reference.id}>
                                <TableCell>{reference.name}</TableCell>
                                <TableCell>{reference.company}</TableCell>
                                <TableCell>
                                    {reference.project?.title.fr}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            reference.is_public
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {reference.is_public
                                            ? 'Publique'
                                            : 'Privée'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={ProfessionalReferenceController.edit(
                                                reference.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={ProfessionalReferenceController.destroy.url(
                                            reference.id,
                                        )}
                                        confirmMessage={`Supprimer la référence "${reference.name}" ?`}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

ProfessionalReferencesIndex.layout = {
    breadcrumbs: [
        { title: 'Références professionnelles', href: referencesIndex() },
    ],
};
