import { Form, Head } from '@inertiajs/react';
import EducationController from '@/actions/App/Http/Controllers/Admin/EducationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as educationsIndex } from '@/routes/admin/educations';

export default function EducationCreate() {
    return (
        <>
            <Head title="Nouvelle formation" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading title="Nouvelle formation" />

                <Form
                    {...EducationController.store.form()}
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
                                    required
                                />
                                <InputError message={errors.institution} />
                            </div>

                            <TranslatableField
                                name="degree"
                                label="Diplôme"
                                required
                                errors={{
                                    fr: errors['degree.fr'],
                                    en: errors['degree.en'],
                                }}
                            />

                            <TranslatableField
                                name="field"
                                label="Filière"
                                required
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

EducationCreate.layout = {
    breadcrumbs: [
        { title: 'Formation', href: educationsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
