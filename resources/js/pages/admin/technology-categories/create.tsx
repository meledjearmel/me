import { Form, Head } from '@inertiajs/react';
import TechnologyCategoryController from '@/actions/App/Http/Controllers/Admin/TechnologyCategoryController';
import FormPageHeader from '@/components/admin/form-page-header';
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

export default function TechnologyCategoryCreate() {
    return (
        <>
            <Head title="Nouvelle catégorie" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle catégorie"
                    description="Ajouter une catégorie à la stack technique"
                    backHref={categoriesIndex()}
                    backLabel="Catégories de technologies"
                />

                <Form
                    {...TechnologyCategoryController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input
                                    id="key"
                                    name="key"
                                    placeholder="frameworks"
                                    required
                                />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
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
                                    defaultValue={0}
                                />
                                <FieldError>{errors.sort_order}</FieldError>
                            </Field>

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TechnologyCategoryCreate.layout = {
    breadcrumbs: [
        { title: 'Catégories de technologies', href: categoriesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
