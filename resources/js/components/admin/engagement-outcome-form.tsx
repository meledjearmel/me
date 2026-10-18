import { Form } from '@inertiajs/react';
import { useState } from 'react';
import EngagementController from '@/actions/App/Http/Controllers/Admin/EngagementController';
import FormSelect from '@/components/admin/form-select';
import { ESTIMATOR_CURRENCIES } from '@/components/admin/estimator-rate-inputs';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { Engagement } from '@/types';

const OUTCOMES = [
    { value: '', label: 'Pas encore d’issue' },
    { value: 'won', label: 'Signée' },
    { value: 'lost', label: 'Perdue' },
] as const;

/**
 * L'issue d'une demande : signée avec le montant réellement signé, ou perdue. Alimente la
 * « Valeur gagnée » du tableau de bord (taux de signature, écart avec l'estimation).
 */
export default function EngagementOutcomeForm({
    engagement,
}: {
    engagement: Engagement;
}) {
    const [outcome, setOutcome] = useState<string>(engagement.outcome ?? '');

    return (
        <Form
            {...EngagementController.outcome.form(engagement.id)}
            options={{ preserveScroll: true }}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <Field data-invalid={!!errors.outcome}>
                        <FieldLabel htmlFor="outcome">Issue</FieldLabel>
                        <FormSelect
                            id="outcome"
                            name="outcome"
                            defaultValue={outcome}
                            onValueChange={setOutcome}
                        >
                            {OUTCOMES.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </FormSelect>
                        <FieldError>{errors.outcome}</FieldError>
                    </Field>

                    {outcome === 'won' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field data-invalid={!!errors.outcome_amount}>
                                <FieldLabel htmlFor="outcome_amount">
                                    Montant signé (hors taxes) *
                                </FieldLabel>
                                <Input
                                    id="outcome_amount"
                                    name="outcome_amount"
                                    type="number"
                                    min={0}
                                    required
                                    defaultValue={
                                        engagement.outcome_amount ?? ''
                                    }
                                />
                                <FieldError>{errors.outcome_amount}</FieldError>
                            </Field>
                            <Field data-invalid={!!errors.outcome_currency}>
                                <FieldLabel htmlFor="outcome_currency">
                                    Devise *
                                </FieldLabel>
                                <FormSelect
                                    id="outcome_currency"
                                    name="outcome_currency"
                                    required
                                    defaultValue={
                                        engagement.outcome_currency ??
                                        engagement.estimate?.currency ??
                                        'CHF'
                                    }
                                >
                                    {ESTIMATOR_CURRENCIES.map((currency) => (
                                        <option
                                            key={currency.value}
                                            value={currency.value}
                                        >
                                            {currency.label}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>
                                    {errors.outcome_currency}
                                </FieldError>
                            </Field>
                        </div>
                    )}

                    <FieldDescription>
                        Une issue range aussi la demande parmi les traitées.
                    </FieldDescription>

                    <Button disabled={processing}>Enregistrer l’issue</Button>
                </>
            )}
        </Form>
    );
}
