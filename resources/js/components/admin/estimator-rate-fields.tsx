import EstimatorRateInputs from '@/components/admin/estimator-rate-inputs';
import TranslatableField from '@/components/translatable-field';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { EstimatorRate } from '@/types';

/** Champs d'une grille tarifaire : son nom et ses taux par devise. */
export default function EstimatorRateFields({
    rate,
    errors,
}: {
    rate?: EstimatorRate;
    errors: Record<string, string | undefined>;
}) {
    return (
        <>
            <TranslatableField
                name="name"
                label="Nom"
                required
                defaultValue={rate?.name}
                errors={{ fr: errors['name.fr'], en: errors['name.en'] }}
            />

            <div className="space-y-2">
                <EstimatorRateInputs
                    name="hourly_rates"
                    defaultValues={rate?.hourly_rates}
                    errors={errors}
                />
                <FieldDescription>
                    Une devise laissée vide suit le taux global des réglages.
                </FieldDescription>
            </div>

            <Field data-invalid={!!errors.sort_order}>
                <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                <Input
                    id="sort_order"
                    name="sort_order"
                    type="number"
                    min={0}
                    defaultValue={rate?.sort_order ?? 0}
                />
                <FieldError>{errors.sort_order}</FieldError>
            </Field>
        </>
    );
}
