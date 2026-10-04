import { usePage } from '@inertiajs/react';
import { useLocale, useTranslations } from '@/lib/i18n';

export type Availability = {
    status: 'available' | 'from' | 'unavailable';
    /** AAAA-MM-JJ, avec le statut `from`. */
    from: string | null;
};

/**
 * Ma disponibilité réglée dans l'admin, avec ses libellés : le badge court de
 * l'en-tête et la mention de la fenêtre de contact.
 */
export function useAvailability(): Availability & {
    badge: string;
    eyebrow: string;
} {
    const t = useTranslations();
    const locale = useLocale();
    const { availability } = usePage<{ availability?: Availability }>().props;
    const current: Availability = availability ?? {
        status: 'available',
        from: null,
    };

    if (current.status === 'from' && current.from) {
        // Midi : aucun fuseau ne fait basculer la date sur le jour voisin.
        const date = new Intl.DateTimeFormat(locale, {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${current.from}T12:00:00`));

        return {
            ...current,
            badge: t.hero.availableFrom(date),
            eyebrow: t.contactDrawer.eyebrowFrom(date),
        };
    }

    if (current.status === 'unavailable') {
        return {
            ...current,
            badge: t.hero.unavailable,
            eyebrow: t.contactDrawer.eyebrowUnavailable,
        };
    }

    return {
        ...current,
        badge: t.hero.availableForWork,
        eyebrow: t.contactDrawer.eyebrow,
    };
}
