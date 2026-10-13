import { Link, usePage } from '@inertiajs/react';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';

/**
 * « Une idée du budget en une minute ? Estimer mon projet → » : raccourci vers
 * l'estimateur de projet, affiché seulement quand il propose au moins un type de projet.
 */
export default function EstimateInvite({
    className = '',
}: {
    className?: string;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const { estimatorEnabled } = usePage<{ estimatorEnabled?: boolean }>()
        .props;

    if (!estimatorEnabled) {
        return null;
    }

    return (
        <p className={`pub-review-invite ${className}`.trim()}>
            <span>{t.estimate.ctaHint}</span>
            <Link href={path('estimate')} prefetch>
                {t.estimate.cta} <span aria-hidden="true">→</span>
            </Link>
        </p>
    );
}
