import { Head, Link } from '@inertiajs/react';
import { Eye, SquarePen } from 'lucide-react';
import {
    destroy,
    edit,
    show,
    index as pageIndex,
} from '@/routes/admin/testimonials';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, Testimonial } from '@/types';

const STATUS_VARIANT = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive',
} as const;

const STATUS_LABEL = {
    pending: 'En attente',
    approved: 'Approuvé',
    rejected: 'Refusé',
} as const;

export default function TestimonialsIndex({
    testimonials,
    filters,
}: {
    testimonials: Paginated<Testimonial>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Avis" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Avis"
                        description="Modération des avis soumis publiquement"
                    />
                </div>

                <ResourceList
                    paginator={testimonials}
                    filters={filters}
                    searchPlaceholder="Auteur, email, contenu…"
                    columns={[
                        { header: 'Auteur', cell: (row) => row.author_name },
                        {
                            header: 'Projet',
                            cell: (row) => row.project?.title.fr ?? 'Général',
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge variant={STATUS_VARIANT[row.status]}>
                                    {STATUS_LABEL[row.status]}
                                </Badge>
                            ),
                        },
                        {
                            header: 'À la une',
                            cell: (row) => (row.is_featured ? '★' : ''),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: 'pending', label: 'En attente' },
                                    { value: 'approved', label: 'Approuvé' },
                                    { value: 'rejected', label: 'Refusé' },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_featured"
                                value={filters.is_featured}
                                label="À la une"
                                options={[
                                    { value: '1', label: 'À la une' },
                                    { value: '0', label: 'Pas à la une' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={show(row.id)} aria-label="Voir">
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={edit(row.id)} aria-label="Modifier">
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={destroy.url(row.id)}
                                confirmMessage={`Supprimer l'avis de "${row.author_name}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

TestimonialsIndex.layout = {
    breadcrumbs: [{ title: 'Avis', href: pageIndex() }],
};
