import { Form, Head } from '@inertiajs/react';
import ExperienceController from '@/actions/App/Http/Controllers/Admin/ExperienceController';
import HighlightsField from '@/components/admin/highlights-field';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as experiencesIndex } from '@/routes/admin/experiences';

export default function ExperienceCreate() {
    return (
        <>
            <Head title="Nouvelle expérience" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading title="Nouvelle expérience" />

                <Form
                    {...ExperienceController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="company">Entreprise *</Label>
                                <Input id="company" name="company" required />
                                <InputError message={errors.company} />
                            </div>

                            <TranslatableField
                                name="role"
                                label="Rôle"
                                required
                                errors={{
                                    fr: errors['role.fr'],
                                    en: errors['role.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="location">Lieu</Label>
                                <Input id="location" name="location" />
                                <InputError message={errors.location} />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">Début *</Label>
                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        required
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">
                                        Fin (vide = poste actuel)
                                    </Label>
                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <HighlightsField errors={errors} />

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

ExperienceCreate.layout = {
    breadcrumbs: [
        { title: 'Expériences', href: experiencesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
