import { Form, Link } from '@inertiajs/react';
import { useMemo } from 'react';
import AppointmentCancellationController from '@/actions/App/Http/Controllers/AppointmentCancellationController';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { AppointmentLocation } from '@/types';

/** Annulation d'un rendez-vous par le visiteur, depuis le lien de son email. */
export default function AppointmentCancel({
    appointment,
    token,
}: {
    appointment: {
        type: string | null;
        starts_at: string;
        timezone: string | null;
        location: AppointmentLocation;
        cancellable: boolean;
        cancelled: boolean;
    };
    token: string;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();

    const when = useMemo(() => {
        const options: Intl.DateTimeFormatOptions = {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit',
        };

        try {
            return new Intl.DateTimeFormat(locale, {
                ...options,
                timeZone: appointment.timezone ?? undefined,
            }).format(new Date(appointment.starts_at));
        } catch {
            return new Intl.DateTimeFormat(locale, options).format(
                new Date(appointment.starts_at),
            );
        }
    }, [appointment.starts_at, appointment.timezone, locale]);

    return (
        <>
            <Seo title={t.booking.cancelTitle} description={t.booking.lead}>
                <meta name="robots" content="noindex, nofollow" />
            </Seo>

            <PublicShell overHero>
                <PageHero
                    id="pub-cancel-title"
                    eyebrow={t.booking.kicker}
                    title={t.booking.cancelTitle}
                />

                <section
                    className="pub-contact pub-booking"
                    aria-label={t.booking.cancelTitle}
                >
                    <div className="site-wrap">
                        <div className="pub-contact__card pub-booking__card">
                            <p className="pub-booking__summary">
                                <span>{t.booking.summary}</span>
                                <strong>
                                    {[appointment.type, when]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </strong>
                                <span>
                                    {t.booking.locations[appointment.location]}
                                </span>
                            </p>

                            {appointment.cancelled ? (
                                <div className="pub-drawer__done" role="status">
                                    <h3>{t.booking.cancelledTitle}</h3>
                                    <p>{t.booking.cancelledText}</p>
                                    <Link
                                        href={path('appointments')}
                                        className="pub-contact__again"
                                    >
                                        {t.booking.again}
                                    </Link>
                                </div>
                            ) : appointment.cancellable ? (
                                <Form
                                    {...AppointmentCancellationController.store.form(
                                        { locale, token },
                                    )}
                                    options={{ preserveScroll: true }}
                                    className="pub-booking__step"
                                >
                                    {({ processing }) => (
                                        <>
                                            <p>{t.booking.cancelText}</p>
                                            <button
                                                type="submit"
                                                className="pub-drawer__send"
                                                disabled={processing}
                                            >
                                                {t.booking.cancelSubmit}
                                                <span aria-hidden="true">
                                                    →
                                                </span>
                                            </button>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <p className="pub-booking__note">
                                    {t.booking.cancelPast}
                                </p>
                            )}
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
