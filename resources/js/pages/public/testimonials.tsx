import { motion } from 'framer-motion';
import Seo from '@/components/public/seo';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import { OPEN_REVIEW_EVENT } from '@/components/public/action-menu';
import { useTranslations } from '@/lib/i18n';
import type { PublicTestimonial } from '@/types';

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

/** Tous les avis approuvés, en colonnes qui s'ajustent à la longueur de chacun. */
export default function Testimonials({
    testimonials,
}: {
    testimonials: PublicTestimonial[];
}) {
    const t = useTranslations();

    return (
        <>
            <Seo title={t.testimonials.pageTitle} description={t.testimonials.lead} />

            <PublicShell overHero>
                <PageHero
                    id="pub-testimonials-title"
                    eyebrow={t.testimonials.kicker}
                    title={t.testimonials.title}
                    lead={
                        <>
                            <p>{t.testimonials.lead}</p>
                            <button
                                type="button"
                                className="pub-page-hero__back pub-reviews__cta"
                                onClick={() => window.dispatchEvent(new Event(OPEN_REVIEW_EVENT))}
                            >
                                {t.fab.review} →
                            </button>
                        </>
                    }
                />

                <section className="pub-reviews" aria-label={t.testimonials.kicker}>
                    <div className="site-wrap">
                        <p className="pub-reviews__count">
                            {testimonials.length} {t.testimonials.countLabel}
                        </p>

                        {testimonials.length === 0 ? (
                            <p className="pub-reviews__empty">{t.testimonials.empty}</p>
                        ) : (
                            <ul className="pub-reviews__grid">
                                {testimonials.map((testimonial, index) => (
                                    <motion.li
                                        key={testimonial.id}
                                        className="pub-review"
                                        initial={{ opacity: 0, y: 28 }}
                                        whileInView={{ opacity: 1, y: 0 }}
                                        viewport={{ once: true, amount: 0.2 }}
                                        transition={{
                                            duration: 0.55,
                                            delay: (index % 3) * 0.07,
                                            ease: [0.22, 1, 0.36, 1],
                                        }}
                                    >
                                        <figure>
                                            <blockquote>{testimonial.content}</blockquote>
                                            <figcaption>
                                                <span className="pub-review__avatar" aria-hidden="true">
                                                    {initials(testimonial.author_name)}
                                                </span>
                                                <span>
                                                    <strong>{testimonial.author_name}</strong>
                                                    {testimonial.author_role && (
                                                        <em>{testimonial.author_role}</em>
                                                    )}
                                                </span>
                                            </figcaption>
                                        </figure>
                                    </motion.li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
