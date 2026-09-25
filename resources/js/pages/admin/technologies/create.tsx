import { Form, Head } from '@inertiajs/react';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import Heading from '@/components/heading';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import FormSelect from '@/components/admin/form-select';
import { TECHNOLOGY_CATEGORIES } from '@/lib/admin-options';
import { index as technologiesIndex } from '@/routes/admin/technologies';

export default function TechnologyCreate() {
    return (
        <>
            <Head title="Nouvelle technologie" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouvelle technologie"
                    description="Ajouter une technologie à la stack"
                />

                <Form
                    {...TechnologyController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.name}>
                                <FieldLabel htmlFor="name">Nom *</FieldLabel>
                                <Input id="name" name="name" required />
                                <FieldError>{errors.name}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.category}>
                                <FieldLabel htmlFor="category">
                                    Catégorie *
                                </FieldLabel>
                                <FormSelect
                                    id="category"
                                    name="category"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Sélectionner...
                                    </option>
                                    {TECHNOLOGY_CATEGORIES.map((category) => (
                                        <option
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {category.label}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.category}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.icon}>
                                <FieldLabel htmlFor="icon">Icône</FieldLabel>
                                <Input id="icon" name="icon" />
                                <FieldError>{errors.icon}</FieldError>
                            </Field>

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TechnologyCreate.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: technologiesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
