import { Form, Head } from '@inertiajs/react';
import JobProfileController from '@/actions/App/Http/Controllers/Admin/JobProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';

export default function JobProfileCreate() {
    return (
        <>
            <Head title="Nouveau profil métier" />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title="Nouveau profil métier"
                    description="Ex. Ingénieur full-stack, Chargé IT, Lead Tech..."
                />

                <Form
                    {...JobProfileController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="key">Clé *</Label>
                                <Input id="key" name="key" required />
                                <InputError message={errors.key} />
                            </div>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
                                errors={{
                                    fr: errors['label.fr'],
                                    en: errors['label.en'],
                                }}
                            />

                            <TranslatableField
                                name="description"
                                label="Description (publique)"
                                textarea
                                required
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <TranslatableField
                                name="cv_description"
                                label="Description CV (jamais publique)"
                                textarea
                                required
                                errors={{
                                    fr: errors['cv_description.fr'],
                                    en: errors['cv_description.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">Ordre</Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={0}
                                />
                                <InputError message={errors.sort_order} />
                            </div>

                            <Button disabled={processing}>Créer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

JobProfileCreate.layout = {
    breadcrumbs: [
        { title: 'Profils métier', href: jobProfilesIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
