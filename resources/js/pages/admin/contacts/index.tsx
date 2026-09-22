import { Head, Link } from '@inertiajs/react';
import { SquarePen } from 'lucide-react';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { destroy, edit, index as contactsIndex } from '@/routes/admin/contacts';
import type { Contact } from '@/types';

const STATUS_VARIANT = {
    new: 'default',
    read: 'secondary',
    replied: 'outline',
} as const;

export default function ContactsIndex({ contacts }: { contacts: Contact[] }) {
    return (
        <>
            <Head title="Contacts" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Contacts"
                    description="Messages reçus depuis le formulaire de contact"
                />

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Sujet</TableHead>
                            <TableHead>Statut</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {contacts.map((contact) => (
                            <TableRow key={contact.id}>
                                <TableCell>{contact.name}</TableCell>
                                <TableCell>{contact.email}</TableCell>
                                <TableCell>{contact.subject}</TableCell>
                                <TableCell>
                                    <Badge
                                        variant={STATUS_VARIANT[contact.status]}
                                    >
                                        {contact.status}
                                    </Badge>
                                </TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link href={edit(contact.id)}>
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={destroy.url(contact.id)}
                                        confirmMessage={`Supprimer le message de "${contact.name}" ?`}
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

ContactsIndex.layout = {
    breadcrumbs: [{ title: 'Contacts', href: contactsIndex() }],
};
