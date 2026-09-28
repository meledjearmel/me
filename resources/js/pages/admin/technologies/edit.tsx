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
import TechnologyIconPicker from '@/components/admin/technology-icon-picker';
import TranslatableField from '@/components/translatable-field';
import { index as technologiesIndex } from '@/routes/admin/technologies';
import type {
    Technology,
    TechnologyCategory,
    TechnologyIconOption,
} from '@/types';

export default function TechnologyEdit({
    technology,
    icons,
    categories,
}: {
    technology: Technology;
    icons: TechnologyIconOption[];
    categories: Pick<TechnologyCategory, 'id' | 'label'>[];
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

                            <Field data-invalid={!!errors.category_id}>
                                <FieldLabel htmlFor="category_id">
                                    Catégorie *
                                </FieldLabel>
                                <FormSelect
                                    id="category_id"
                                    name="category_id"
                                    required
                                    defaultValue={technology.category_id}
                                >
                                    {categories.map((category) => (
                                        <option
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.label.fr}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.category_id}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.icon}>
                                <FieldLabel>Logo</FieldLabel>
                                <TechnologyIconPicker
                                    icons={icons}
                                    defaultValue={technology.icon}
                                    invalid={!!errors.icon}
                                />
                                <FieldError>{errors.icon}</FieldError>
                            </Field>

                            <TranslatableField
                                name="description"
                                label="Description (infobulle du logo, 150 max)"
                                maxLength={150}
                                defaultValue={{
                                    fr: technology.description?.fr,
                                    en: technology.description?.en,
                                }}
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

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
