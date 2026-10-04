import FormSelect from '@/components/admin/form-select';
import TranslatableField from '@/components/translatable-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PUBLICATION_STATUSES, USES_CATEGORIES } from '@/lib/admin-options';
import type { UsesItem } from '@/types';

/** Champs d'un élément de la page « Uses », partagés par la création et la modification. */
export default function UsesItemFields({
    item,
    errors,
}: {
    item?: UsesItem;
    errors: Record<string, string>;
}) {
    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.category}>
                    <FieldLabel htmlFor="category">Rubrique *</FieldLabel>
                    <FormSelect
                        id="category"
                        name="category"
                        defaultValue={item?.category}
                        required
                    >
                        <option value="" disabled>
                            Choisir…
                        </option>
                        {USES_CATEGORIES.map((category) => (
                            <option key={category.value} value={category.value}>
                                {category.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.category}</FieldError>
                </Field>

                <Field data-invalid={!!errors.name}>
                    <FieldLabel htmlFor="name">Nom *</FieldLabel>
                    <Input
                        id="name"
                        name="name"
                        maxLength={120}
                        defaultValue={item?.name}
                        placeholder="MacBook Pro 14″, PhpStorm…"
                        required
                    />
                    <FieldError>{errors.name}</FieldError>
                </Field>
            </div>

            <TranslatableField
                name="description"
                label="Description (facultatif)"
                maxLength={300}
                defaultValue={item?.description ?? undefined}
                errors={{
                    fr: errors['description.fr'],
                    en: errors['description.en'],
                }}
            />

            <Field data-invalid={!!errors.status}>
                <FieldLabel htmlFor="status">Statut *</FieldLabel>
                <FormSelect
                    id="status"
                    name="status"
                    required
                    defaultValue={item?.status ?? 'published'}
                >
                    {PUBLICATION_STATUSES.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </FormSelect>
                <FieldError>{errors.status}</FieldError>
            </Field>

            <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <Field data-invalid={!!errors.url}>
                    <FieldLabel htmlFor="url">Lien (facultatif)</FieldLabel>
                    <Input
                        id="url"
                        name="url"
                        type="url"
                        defaultValue={item?.url ?? ''}
                    />
                    <FieldError>{errors.url}</FieldError>
                </Field>

                <Field data-invalid={!!errors.sort_order}>
                    <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min={0}
                        defaultValue={item?.sort_order ?? 0}
                    />
                    <FieldError>{errors.sort_order}</FieldError>
                </Field>
            </div>
        </>
    );
}
