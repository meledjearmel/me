import { Link } from '@inertiajs/react';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';

/**
 * Page d'erreur du site public (403, 404, 429, 500, 503), rendue par le
 * gestionnaire d'exceptions : même ciel et même coque que les autres pages,
 * avec de quoi repartir vers l'accueil, les projets ou le contact.
 */
export default function ErrorPage({ status }: { status: number }) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const title = t.error.titles[status] ?? t.error.titles[500];
    const text = t.error.texts[status] ?? t.error.texts[500];

    return (
        <>
            <Seo title={t.error.eyebrow(status)} description={text}>
                <meta name="robots" content="noindex" />
            </Seo>

            <PublicShell overHero>
                <PageHero
                    id="pub-error-title"
                    eyebrow={t.error.eyebrow(status)}
                    title={title}
                    lead={
                        <>
                            <p>{text}</p>
                            <nav className="pub-error__links">
                                <Link
                                    href={path('')}
                                    className="pub-error__primary"
                                >
                                    {t.error.home}
                                </Link>
                                <Link href={path('projects')}>
                                    {t.error.projects}
                                </Link>
                                <Link href={path('contact')}>
                                    {t.error.contact}
                                </Link>
                            </nav>
                        </>
                    }
                    aside={
                        <p
                            className="pub-error__code pub-reveal pub-reveal--late"
                            aria-hidden="true"
                        >
                            {status}
                        </p>
                    }
                />

                {/* Place laissée au pied de page, qui remonte sur la section précédente. */}
                <div className="pub-error__spacer" aria-hidden="true" />
            </PublicShell>
        </>
    );
}
