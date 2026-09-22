import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import SkillController from '@/actions/App/Http/Controllers/Admin/SkillController';
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
import { index as skillsIndex } from '@/routes/admin/skills';
import type { Skill } from '@/types';

export default function SkillsIndex({ skills }: { skills: Skill[] }) {
    return (
        <>
            <Head title="Compétences" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Compétences"
                        description="Regroupées par domaine"
                    />
                    <Button asChild>
                        <Link href={SkillController.create()}>
                            <Plus /> Nouvelle compétence
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom (FR)</TableHead>
                            <TableHead>Domaine</TableHead>
                            <TableHead>Ordre</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {skills.map((skill) => (
                            <TableRow key={skill.id}>
                                <TableCell>{skill.name.fr}</TableCell>
                                <TableCell>{skill.domain?.label.fr}</TableCell>
                                <TableCell>{skill.sort_order}</TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={SkillController.edit(
                                                skill.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={SkillController.destroy.url(
                                            skill.id,
                                        )}
                                        confirmMessage={`Supprimer la compétence "${skill.name.fr}" ?`}
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

SkillsIndex.layout = {
    breadcrumbs: [{ title: 'Compétences', href: skillsIndex() }],
};
