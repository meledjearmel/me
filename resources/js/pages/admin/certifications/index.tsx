import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import CertificationController from '@/actions/App/Http/Controllers/Admin/CertificationController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    CERTIFICATION_KINDS,
    PUBLICATION_STATUSES,
} from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/certifications';
import type { Certification, ListFilters, Paginated } from '@/types';

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

            <div className="flex flex-col gap-6 p-4">
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
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={CertificationController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
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
