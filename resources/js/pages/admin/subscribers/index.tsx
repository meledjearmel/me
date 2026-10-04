import { Head } from '@inertiajs/react';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { destroy, index as pageIndex } from '@/routes/admin/subscribers';
import type { ListFilters, Paginated, Subscriber } from '@/types';

function statusOf(subscriber: Subscriber): {
    label: string;
    variant: 'default' | 'secondary' | 'outline';
} {
    if (subscriber.unsubscribed_at) {
        return { label: 'Désinscrit', variant: 'outline' };
    }

    return subscriber.confirmed_at
        ? { label: 'Actif', variant: 'default' }
        : { label: 'En attente', variant: 'secondary' };
}

export default function SubscribersIndex({
    subscribers,
    filters,
    summary,
}: {
    subscribers: Paginated<Subscriber>;
    filters: ListFilters;
    summary: { active: number; pending: number; unsubscribed: number };
}) {
    return (
        <>
            <Head title="Newsletter" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Newsletter"
                    description="Les abonnés qui reçoivent chaque nouvel article du blog"
                />

                <dl className="grid gap-3 sm:grid-cols-3">
                    {[
                        ['Abonnés actifs', summary.active],
                        ['En attente de confirmation', summary.pending],
                        ['Désinscrits', summary.unsubscribed],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-lg border p-4">
                            <dt className="text-sm text-muted-foreground">
                                {label}
                            </dt>
                            <dd className="text-2xl font-semibold">{value}</dd>
                        </div>
                    ))}
                </dl>

                <ResourceList
                    paginator={subscribers}
                    filters={filters}
                    searchPlaceholder="Email…"
                    columns={[
                        { header: 'Email', cell: (row) => row.email },
                        {
                            header: 'État',
                            cell: (row) => {
                                const status = statusOf(row);

                                return (
                                    <Badge variant={status.variant}>
                                        {status.label}
                                    </Badge>
                                );
                            },
                        },
                        {
                            header: 'Langue',
                            cell: (row) => row.locale.toUpperCase(),
                        },
                        {
                            header: 'Inscription',
                            cell: (row) => formatDate(row.created_at),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="État"
                                options={[
                                    { value: 'active', label: 'Actif' },
                                    { value: 'pending', label: 'En attente' },
                                    {
                                        value: 'unsubscribed',
                                        label: 'Désinscrit',
                                    },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="locale"
                                value={filters.locale}
                                label="Langue"
                                options={[
                                    { value: 'fr', label: 'Français' },
                                    { value: 'en', label: 'Anglais' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <DeleteButton
                            href={destroy.url(row.id)}
                            confirmMessage="Supprimer cet abonné ?"
                        />
                    )}
                />
            </div>
        </>
    );
}

SubscribersIndex.layout = {
    breadcrumbs: [{ title: 'Newsletter', href: pageIndex() }],
};
