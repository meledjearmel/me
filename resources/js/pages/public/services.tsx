import { Link } from '@inertiajs/react';
import BookingInvite from '@/components/public/booking-invite';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicService } from '@/types';

/**
 * Page « Services » : chaque offre avec ce qui est compris, un prix de départ dans la
 * devise du visiteur, des projets en exemple et un avis client ; « Estimer mon projet »
 * ouvre l'estimateur avec le bon type de projet déjà choisi.
 */
export default function Services({ services }: { services: PublicService[] }) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();

    const money = (amount: number, currency: string) =>
        `${new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(amount)} ${currency === 'XOF' ? 'FCFA' : currency}`;

    return (
        <>
            <Seo
                title={t.services.title}
                description={t.services.seo}
                breadcrumbs={[[t.services.title, `/${locale}/services`]]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-services-title"
                    eyebrow={t.services.title}
                    title={t.services.heading}
                >
                    <p>{t.services.hook}</p>
                </PageHero>

                <div className="pub-services site-wrap">
                    {services.map((service, index) => (
                        <article
                            key={service.id}
                            id={service.slug}
                            className="pub-service"
                            aria-labelledby={`pub-service-${service.id}`}
                        >
                            <div className="pub-service__main">
                                <p
                                    className="pub-service__index"
                                    aria-hidden="true"
                                >
                                    {String(index + 1).padStart(2, '0')}
                                </p>
                                <h2
                                    id={`pub-service-${service.id}`}
                                    className="pub-service__title"
                                >
                                    {service.title}
                                </h2>
                                {service.tagline && (
                                    <p className="pub-service__tagline">
                                        {service.tagline}
                                    </p>
                                )}
                                {service.description
                                    ?.split(/\n{2,}/)
                                    .map((paragraph) => (
                                        <p
                                            key={paragraph}
                                            className="pub-service__text"
                                        >
                                            {paragraph}
                                        </p>
                                    ))}

                                {service.includes.length > 0 && (
                                    <>
                                        <p className="pub-booking__label">
                                            {t.services.includes}
                                        </p>
                                        <ul className="pub-service__includes">
                                            {service.includes.map((line) => (
                                                <li key={line}>{line}</li>
                                            ))}
                                        </ul>
                                    </>
                                )}
                            </div>

                            <aside className="pub-service__aside">
                                <div className="pub-service__price">
                                    {service.price ? (
                                        <>
                                            <span>{t.services.from}</span>
                                            <strong>
                                                {service.price.billing ===
                                                'hourly'
                                                    ? t.services.fromHourly(
                                                          money(
                                                              service.price
                                                                  .amount,
                                                              service.price
                                                                  .currency,
                                                          ),
                                                      )
                                                    : money(
                                                          service.price.amount,
                                                          service.price
                                                              .currency,
                                                      )}
                                            </strong>
                                        </>
                                    ) : (
                                        <strong>{t.services.onQuote}</strong>
                                    )}
                                </div>

                                <div className="pub-service__actions">
                                    {service.estimate_type_id !== null && (
                                        <Link
                                            href={path(
                                                `estimate?type=${service.estimate_type_id}`,
                                            )}
                                            className="pub-drawer__send"
                                            prefetch
                                        >
                                            {t.services.estimate}
                                            <span aria-hidden="true">→</span>
                                        </Link>
                                    )}
                                    <Link
                                        href={path('contact')}
                                        className="pub-booking__link"
                                    >
                                        {t.services.talk}
                                    </Link>
                                </div>

                                {service.price && (
                                    <p className="pub-booking__note">
                                        {t.services.priceNote}
                                    </p>
                                )}
                            </aside>

                            {(service.projects.length > 0 ||
                                service.testimonial) && (
                                <div className="pub-service__proof">
                                    {service.projects.length > 0 && (
                                        <div>
                                            <p className="pub-booking__label">
                                                {t.services.examples}
                                            </p>
                                            <ul className="pub-service__projects">
                                                {service.projects.map(
                                                    (project) => (
                                                        <li key={project.id}>
                                                            <Link
                                                                href={path(
                                                                    `projects/${project.slug}`,
                                                                )}
                                                                prefetch
                                                            >
                                                                {project.cover_url && (
                                                                    <img
                                                                        src={
                                                                            project.cover_url
                                                                        }
                                                                        alt=""
                                                                        loading="lazy"
                                                                    />
                                                                )}
                                                                <strong>
                                                                    {
                                                                        project.title
                                                                    }
                                                                </strong>
                                                                {project.tagline && (
                                                                    <span>
                                                                        {
                                                                            project.tagline
                                                                        }
                                                                    </span>
                                                                )}
                                                            </Link>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}

                                    {service.testimonial && (
                                        <figure className="pub-service__quote">
                                            <blockquote>
                                                {service.testimonial
                                                    .highlight ??
                                                    service.testimonial.content}
                                            </blockquote>
                                            <figcaption>
                                                {
                                                    service.testimonial
                                                        .author_name
                                                }
                                                {service.testimonial
                                                    .author_role &&
                                                    ` · ${service.testimonial.author_role}`}
                                            </figcaption>
                                        </figure>
                                    )}
                                </div>
                            )}
                        </article>
                    ))}

                    <BookingInvite className="pub-review-invite--start" />
                </div>
            </PublicShell>
        </>
    );
}
