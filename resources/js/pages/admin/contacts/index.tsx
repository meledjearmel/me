import { Head, Link } from '@inertiajs/react';
import { Eye, SquarePen } from 'lucide-react';
import {
    destroy,
    edit,
    show,
    index as pageIndex,
} from '@/routes/admin/contacts';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, Contact } from '@/types';

const STATUS_VARIANT = {
    new: 'default',
    read: 'secondary',
    replied: 'outline',
} as const;

const STATUS_LABEL = {
    new: 'Nouveau',
    read: 'Lu',
    replied: 'Répondu',
} as const;

export default function ContactsIndex({
    contacts,
    filters,
}: {
    contacts: Paginated<Contact>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Contacts" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Contacts"
                        description="Messages reçus depuis le formulaire de contact"
                    />
                </div>

                <ResourceList
                    paginator={contacts}
                    filters={filters}
                    searchPlaceholder="Nom, email, sujet, message…"
                    columns={[
                        { header: 'Nom', cell: (row) => row.name },
                        { header: 'Email', cell: (row) => row.email },
                        { header: 'Sujet', cell: (row) => row.subject },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge variant={STATUS_VARIANT[row.status]}>
                                    {STATUS_LABEL[row.status]}
                                </Badge>
                            ),
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
                                    { value: 'new', label: 'Nouveau' },
                                    { value: 'read', label: 'Lu' },
                                    { value: 'replied', label: 'Répondu' },
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
                                confirmMessage={`Supprimer le message de "${row.name}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ContactsIndex.layout = {
    breadcrumbs: [{ title: 'Contacts', href: pageIndex() }],
};
