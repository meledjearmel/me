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
import type { Experience } from '@/types';

export default function ExperienceEdit({
    experience,
}: {
    experience: Experience;
}) {
    return (
        <>
            <Head title={`Modifier — ${experience.company}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier l'expérience"
                    description={experience.company}
                />

                <Form
                    {...ExperienceController.update.form(experience.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="company">Entreprise *</Label>
                                <Input
                                    id="company"
                                    name="company"
                                    defaultValue={experience.company}
                                    required
                                />
                                <InputError message={errors.company} />
                            </div>

                            <TranslatableField
                                name="role"
                                label="Rôle"
                                required
                                defaultValue={experience.role}
                                errors={{
                                    fr: errors['role.fr'],
                                    en: errors['role.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="location">Lieu</Label>
                                <Input
                                    id="location"
                                    name="location"
                                    defaultValue={experience.location ?? ''}
                                />
                                <InputError message={errors.location} />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">Début *</Label>
                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        defaultValue={experience.start_date}
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
                                        defaultValue={experience.end_date ?? ''}
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                defaultValue={
                                    experience.description ?? undefined
                                }
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <HighlightsField
                                defaultHighlights={experience.highlights}
                                errors={errors}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">Ordre</Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={experience.sort_order}
                                />
                                <InputError message={errors.sort_order} />
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ExperienceEdit.layout = {
    breadcrumbs: [
        { title: 'Expériences', href: experiencesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
