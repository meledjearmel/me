import CheckboxGroup from '@/components/admin/checkbox-group';
import TranslatableField from '@/components/translatable-field';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { APPOINTMENT_LOCATIONS } from '@/lib/admin-options';
import type { AppointmentType } from '@/types';

/** Champs communs aux formulaires de création et de modification d'un type de rendez-vous. */
export default function AppointmentTypeFields({
    appointmentType,
    errors,
}: {
    appointmentType?: AppointmentType;
    errors: Record<string, string | undefined>;
}) {
    return (
        <>
            <TranslatableField
                name="name"
                label="Nom"
                required
                defaultValue={appointmentType?.name}
                errors={{ fr: errors['name.fr'], en: errors['name.en'] }}
            />

            <TranslatableField
                name="description"
                label="Description"
                textarea
                defaultValue={appointmentType?.description ?? undefined}
                errors={{
                    fr: errors['description.fr'],
                    en: errors['description.en'],
                }}
            />

            <Field data-invalid={!!errors.duration_minutes}>
                <FieldLabel htmlFor="duration_minutes">
                    Durée (minutes) *
                </FieldLabel>
                <Input
                    id="duration_minutes"
                    name="duration_minutes"
                    type="number"
                    min={10}
                    max={480}
                    step={5}
                    required
                    defaultValue={appointmentType?.duration_minutes ?? 30}
                />
                <FieldError>{errors.duration_minutes}</FieldError>
            </Field>

            <div>
                <CheckboxGroup
                    label="Lieux possibles *"
                    name="locations"
                    options={APPOINTMENT_LOCATIONS.map((location) => ({
                        id: location.value,
                        label: location.label,
                    }))}
                    defaultSelectedIds={appointmentType?.locations ?? ['video']}
                />
                <FieldError>
                    {errors.locations ?? errors['locations.0']}
                </FieldError>
            </div>

            <Field orientation="horizontal">
                <input type="hidden" name="is_active" value="0" />
                <Checkbox
                    id="is_active"
                    name="is_active"
                    value="1"
                    defaultChecked={appointmentType?.is_active ?? true}
                />
                <FieldLabel htmlFor="is_active">
                    Proposé aux visiteurs
                </FieldLabel>
            </Field>
            <FieldDescription>
                Décoché, le type reste dans l'admin mais disparaît de la page de
                réservation.
            </FieldDescription>

            <Field data-invalid={!!errors.sort_order}>
                <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                <Input
                    id="sort_order"
                    name="sort_order"
                    type="number"
                    min={0}
                    defaultValue={appointmentType?.sort_order ?? 0}
                />
                <FieldError>{errors.sort_order}</FieldError>
            </Field>
        </>
    );
}
