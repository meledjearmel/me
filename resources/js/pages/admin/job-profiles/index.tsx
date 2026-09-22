import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import JobProfileController from '@/actions/App/Http/Controllers/Admin/JobProfileController';
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
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';
import type { JobProfile } from '@/types';

export default function JobProfilesIndex({
    jobProfiles,
}: {
    jobProfiles: JobProfile[];
}) {
    return (
        <>
            <Head title="Profils métier" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Profils métier"
                        description="Pilotent les projets affichés dans chaque CV généré"
                    />
                    <Button asChild>
                        <Link href={JobProfileController.create()}>
                            <Plus /> Nouveau profil
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Clé</TableHead>
                            <TableHead>Libellé (FR)</TableHead>
                            <TableHead>Ordre</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {jobProfiles.map((jobProfile) => (
                            <TableRow key={jobProfile.id}>
                                <TableCell className="font-mono text-xs">
                                    {jobProfile.key}
                                </TableCell>
                                <TableCell>{jobProfile.label.fr}</TableCell>
                                <TableCell>{jobProfile.sort_order}</TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={JobProfileController.edit(
                                                jobProfile.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={JobProfileController.destroy.url(
                                            jobProfile.id,
                                        )}
                                        confirmMessage={`Supprimer le profil "${jobProfile.label.fr}" ?`}
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

JobProfilesIndex.layout = {
    breadcrumbs: [{ title: 'Profils métier', href: jobProfilesIndex() }],
};
