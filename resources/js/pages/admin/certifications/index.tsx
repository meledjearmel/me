import { Head, Link } from '@inertiajs/react';
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from '@/components/admin/status-menu';
import { Plus, SquarePen } from 'lucide-react';
import CertificationController from '@/actions/App/Http/Controllers/Admin/CertificationController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import AiDraftBadge from '@/components/admin/ai-draft-badge';
import { Button } from '@/components/ui/button';
import { CERTIFICATION_KINDS, PUBLICATION_STATUSES } from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/certifications';
import type { Certification, ListFilters, Paginated } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

export default function CertificationsIndex({
    certifications,
    filters,
}: {
    certifications: Paginated<Certification>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Certifications" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Certifications"
                        description="Certifications et formations courtes de la page publique (masquée tant qu’aucune n’est publiée)"
                    />
                    <Button asChild>
                        <Link href={CertificationController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={certifications}
                    filters={filters}
                    searchPlaceholder="Intitulé ou organisme…"
                    columns={[
                        { header: 'Intitulé', cell: (row) => row.name.fr },
                        { header: 'Organisme', cell: (row) => row.issuer },
                        {
                            header: 'Type',
                            cell: (row) =>
                                row.kind === 'course'
                                    ? 'Formation'
                                    : 'Certification',
                        },
                        {
                            header: 'Obtenue le',
                            cell: (row) => formatDate(row.issued_on),
                        },
                        {
                            header: 'Statut',
                            fit: true,
                            cell: (row) => (
                                <>
                                    <StatusMenu
                                        type="certification"
                                        id={row.id}
                                        status={row.status}
                                        options={PUBLICATION_STATUSES}
                                        variants={PUBLICATION_VARIANTS}
                                    />
                                    <AiDraftBadge
                                        draftedBy={row.drafted_by}
                                        status={row.status}
                                    />
                                </>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="kind"
                                value={filters.kind}
                                label="Type"
                                options={[...CERTIFICATION_KINDS]}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[...PUBLICATION_STATUSES]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={CertificationController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={CertificationController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer « ${row.name.fr} » ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

CertificationsIndex.layout = {
    breadcrumbs: [{ title: 'Certifications', href: pageIndex() }],
};
