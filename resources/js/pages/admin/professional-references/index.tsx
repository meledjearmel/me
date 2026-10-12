import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/professional-references';
import ProfessionalReferenceController from '@/actions/App/Http/Controllers/Admin/ProfessionalReferenceController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, ProfessionalReference } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

export default function ProfessionalReferencesIndex({
    professionalReferences,
    filters,
}: {
    professionalReferences: Paginated<ProfessionalReference>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Références professionnelles" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Références professionnelles"
                        description="Visibilité contrôlée par champ, jamais publiques par défaut"
                    />
                    <Button asChild>
                        <Link href={ProfessionalReferenceController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle référence
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={professionalReferences}
                    filters={filters}
                    searchPlaceholder="Nom, poste, société, email…"
                    columns={[
                        { header: 'Nom', cell: (row) => row.name },
                        { header: 'Société', cell: (row) => row.company },
                        {
                            header: 'Projet',
                            cell: (row) => row.project?.title.fr,
                        },
                        {
                            header: 'Visibilité',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.is_public ? 'default' : 'secondary'
                                    }
                                >
                                    {row.is_public ? 'Sur le CV' : 'Privée'}
                                </Badge>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="is_public"
                                value={filters.is_public}
                                label="Visibilité"
                                options={[
                                    { value: '1', label: 'Sur le CV' },
                                    { value: '0', label: 'Privée' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={ProfessionalReferenceController.show(
                                            row.id,
                                        )}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={ProfessionalReferenceController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={ProfessionalReferenceController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer la référence "${row.name}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ProfessionalReferencesIndex.layout = {
    breadcrumbs: [{ title: 'Références', href: pageIndex() }],
};
