import { Form, Head } from '@inertiajs/react';
import EducationController from '@/actions/App/Http/Controllers/Admin/EducationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as educationsIndex } from '@/routes/admin/educations';
import type { Education } from '@/types';

export default function EducationEdit({ education }: { education: Education }) {
    return (
        <>
            <Head title={`Modifier — ${education.degree.fr}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la formation"
                    description={education.degree.fr}
                />

                <Form
                    {...EducationController.update.form(education.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="institution">
                                    Établissement *
                                </Label>
                                <Input
                                    id="institution"
                                    name="institution"
                                    defaultValue={education.institution}
                                    required
                                />
                                <InputError message={errors.institution} />
                            </div>

                            <TranslatableField
                                name="degree"
                                label="Diplôme"
                                required
                                defaultValue={education.degree}
                                errors={{
                                    fr: errors['degree.fr'],
                                    en: errors['degree.en'],
                                }}
                            />

                            <TranslatableField
                                name="field"
                                label="Filière"
                                required
                                defaultValue={education.field}
                                errors={{
                                    fr: errors['field.fr'],
                                    en: errors['field.en'],
                                }}
                            />

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">Début *</Label>
                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        defaultValue={education.start_date}
                                        required
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">Fin</Label>
                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                        defaultValue={education.end_date ?? ''}
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                defaultValue={
                                    education.description ?? undefined
                                }
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">Ordre</Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={education.sort_order}
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

EducationEdit.layout = {
    breadcrumbs: [
        { title: 'Formation', href: educationsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
