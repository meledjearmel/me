import { Badge } from '@/components/ui/badge';
import ShowPage, { formatDate } from '@/components/admin/show-page';
import { edit, index as contactsIndex } from '@/routes/admin/contacts';
import type { Contact } from '@/types';

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

export default function ContactShow({ contact: row }: { contact: Contact }) {
    return (
        <ShowPage
            title={`Message de ${row.name}`}
            description={row.email}
            badge={
                <Badge variant={STATUS_VARIANT[row.status]}>
                    {STATUS_LABEL[row.status]}
                </Badge>
            }
            backHref={contactsIndex()}
            backLabel="Contacts"
            editHref={edit(row.id)}
            sections={[
                {
                    title: 'Expéditeur',
                    items: [
                        { label: 'Nom', value: row.name },
                        {
                            label: 'Email',
                            value: (
                                <a
                                    href={`mailto:${row.email}`}
                                    className="underline"
                                >
                                    {row.email}
                                </a>
                            ),
                        },
                        { label: 'Sujet', value: row.subject },
                        { label: 'Reçu le', value: formatDate(row.created_at) },
                    ],
                },
                {
                    title: 'Message',
                    items: [
                        { label: 'Contenu', value: row.message, wide: true },
                    ],
                },
            ]}
        />
    );
}

ContactShow.layout = {
    breadcrumbs: [
        { title: 'Contacts', href: contactsIndex() },
        { title: 'Détail', href: '' },
    ],
};
