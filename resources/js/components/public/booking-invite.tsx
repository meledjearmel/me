import { Link, usePage } from '@inertiajs/react';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';

/**
 * « Plutôt en parler de vive voix ? Prendre rendez-vous → » : raccourci vers la
 * prise de rendez-vous, affiché seulement quand elle est ouverte.
 */
export default function BookingInvite({
    className = '',
}: {
    className?: string;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const { bookingOpen } = usePage<{ bookingOpen?: boolean }>().props;

    if (!bookingOpen) {
        return null;
    }

    return (
        <p className={`pub-review-invite ${className}`.trim()}>
            <span>{t.booking.ctaHint}</span>
            <Link href={path('appointments')} prefetch>
                {t.booking.cta} <span aria-hidden="true">→</span>
            </Link>
        </p>
    );
}
