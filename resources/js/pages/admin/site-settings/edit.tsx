import { Form, Head } from '@inertiajs/react';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import FormSelect from '@/components/admin/form-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { edit as pageEdit } from '@/routes/admin/site-settings';

export default function SiteSettingsEdit({
    settings,
}: {
    settings: { contact_opens_drawer: boolean };
}) {
    return (
        <>
            <Head title="Réglages du site" />

            <div className="flex max-w-3xl flex-col gap-6 p-4">
                <Heading
                    title="Réglages du site"
                    description="Comment le site public se comporte pour les visiteurs"
                />

                <Form
                    {...SiteSettingController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.contact_opens_drawer}>
                                <FieldLabel htmlFor="contact_opens_drawer">
                                    Bouton « Contact » du menu
                                </FieldLabel>
                                <FormSelect
                                    id="contact_opens_drawer"
                                    name="contact_opens_drawer"
                                    defaultValue={
                                        settings.contact_opens_drawer
                                            ? '1'
                                            : '0'
                                    }
                                >
                                    <option value="1">
                                        Ouvre un tiroir latéral avec le
                                        formulaire, sans quitter la page
                                    </option>
                                    <option value="0">
                                        Mène à la page Contact
                                    </option>
                                </FormSelect>
                                <FieldDescription>
                                    S'applique aussi au grand titre du pied de
                                    page.
                                </FieldDescription>
                                <FieldError>
                                    {errors.contact_opens_drawer}
                                </FieldError>
                            </Field>

                            <Button disabled={processing} className="w-fit">
                                Enregistrer
                            </Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

SiteSettingsEdit.layout = {
    breadcrumbs: [{ title: 'Réglages du site', href: pageEdit() }],
};
