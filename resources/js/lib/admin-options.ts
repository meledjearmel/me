import type { AppointmentStatus } from '@/types';

export const PROJECT_STATUSES = [
    { value: 'published', label: 'Publié' },
    { value: 'archived', label: 'Archivé' },
] as const;

export const USES_CATEGORIES = [
    { value: 'hardware', label: 'Matériel' },
    { value: 'development', label: 'Développement' },
    { value: 'apps', label: 'Applications' },
    { value: 'services', label: 'Services' },
] as const;

export const TESTIMONIAL_STATUSES = [
    { value: 'pending', label: 'En attente' },
    { value: 'approved', label: 'Approuvé' },
    { value: 'rejected', label: 'Rejeté' },
] as const;

export const CONTACT_STATUSES = [
    { value: 'new', label: 'Nouveau' },
    { value: 'read', label: 'Lu' },
    { value: 'replied', label: 'Répondu' },
] as const;

export const REFERENCE_VISIBLE_FIELDS = [
    { value: 'name', label: 'Nom' },
    { value: 'role', label: 'Rôle' },
    { value: 'company', label: 'Société' },
    { value: 'email', label: 'Email' },
    { value: 'phone', label: 'Téléphone' },
    { value: 'relationship', label: 'Relation' },
] as const;

export const PUBLICATION_STATUSES = [
    { value: 'published', label: 'Publié' },
    { value: 'draft', label: 'Brouillon' },
] as const;

export const APPOINTMENT_STATUSES = [
    { value: 'pending', label: 'En attente' },
    { value: 'confirmed', label: 'Confirmé' },
    { value: 'declined', label: 'Refusé' },
    { value: 'cancelled', label: 'Annulé' },
] as const;

export const APPOINTMENT_LOCATIONS = [
    { value: 'video', label: 'Visio' },
    { value: 'phone', label: 'Téléphone' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'in_person', label: 'En présentiel' },
] as const;

export const WEEK_DAYS = [
    { value: 'monday', label: 'Lundi' },
    { value: 'tuesday', label: 'Mardi' },
    { value: 'wednesday', label: 'Mercredi' },
    { value: 'thursday', label: 'Jeudi' },
    { value: 'friday', label: 'Vendredi' },
    { value: 'saturday', label: 'Samedi' },
    { value: 'sunday', label: 'Dimanche' },
] as const;

/** Libellé d'une valeur dans une de ces listes (la valeur brute si elle n'y est pas). */
export function optionLabel(
    options: readonly { value: string; label: string }[],
    value: string | null | undefined,
): string {
    return (
        options.find((option) => option.value === value)?.label ?? value ?? ''
    );
}

export const APPOINTMENT_STATUS_VARIANT: Record<
    AppointmentStatus,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    pending: 'default',
    confirmed: 'secondary',
    declined: 'outline',
    cancelled: 'outline',
};

const dateTime = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Africa/Abidjan',
});

const time = new Intl.DateTimeFormat('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Africa/Abidjan',
});

/** « mercredi 7 octobre à 10:00 – 11:00 », en heure d'Abidjan. */
export const formatSlot = (startsAt: string, endsAt: string): string =>
    `${dateTime.format(new Date(startsAt))} – ${time.format(new Date(endsAt))}`;
