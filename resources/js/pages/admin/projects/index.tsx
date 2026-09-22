import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
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
import { index as projectsIndex } from '@/routes/admin/projects';
import type { Project } from '@/types';

export default function ProjectsIndex({ projects }: { projects: Project[] }) {
    return (
        <>
            <Head title="Projets" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Projets" />
                    <Button asChild>
                        <Link href={ProjectController.create()}>
                            <Plus /> Nouveau projet
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Titre (FR)</TableHead>
                            <TableHead>Statut</TableHead>
                            <TableHead>Mis en avant</TableHead>
                            <TableHead>Ordre</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {projects.map((project) => (
                            <TableRow key={project.id}>
                                <TableCell>{project.title.fr}</TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            project.status === 'published'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {project.status}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {project.is_featured ? 'Oui' : '—'}
                                </TableCell>
                                <TableCell>{project.sort_order}</TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={ProjectController.edit(
                                                project.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={ProjectController.destroy.url(
                                            project.id,
                                        )}
                                        confirmMessage={`Supprimer le projet "${project.title.fr}" ?`}
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

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projets', href: projectsIndex() }],
};
