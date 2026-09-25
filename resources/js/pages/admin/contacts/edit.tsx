import { Form, Head } from '@inertiajs/react';
import ContactController from '@/actions/App/Http/Controllers/Admin/ContactController';
import Heading from '@/components/heading';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import FormSelect from '@/components/admin/form-select';
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
                        <FieldGroup>
                            <Field data-invalid={!!errors.status}>
                                <FieldLabel htmlFor="status">
                                    Statut *
                                </FieldLabel>
                                <FormSelect
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
                                </FormSelect>
                                <FieldError>{errors.status}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
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
