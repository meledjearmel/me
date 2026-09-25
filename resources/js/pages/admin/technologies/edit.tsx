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
import type { Technology } from '@/types';

export default function TechnologyEdit({
    technology,
}: {
    technology: Technology;
}) {
    return (
        <>
            <Head title={`Modifier — ${technology.name}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la technologie"
                    description={technology.name}
                />

                <Form
                    {...TechnologyController.update.form(technology.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.name}>
                                <FieldLabel htmlFor="name">Nom *</FieldLabel>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={technology.name}
                                    required
                                />
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
                                    defaultValue={technology.category}
                                >
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
                                <Input
                                    id="icon"
                                    name="icon"
                                    defaultValue={technology.icon ?? ''}
                                />
                                <FieldError>{errors.icon}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TechnologyEdit.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: technologiesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
