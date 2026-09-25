import { Link, usePage } from '@inertiajs/react';
import { useLocale, useTranslations } from '@/lib/i18n';

/**
 * Bascule vers l'autre langue en restant sur la même page
 * (/fr/projects devient /en/projects).
 */
export default function LanguageSwitch() {
    const t = useTranslations();
    const locale = useLocale();
    const { url } = usePage();
    const other = locale === 'fr' ? 'en' : 'fr';
    const href = url.replace(new RegExp(`^/${locale}(?=[/?#]|$)`), `/${other}`);

    return (
        <Link
            href={href}
            className="pub-round pub-lang"
            aria-label={t.nav.switchLanguage}
            hrefLang={other}
            lang={other}
        >
            {other.toUpperCase()}
        </Link>
    );
}
