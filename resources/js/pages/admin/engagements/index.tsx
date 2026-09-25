import { Head, Link, router } from '@inertiajs/react';
import { Check, Eye, RotateCcw } from 'lucide-react';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    destroy,
    index as engagementsIndex,
    show,
    update,
} from '@/routes/admin/engagements';
import type { Engagement, ListFilters, Paginated } from '@/types';

const TYPE_LABEL = { freelance: 'Freelance', hiring: 'Embauche' } as const;

export default function EngagementsIndex({
    engagements,
    filters,
}: {
    engagements: Paginated<Engagement>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Collaborations" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Collaborations"
                    description="Demandes de projet freelance et de recrutement reçues depuis le site"
                />

                <ResourceList
                    paginator={engagements}
                    filters={filters}
                    searchPlaceholder="Nom, email, société, sujet…"
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="type"
                                value={filters.type}
                                label="Type"
                                options={[
                                    { value: 'freelance', label: 'Freelance' },
                                    { value: 'hiring', label: 'Embauche' },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: 'new', label: 'Nouveau' },
                                    { value: 'handled', label: 'Traité' },
                                ]}
                            />
                        </>
                    )}
                    columns={[
                        {
                            header: 'Type',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.type === 'hiring'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {TYPE_LABEL[row.type]}
                                </Badge>
                            ),
                        },
                        {
                            header: 'Contact',
                            cell: (row) => (
                                <>
                                    <div className="font-medium">
                                        {row.name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {row.email}
                                        {row.company && ` · ${row.company}`}
                                    </div>
                                </>
                            ),
                        },
                        {
                            header: 'Poste / projet',
                            cell: (row) => row.subject,
                        },
                        {
                            header: 'Détails',
                            className: 'max-w-xs text-sm text-muted-foreground',
                            cell: (row) => (
                                <>
                                    {[
                                        row.job_profile?.label.fr,
                                        row.contract,
                                        row.budget_label,
                                        row.timeline,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                    {row.message && (
                                        <p className="mt-1 line-clamp-2">
                                            {row.message}
                                        </p>
                                    )}
                                </>
                            ),
                        },
                        {
                            header: 'CV',
                            cell: (row) =>
                                row.type === 'hiring' &&
                                (row.cv_sent_at ? 'Envoyé' : 'Non envoyé'),
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.status === 'new'
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    {row.status === 'new'
                                        ? 'Nouveau'
                                        : 'Traité'}
                                </Badge>
                            ),
                        },
                    ]}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={show(row.id)} aria-label="Voir">
                                    <Eye />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                title={
                                    row.status === 'new'
                                        ? 'Marquer comme traité'
                                        : 'Remettre en nouveau'
                                }
                                aria-label={
                                    row.status === 'new'
                                        ? 'Marquer comme traité'
                                        : 'Remettre en nouveau'
                                }
                                onClick={() =>
                                    router.patch(
                                        update.url(row.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {row.status === 'new' ? (
                                    <Check />
                                ) : (
                                    <RotateCcw />
                                )}
                            </Button>
                            <DeleteButton
                                href={destroy.url(row.id)}
                                confirmMessage={`Supprimer la demande de "${row.name}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

EngagementsIndex.layout = {
    breadcrumbs: [{ title: 'Collaborations', href: engagementsIndex() }],
};
