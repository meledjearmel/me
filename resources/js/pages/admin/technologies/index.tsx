import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
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
import { index as technologiesIndex } from '@/routes/admin/technologies';
import type { Technology } from '@/types';

export default function TechnologiesIndex({
    technologies,
}: {
    technologies: Technology[];
}) {
    return (
        <>
            <Head title="Technologies" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Technologies"
                        description="Stack technique affichée sur les projets et le CV"
                    />
                    <Button asChild>
                        <Link href={TechnologyController.create()}>
                            <Plus /> Nouvelle technologie
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom</TableHead>
                            <TableHead>Catégorie</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {technologies.map((technology) => (
                            <TableRow key={technology.id}>
                                <TableCell>{technology.name}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {technology.category}
                                    </Badge>
                                </TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={TechnologyController.edit(
                                                technology.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={TechnologyController.destroy.url(
                                            technology.id,
                                        )}
                                        confirmMessage={`Supprimer la technologie "${technology.name}" ?`}
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

TechnologiesIndex.layout = {
    breadcrumbs: [{ title: 'Technologies', href: technologiesIndex() }],
};
