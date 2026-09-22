import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import EducationController from '@/actions/App/Http/Controllers/Admin/EducationController';
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
import { index as educationsIndex } from '@/routes/admin/educations';
import type { Education } from '@/types';

export default function EducationsIndex({
    educations,
}: {
    educations: Education[];
}) {
    return (
        <>
            <Head title="Formation" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Formation" />
                    <Button asChild>
                        <Link href={EducationController.create()}>
                            <Plus /> Nouvelle formation
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Établissement</TableHead>
                            <TableHead>Diplôme (FR)</TableHead>
                            <TableHead>Période</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {educations.map((education) => (
                            <TableRow key={education.id}>
                                <TableCell>{education.institution}</TableCell>
                                <TableCell>{education.degree.fr}</TableCell>
                                <TableCell>
                                    {education.start_date} —{' '}
                                    {education.end_date ?? 'en cours'}
                                </TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={EducationController.edit(
                                                education.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={EducationController.destroy.url(
                                            education.id,
                                        )}
                                        confirmMessage={`Supprimer la formation "${education.degree.fr}" ?`}
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

EducationsIndex.layout = {
    breadcrumbs: [{ title: 'Formation', href: educationsIndex() }],
};
