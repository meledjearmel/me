import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/skills';
import SkillController from '@/actions/App/Http/Controllers/Admin/SkillController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Domain, ListFilters, Paginated, Skill } from '@/types';

export default function SkillsIndex({
    skills,
    filters,
    domains,
}: {
    skills: Paginated<Skill>;
    filters: ListFilters;
    domains: Pick<Domain, 'id' | 'label'>[];
}) {
    return (
        <>
            <Head title="Compétences" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Compétences"
                        description="Regroupées par domaine"
                    />
                    <Button asChild>
                        <Link href={SkillController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                            compétence
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={skills}
                    filters={filters}
                    searchPlaceholder="Nom de la compétence…"
                    columns={[
                        { header: 'Nom (FR)', cell: (row) => row.name.fr },
                        {
                            header: 'Domaine',
                            cell: (row) => row.domain?.label.fr,
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.status === 'published'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {row.status === 'published'
                                        ? 'Publié'
                                        : 'Brouillon'}
                                </Badge>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="domain_id"
                                value={filters.domain_id}
                                label="Domaine"
                                options={domains.map((domain) => ({
                                    value: String(domain.id),
                                    label: domain.label.fr,
                                }))}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: 'published', label: 'Publié' },
                                    { value: 'draft', label: 'Brouillon' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={SkillController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={SkillController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={SkillController.destroy.url(row.id)}
                                confirmMessage={`Supprimer la compétence "${row.name.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

SkillsIndex.layout = {
    breadcrumbs: [{ title: 'Compétences', href: pageIndex() }],
};
