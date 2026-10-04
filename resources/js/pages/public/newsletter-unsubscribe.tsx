import { Form, Link } from '@inertiajs/react';
import NewsletterController from '@/actions/App/Http/Controllers/NewsletterController';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';

/**
 * Désinscription de la newsletter depuis le lien d'un email. Elle demande un clic :
 * un antivirus qui ouvre les liens du message ne désinscrit personne.
 */
export default function NewsletterUnsubscribe({
    token,
    email,
    unsubscribed,
}: {
    token: string;
    email: string;
    unsubscribed: boolean;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();

    return (
        <>
            <Seo
                title={t.newsletter.unsubscribeTitle}
                description={t.newsletter.text}
            >
                <meta name="robots" content="noindex, nofollow" />
            </Seo>

            <PublicShell overHero>
                <PageHero
                    id="pub-unsubscribe-title"
                    eyebrow={t.nav.blog}
                    title={t.newsletter.unsubscribeTitle}
                />

                <section
                    className="pub-contact pub-booking"
                    aria-label={t.newsletter.unsubscribeTitle}
                >
                    <div className="site-wrap">
                        <div className="pub-contact__card pub-booking__card">
                            {unsubscribed ? (
                                <div className="pub-drawer__done" role="status">
                                    <h3>{t.newsletter.unsubscribedTitle}</h3>
                                    <p>{t.newsletter.unsubscribedText}</p>
                                    <Link
                                        href={path('blog')}
                                        className="pub-contact__again"
                                    >
                                        {t.blog.back}
                                    </Link>
                                </div>
                            ) : (
                                <Form
                                    {...NewsletterController.destroy.form({
                                        locale,
                                        token,
                                    })}
                                    options={{ preserveScroll: true }}
                                    className="pub-booking__step"
                                >
                                    {({ processing }) => (
                                        <>
                                            <p>
                                                {t.newsletter.unsubscribeText(
                                                    email,
                                                )}
                                            </p>
                                            <button
                                                type="submit"
                                                className="pub-drawer__send"
                                                disabled={processing}
                                            >
                                                {t.newsletter.unsubscribeSubmit}
                                                <span aria-hidden="true">
                                                    →
                                                </span>
                                            </button>
                                        </>
                                    )}
                                </Form>
                            )}
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
