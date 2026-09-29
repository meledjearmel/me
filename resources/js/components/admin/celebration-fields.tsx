import TranslatableField from '@/components/translatable-field';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { Celebration } from '@/types';

/** Champs communs aux formulaires de création et de modification d'une surprise. */
export default function CelebrationFields({
    celebration,
    errors,
}: {
    celebration?: Celebration;
    errors: Record<string, string | undefined>;
}) {
    return (
        <>
            <TranslatableField
                name="message"
                label="Message"
                textarea
                required
                defaultValue={celebration?.message}
                errors={{
                    fr: errors['message.fr'],
                    en: errors['message.en'],
                }}
            />

            <TranslatableField
                name="button_label"
                label="Texte du bouton"
                required
                defaultValue={
                    celebration?.button_label ?? {
                        fr: 'Féliciter',
                        en: 'Congratulate',
                    }
                }
                errors={{
                    fr: errors['button_label.fr'],
                    en: errors['button_label.en'],
                }}
            />

            <Field data-invalid={!!errors.congratulated_for}>
                <FieldLabel htmlFor="congratulated_for">
                    Félicité pour *
                </FieldLabel>
                <Input
                    id="congratulated_for"
                    name="congratulated_for"
                    maxLength={150}
                    placeholder="votre prix de meilleur agent"
                    defaultValue={celebration?.congratulated_for ?? ''}
                    required
                />
                <FieldDescription>
                    Complète la notification : « Vous avez reçu 3
                    félicitations pour… ». En français, adressé à vous.
                </FieldDescription>
                <FieldError>{errors.congratulated_for}</FieldError>
            </Field>

            <div className="grid gap-2 sm:grid-cols-3">
                <Field data-invalid={!!errors.starts_at}>
                    <FieldLabel htmlFor="starts_at">
                        Affichée à partir du
                    </FieldLabel>
                    <Input
                        id="starts_at"
                        name="starts_at"
                        type="date"
                        defaultValue={celebration?.starts_at ?? ''}
                    />
                    <FieldError>{errors.starts_at}</FieldError>
                </Field>
                <Field data-invalid={!!errors.ends_at}>
                    <FieldLabel htmlFor="ends_at">Jusqu'au</FieldLabel>
                    <Input
                        id="ends_at"
                        name="ends_at"
                        type="date"
                        defaultValue={celebration?.ends_at ?? ''}
                    />
                    <FieldError>{errors.ends_at}</FieldError>
                </Field>
                <Field data-invalid={!!errors.weight}>
                    <FieldLabel htmlFor="weight">Poids</FieldLabel>
                    <Input
                        id="weight"
                        name="weight"
                        type="number"
                        min={1}
                        max={100}
                        defaultValue={celebration?.weight ?? 1}
                    />
                    <FieldDescription>
                        Plus il est élevé, plus elle sort souvent au tirage.
                    </FieldDescription>
                    <FieldError>{errors.weight}</FieldError>
                </Field>
            </div>

            <div className="grid gap-2 sm:grid-cols-3">
                <Field data-invalid={!!errors.chance_percent}>
                    <FieldLabel htmlFor="chance_percent">
                        Chance d'apparition (%)
                    </FieldLabel>
                    <Input
                        id="chance_percent"
                        name="chance_percent"
                        type="number"
                        min={1}
                        max={100}
                        defaultValue={celebration?.chance_percent ?? 33}
                        required
                    />
                    <FieldDescription>
                        Part des visites qui la verront (une fois par session
                        au plus). 100 % : à chaque visite.
                    </FieldDescription>
                    <FieldError>{errors.chance_percent}</FieldError>
                </Field>
                <Field data-invalid={!!errors.delay_seconds}>
                    <FieldLabel htmlFor="delay_seconds">
                        Délai avant apparition (s)
                    </FieldLabel>
                    <Input
                        id="delay_seconds"
                        name="delay_seconds"
                        type="number"
                        min={0}
                        max={600}
                        defaultValue={celebration?.delay_seconds ?? 9}
                        required
                    />
                    <FieldDescription>
                        Temps laissé au visiteur pour découvrir la page.
                    </FieldDescription>
                    <FieldError>{errors.delay_seconds}</FieldError>
                </Field>
                <Field data-invalid={!!errors.display_seconds}>
                    <FieldLabel htmlFor="display_seconds">
                        Durée d'affichage (s)
                    </FieldLabel>
                    <Input
                        id="display_seconds"
                        name="display_seconds"
                        type="number"
                        min={5}
                        max={120}
                        defaultValue={celebration?.display_seconds ?? 15}
                        required
                    />
                    <FieldDescription>
                        Sans interaction, la bulle disparaît ensuite.
                    </FieldDescription>
                    <FieldError>{errors.display_seconds}</FieldError>
                </Field>
            </div>

            <Field orientation="horizontal">
                <input type="hidden" name="is_active" value="0" />
                <Checkbox
                    id="is_active"
                    name="is_active"
                    value="1"
                    defaultChecked={celebration?.is_active ?? true}
                />
                <FieldLabel htmlFor="is_active">Active</FieldLabel>
            </Field>
        </>
    );
}
