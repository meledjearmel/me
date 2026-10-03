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
import type { AvailabilityRule, BlockedPeriod, BookingSettings } from '@/types';

/** « Lundi, mardi, mercredi » : les jours d'une plage, dans l'ordre de la semaine. */
const daysLabel = (days: string[]) =>
    WEEK_DAYS.filter((day) => days.includes(day.value))
        .map((day) => day.label)
        .join(', ');

export default function AvailabilityIndex({
    settings,
    rules,
    blockedPeriods,
}: {
    settings: BookingSettings;
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
                    description="Quand les visiteurs peuvent réserver un rendez-vous. Les heures sont celles d'Abidjan (GMT)."
                />

                <section className="space-y-4">
                    <h2 className="text-lg font-semibold">Réglages</h2>
                    <Form
                        {...AvailabilityController.updateSettings.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <FieldGroup>
                                <Field orientation="horizontal">
                                    <input
                                        type="hidden"
                                        name="is_enabled"
                                        value="0"
                                    />
                                    <Checkbox
                                        id="is_enabled"
                                        name="is_enabled"
                                        value="1"
                                        defaultChecked={settings.is_enabled}
                                    />
                                    <FieldLabel htmlFor="is_enabled">
                                        Prise de rendez-vous ouverte
                                    </FieldLabel>
                                </Field>
                                <FieldDescription>
                                    Fermée, la page de réservation et ses
                                    raccourcis disparaissent du site.
                                </FieldDescription>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <Field
                                        data-invalid={!!errors.min_notice_hours}
                                    >
                                        <FieldLabel htmlFor="min_notice_hours">
                                            Délai minimum (heures)
                                        </FieldLabel>
                                        <Input
                                            id="min_notice_hours"
                                            name="min_notice_hours"
                                            type="number"
                                            min={0}
                                            max={720}
                                            defaultValue={
                                                settings.min_notice_hours
                                            }
                                        />
                                        <FieldError>
                                            {errors.min_notice_hours}
                                        </FieldError>
                                    </Field>
                                    <Field data-invalid={!!errors.horizon_days}>
                                        <FieldLabel htmlFor="horizon_days">
                                            Réservable jusqu'à (jours)
                                        </FieldLabel>
                                        <Input
                                            id="horizon_days"
                                            name="horizon_days"
                                            type="number"
                                            min={1}
                                            max={365}
                                            defaultValue={settings.horizon_days}
                                        />
                                        <FieldError>
                                            {errors.horizon_days}
                                        </FieldError>
                                    </Field>
                                    <Field
                                        data-invalid={!!errors.buffer_minutes}
                                    >
                                        <FieldLabel htmlFor="buffer_minutes">
                                            Pause entre deux RDV (min)
                                        </FieldLabel>
                                        <Input
                                            id="buffer_minutes"
                                            name="buffer_minutes"
                                            type="number"
                                            min={0}
                                            max={240}
                                            step={5}
                                            defaultValue={
                                                settings.buffer_minutes
                                            }
                                        />
                                        <FieldError>
                                            {errors.buffer_minutes}
                                        </FieldError>
                                    </Field>
                                </div>

                                <Field data-invalid={!!errors.video_link}>
                                    <FieldLabel htmlFor="video_link">
                                        Lien visio par défaut
                                    </FieldLabel>
                                    <Input
                                        id="video_link"
                                        name="video_link"
                                        type="url"
                                        placeholder="https://meet.google.com/…"
                                        defaultValue={settings.video_link ?? ''}
                                    />
                                    <FieldDescription>
                                        Repris à la confirmation d'une visio si
                                        vous n'en précisez pas d'autre.
                                    </FieldDescription>
                                    <FieldError>{errors.video_link}</FieldError>
                                </Field>

                                <Button disabled={processing} className="w-fit">
                                    Enregistrer
                                </Button>
                            </FieldGroup>
                        )}
                    </Form>
                </section>

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
