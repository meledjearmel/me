import {
    easeInOut,
    motion,
    useReducedMotion,
    useTransform,
    type MotionValue,
} from 'framer-motion';
import { Link } from '@inertiajs/react';
import { useRef } from 'react';
import { useViewportProgress } from '@/hooks/use-viewport-progress';
import CloudBank from '@/components/public/cloud-bank';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicTestimonial } from '@/types';

/** Écart entre le centre de deux cartes : carte de 430px + 32px d'espace. */
const FAN_STRIDE = 462;

/** Zone d'espacement : 0 % quand son haut touche le bas de la fenêtre, 100 % quand son bas y arrive. */
const SCRUB_RANGE = (height: number, viewport: number): [number, number] => [
    viewport,
    viewport - height,
];

/** Position de chaque carte dans le paquet : devant, à droite, à gauche. */
const POSITIONS = [0, 1, -1] as const;

function QuoteIcon() {
    return (
        <svg
            className="pub-testi__icon"
            viewBox="0 0 82 82"
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M14 50c0-13 8-24 22-29l2 6c-8 3-12 8-12 14h10v22H14V50Zm32 0c0-13 8-24 22-29l2 6c-8 3-12 8-12 14h10v22H46V50Z"
            />
        </svg>
    );
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function TestimonialCard({
    testimonial,
    position,
    spread,
}: {
    testimonial: PublicTestimonial;
    position: number;
    spread: MotionValue<number>;
}) {
    // Les cartes partent empilées au centre puis glissent de côté (x = pos × écart).
    const x = useTransform(spread, (value) => value * position * FAN_STRIDE);

    return (
        <motion.figure
            className="pub-testi__card"
            style={{ x, zIndex: 3 - Math.abs(position) }}
        >
            <QuoteIcon />

            <blockquote className="pub-testi__quote">
                {testimonial.content}
            </blockquote>

            <hr className="pub-testi__divider" />

            <figcaption className="pub-testi__author">
                <span className="pub-testi__avatar" aria-hidden="true">
                    {initials(testimonial.author_name)}
                </span>
                <span>
                    <span className="pub-testi__name">
                        {testimonial.author_name}
                    </span>
                    {testimonial.author_role && (
                        <span className="pub-testi__role">
                            {testimonial.author_role}
                        </span>
                    )}
                </span>
            </figcaption>
        </motion.figure>
    );
}

/**
 * Témoignages : la section glisse par-dessus les projets épinglés, puis reste
 * en place pendant que le paquet de cartes s'ouvre avec le scroll (comme la
 * référence). Sous 1100px, les cartes forment un carrousel qu'on fait glisser.
 */
export default function Testimonials({
    testimonials,
    total,
}: {
    testimonials: PublicTestimonial[];
    /** Nombre total d'avis approuvés : au-delà de ceux du paquet, un lien mène à tous. */
    total: number;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const reduceMotion = useReducedMotion();
    const scrubRef = useRef<HTMLDivElement>(null);

    // Le paquet s'ouvre pendant que la zone d'espacement (juste après la section
    // épinglée) traverse l'écran : progression 0 → 1 sur sa hauteur.
    const scrollYProgress = useViewportProgress(scrubRef, SCRUB_RANGE);
    const scrubbed = useTransform(scrollYProgress, [0, 0.74], [0, 1], {
        ease: easeInOut,
    });
    const fixedOpen = useTransform(scrollYProgress, () => 1);
    const spread = reduceMotion ? fixedOpen : scrubbed;

    if (testimonials.length === 0) {
        return null;
    }

    return (
        <>
            <section
                className="pub-testi"
                id="testimonials"
                aria-labelledby="pub-testi-title"
            >
                <CloudBank
                    className="pub-testi__crest"
                    fill="var(--pub-testi-top)"
                    wisp="var(--pub-testi-wisp)"
                    flip
                />

                <div className="pub-testi__inner">
                    <header className="pub-testi__head">
                        <p className="pub-kicker">{t.testimonials.kicker}</p>
                        <h2 id="pub-testi-title" className="pub-testi__title">
                            {t.testimonials.title}
                        </h2>

                            {total > testimonials.length && (
                            <Link href={path('testimonials')} className="pub-testi__all">
                                {t.testimonials.all} ({total}) <span aria-hidden="true">→</span>
                            </Link>
                        )}
                    </header>

                    <div className="pub-testi__cards">
                        {testimonials.map((testimonial, index) => (
                            <TestimonialCard
                                key={testimonial.id}
                                testimonial={testimonial}
                                position={POSITIONS[index] ?? 0}
                                spread={spread}
                            />
                        ))}
                    </div>
                </div>
            </section>

            <div ref={scrubRef} className="pub-testi__scrub" aria-hidden="true" />
        </>
    );
}
