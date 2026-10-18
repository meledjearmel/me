import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { EstimatorCurrency } from '@/types';

export const ESTIMATOR_CURRENCIES: {
    value: EstimatorCurrency;
    label: string;
}[] = [
    { value: 'CHF', label: 'CHF' },
    { value: 'EUR', label: 'EUR' },
    { value: 'USD', label: 'USD' },
    { value: 'XOF', label: 'FCFA' },
];

/**
 * Un taux horaire par devise. Une devise laissée vide suit le taux de repli, rappelé en
 * texte indicatif (grille ou taux global).
 */
export default function EstimatorRateInputs({
    name,
    defaultValues,
    fallbacks,
    errors,
}: {
    name: string;
    defaultValues?: Partial<Record<EstimatorCurrency, number | null>> | null;
    fallbacks?: Partial<Record<EstimatorCurrency, number | null>>;
    errors: Record<string, string | undefined>;
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {ESTIMATOR_CURRENCIES.map((currency) => {
                const error = errors[`${name}.${currency.value}`];
                const fallback = fallbacks?.[currency.value];

                return (
                    <Field key={currency.value} data-invalid={!!error}>
                        <FieldLabel htmlFor={`${name}-${currency.value}`}>
                            Taux horaire ({currency.label})
                        </FieldLabel>
                        <Input
                            id={`${name}-${currency.value}`}
                            name={`${name}[${currency.value}]`}
                            type="number"
                            min={0}
                            placeholder={
                                fallback ? String(fallback) : undefined
                            }
                            defaultValue={defaultValues?.[currency.value] ?? ''}
                        />
                        <FieldError>{error}</FieldError>
                    </Field>
                );
            })}
        </div>
    );
}
