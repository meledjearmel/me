import { Form, Head } from '@inertiajs/react';
import AvailabilityController from '@/actions/App/Http/Controllers/Admin/AvailabilityController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import DeleteButton from '@/components/admin/delete-button';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { WEEK_DAYS } from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/availability';
import type { AvailabilityRule, BlockedPeriod } from '@/types';

/** « Lundi, mardi, mercredi » : les jours d'une plage, dans l'ordre de la semaine. */
const daysLabel = (days: string[]) =>
    WEEK_DAYS.filter((day) => days.includes(day.value))
        .map((day) => day.label)
        .join(', ');

export default function AvailabilityIndex({
    rules,
    blockedPeriods,
}: {
    rules: AvailabilityRule[];
    blockedPeriods: BlockedPeriod[];
}) {
    const today = new Date().toISOString().slice(0, 10);

    return (
        <>
            <Head title="Disponibilités" />

            <div className="flex max-w-4xl flex-col gap-10 p-4">
                <Heading
                    title="Disponibilités"
                    description="Quand les visiteurs peuvent réserver un rendez-vous. Les heures sont celles d'Abidjan (GMT). Ouverture, délais et lien visio : Réglages du site."
                />

                <section className="space-y-4">
                    <h2 className="text-lg font-semibold">
                        Plages de la semaine
                    </h2>

                    {rules.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aucune plage : aucun créneau n'est proposé.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {rules.map((rule) => (
                                <li
                                    key={rule.id}
                                    className="flex items-center justify-between gap-4 p-3"
                                >
                                    <span>
                                        <strong>{daysLabel(rule.days)}</strong>
                                        <span className="text-muted-foreground">
                                            {' '}
                                            · {rule.start} – {rule.end}
                                        </span>
                                    </span>
                                    <DeleteButton
                                        href={AvailabilityController.destroy.url(
                                            rule.id,
                                        )}
                                        confirmMessage="Retirer cette plage ? Les rendez-vous déjà pris sont conservés."
                                    />
                                </li>
                            ))}
                        </ul>
                    )}

                    <Form
                        {...AvailabilityController.storeRule.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-4 rounded-lg border p-4"
                    >
                        {({ processing, errors }) => (
                            <FieldGroup>
                                <div>
                                    <CheckboxGroup
                                        label="Jours"
                                        name="days"
                                        options={WEEK_DAYS.map((day) => ({
                                            id: day.value,
                                            label: day.label,
                                        }))}
                                    />
                                    <FieldError>
                                        {errors.days ?? errors['days.0']}
                                    </FieldError>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field data-invalid={!!errors.start}>
                                        <FieldLabel htmlFor="start">
                                            De
                                        </FieldLabel>
                                        <Input
                                            id="start"
                                            name="start"
                                            type="time"
                                            defaultValue="09:00"
                                            required
                                        />
                                        <FieldError>{errors.start}</FieldError>
                                    </Field>
                                    <Field data-invalid={!!errors.end}>
                                        <FieldLabel htmlFor="end">À</FieldLabel>
                                        <Input
                                            id="end"
                                            name="end"
                                            type="time"
                                            defaultValue="12:00"
                                            required
                                        />
                                        <FieldError>{errors.end}</FieldError>
                                    </Field>
                                </div>
                                <Button disabled={processing} className="w-fit">
                                    Ajouter la plage
                                </Button>
                            </FieldGroup>
                        )}
                    </Form>
                </section>

                <section className="space-y-4">
                    <h2 className="text-lg font-semibold">Périodes bloquées</h2>
                    <p className="text-sm text-muted-foreground">
                        Congés, déplacements… Aucun créneau n'est proposé ces
                        jours-là.
                    </p>

                    {blockedPeriods.length > 0 && (
                        <ul className="divide-y rounded-lg border">
                            {blockedPeriods.map((period) => (
                                <li
                                    key={period.id}
                                    className="flex items-center justify-between gap-4 p-3"
                                >
                                    <span>
                                        <strong>
                                            {period.from === period.to
                                                ? formatDate(period.from)
                                                : `${formatDate(period.from)} → ${formatDate(period.to)}`}
                                        </strong>
                                        {period.label && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                · {period.label}
                                            </span>
                                        )}
                                    </span>
                                    <DeleteButton
                                        href={AvailabilityController.destroy.url(
                                            period.id,
                                        )}
                                        confirmMessage="Retirer cette période bloquée ?"
                                    />
                                </li>
                            ))}
                        </ul>
                    )}

                    <Form
                        {...AvailabilityController.storeBlockedPeriod.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-4 rounded-lg border p-4"
                    >
                        {({ processing, errors }) => (
                            <FieldGroup>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field data-invalid={!!errors.from}>
                                        <FieldLabel htmlFor="from">
                                            Du
                                        </FieldLabel>
                                        <Input
                                            id="from"
                                            name="from"
                                            type="date"
                                            min={today}
                                            required
                                        />
                                        <FieldError>{errors.from}</FieldError>
                                    </Field>
                                    <Field data-invalid={!!errors.to}>
                                        <FieldLabel htmlFor="to">
                                            Au (inclus)
                                        </FieldLabel>
                                        <Input
                                            id="to"
                                            name="to"
                                            type="date"
                                            min={today}
                                            required
                                        />
                                        <FieldError>{errors.to}</FieldError>
                                    </Field>
                                </div>
                                <Field data-invalid={!!errors.label}>
                                    <FieldLabel htmlFor="label">
                                        Motif (pour vous)
                                    </FieldLabel>
                                    <Input
                                        id="label"
                                        name="label"
                                        placeholder="Congés"
                                    />
                                    <FieldError>{errors.label}</FieldError>
                                </Field>
                                <Button disabled={processing} className="w-fit">
                                    Bloquer ces jours
                                </Button>
                            </FieldGroup>
                        )}
                    </Form>
                </section>
            </div>
        </>
    );
}

AvailabilityIndex.layout = {
    breadcrumbs: [{ title: 'Disponibilités', href: pageIndex() }],
};
