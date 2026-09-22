import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import ExperienceController from '@/actions/App/Http/Controllers/Admin/ExperienceController';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as experiencesIndex } from '@/routes/admin/experiences';
import type { Experience } from '@/types';

export default function ExperiencesIndex({
    experiences,
}: {
    experiences: Experience[];
}) {
    return (
        <>
            <Head title="Expériences" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Expériences" />
                    <Button asChild>
                        <Link href={ExperienceController.create()}>
                            <Plus /> Nouvelle expérience
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Entreprise</TableHead>
                            <TableHead>Rôle (FR)</TableHead>
                            <TableHead>Période</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {experiences.map((experience) => (
                            <TableRow key={experience.id}>
                                <TableCell>{experience.company}</TableCell>
                                <TableCell>{experience.role.fr}</TableCell>
                                <TableCell>
                                    {experience.start_date} —{' '}
                                    {experience.end_date ?? 'en cours'}
                                </TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={ExperienceController.edit(
                                                experience.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={ExperienceController.destroy.url(
                                            experience.id,
                                        )}
                                        confirmMessage={`Supprimer l'expérience "${experience.company}" ?`}
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

ExperiencesIndex.layout = {
    breadcrumbs: [{ title: 'Expériences', href: experiencesIndex() }],
};
