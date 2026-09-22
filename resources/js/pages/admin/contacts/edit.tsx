import { Form, Head } from '@inertiajs/react';
import ContactController from '@/actions/App/Http/Controllers/Admin/ContactController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { CONTACT_STATUSES } from '@/lib/admin-options';
import { index as contactsIndex } from '@/routes/admin/contacts';
import type { Contact } from '@/types';

export default function ContactEdit({ contact }: { contact: Contact }) {
    return (
        <>
            <Head title={`Contact de ${contact.name}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Message de contact"
                    description={`${contact.name} — ${contact.email}`}
                />

                <div className="space-y-2 rounded-md border bg-muted/30 p-4 text-sm">
                    {contact.subject && (
                        <p className="font-medium">{contact.subject}</p>
                    )}
                    <p>{contact.message}</p>
                </div>

                <Form
                    {...ContactController.update.form(contact.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Statut *</Label>
                                <NativeSelect
                                    id="status"
                                    name="status"
                                    required
                                    defaultValue={contact.status}
                                >
                                    {CONTACT_STATUSES.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.status} />
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ContactEdit.layout = {
    breadcrumbs: [
        { title: 'Contacts', href: contactsIndex() },
        { title: 'Message', href: '' },
    ],
};
