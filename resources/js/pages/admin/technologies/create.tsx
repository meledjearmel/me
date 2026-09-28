import { Form, Head } from "@inertiajs/react";
import TechnologyController from "@/actions/App/Http/Controllers/Admin/TechnologyController";
import FormPageHeader from "@/components/admin/form-page-header";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import FormSelect from "@/components/admin/form-select";
import TechnologyIconPicker from "@/components/admin/technology-icon-picker";
import TranslatableField from "@/components/translatable-field";
import { index as technologiesIndex } from "@/routes/admin/technologies";
import type { TechnologyCategory, TechnologyIconOption } from "@/types";

export default function TechnologyCreate({
    icons,
    categories,
}: {
    icons: TechnologyIconOption[];
    categories: Pick<TechnologyCategory, "id" | "label">[];
}) {
    return (
        <>
            <Head title="Nouvelle technologie" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle technologie"
                    description="Ajouter une technologie à la stack"
                    backHref={technologiesIndex()}
                    backLabel="Technologies"
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

                            <Field data-invalid={!!errors.category_id}>
                                <FieldLabel htmlFor="category_id">
                                    Catégorie *
                                </FieldLabel>
                                <FormSelect
                                    id="category_id"
                                    name="category_id"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Sélectionner...
                                    </option>
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
                                    invalid={!!errors.icon}
                                />
                                <FieldError>{errors.icon}</FieldError>
                            </Field>

                            <TranslatableField
                                name="description"
                                label="Description (infobulle du logo, 150 max)"
                                maxLength={150}
                                technologySource={() => {
                                    const form = document
                                        .getElementById("name")
                                        ?.closest("form");
                                    const data = new FormData(
                                        form ?? undefined,
                                    );
                                    const category = categories.find(
                                        (item) =>
                                            String(item.id) ===
                                            data.get("category_id"),
                                    );

                                    return {
                                        name: String(data.get("name") ?? ""),
                                        category: category?.label.fr ?? null,
                                    };
                                }}
                                errors={{
                                    fr: errors["description.fr"],
                                    en: errors["description.en"],
                                }}
                            />

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
        { title: "Technologies", href: technologiesIndex() },
        { title: "Nouvelle", href: "" },
    ],
};
