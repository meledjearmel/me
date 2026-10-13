import { Form } from '@inertiajs/react';
import EstimatorSettingsController from '@/actions/App/Http/Controllers/Admin/EstimatorSettingsController';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { EstimatorCurrency, EstimatorSettings } from '@/types';

const CURRENCIES: { value: EstimatorCurrency; label: string }[] = [
    { value: 'CHF', label: 'CHF' },
    { value: 'EUR', label: 'EUR' },
    { value: 'USD', label: 'USD' },
    { value: 'XOF', label: 'FCFA' },
];

/** Taux horaire par devise, marge pour imprévus et heures facturables par semaine. */
export default function EstimatorSettingsForm({
    settings,
}: {
    settings: EstimatorSettings;
}) {
    return (
        <Form
            {...EstimatorSettingsController.update.form()}
            options={{ preserveScroll: true }}
            className="space-y-4 rounded-lg border p-4"
        >
            {({ processing, errors }) => (
                <>
                    <div>
                        <h2 className="font-medium">Réglages du calcul</h2>
                        <p className="text-sm text-muted-foreground">
                            Prix = heures × (1 + options en %) × (1 + marge) ×
                            taux horaire. Une devise sans taux n'est pas
                            proposée ; sans aucun taux, la page est masquée.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {CURRENCIES.map((currency) => (
                            <Field
                                key={currency.value}
                                data-invalid={
                                    !!errors[
                                        `estimator_hourly_rates.${currency.value}`
                                    ]
                                }
                            >
                                <FieldLabel htmlFor={`rate-${currency.value}`}>
                                    Taux horaire ({currency.label})
                                </FieldLabel>
                                <Input
                                    id={`rate-${currency.value}`}
                                    name={`estimator_hourly_rates[${currency.value}]`}
                                    type="number"
                                    min={0}
                                    defaultValue={
                                        settings.estimator_hourly_rates[
                                            currency.value
                                        ] ?? ''
                                    }
                                />
                                <FieldError>
                                    {
                                        errors[
                                            `estimator_hourly_rates.${currency.value}`
                                        ]
                                    }
                                </FieldError>
                            </Field>
                        ))}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field data-invalid={!!errors.estimator_risk_percent}>
                            <FieldLabel htmlFor="estimator_risk_percent">
                                Marge pour imprévus (%)
                            </FieldLabel>
                            <Input
                                id="estimator_risk_percent"
                                name="estimator_risk_percent"
                                type="number"
                                min={0}
                                max={200}
                                required
                                defaultValue={settings.estimator_risk_percent}
                            />
                            <FieldDescription>
                                Ajoutée aux heures, jamais détaillée au
                                visiteur.
                            </FieldDescription>
                            <FieldError>
                                {errors.estimator_risk_percent}
                            </FieldError>
                        </Field>
                        <Field data-invalid={!!errors.estimator_hours_per_week}>
                            <FieldLabel htmlFor="estimator_hours_per_week">
                                Heures facturables par semaine
                            </FieldLabel>
                            <Input
                                id="estimator_hours_per_week"
                                name="estimator_hours_per_week"
                                type="number"
                                min={1}
                                max={80}
                                required
                                defaultValue={settings.estimator_hours_per_week}
                            />
                            <FieldDescription>
                                Sert à convertir les heures en délai.
                            </FieldDescription>
                            <FieldError>
                                {errors.estimator_hours_per_week}
                            </FieldError>
                        </Field>
                    </div>

                    <Button disabled={processing}>
                        Enregistrer les réglages
                    </Button>
                </>
            )}
        </Form>
    );
}
