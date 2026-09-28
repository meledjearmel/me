import { Form, Head } from '@inertiajs/react';
import TechnologyCategoryController from '@/actions/App/Http/Controllers/Admin/TechnologyCategoryController';
import Heading from '@/components/heading';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { index as categoriesIndex } from '@/routes/admin/technology-categories';
import type { TechnologyCategory } from '@/types';

export default function TechnologyCategoryEdit({
    category,
}: {
    category: TechnologyCategory;
}) {
    return (
        <>
            <Head title={`Modifier — ${category.label.fr}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la catégorie"
                    description={category.label.fr}
                />

                <Form
                    {...TechnologyCategoryController.update.form(category.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input
                                    id="key"
                                    name="key"
                                    defaultValue={category.key}
                                    required
                                />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
                                defaultValue={category.label}
                                errors={{
                                    fr: errors['label.fr'],
                                    en: errors['label.en'],
                                }}
                            />

                            <Field data-invalid={!!errors.sort_order}>
                                <FieldLabel htmlFor="sort_order">
                                    Ordre
                                </FieldLabel>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={category.sort_order}
                                />
                                <FieldError>{errors.sort_order}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TechnologyCategoryEdit.layout = {
    breadcrumbs: [
        { title: 'Catégories de technologies', href: categoriesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
