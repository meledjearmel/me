import { motion } from 'framer-motion';
import type { ReactNode } from 'react';
import HeroScene from '@/components/public/hero-scene';
import Letters from '@/components/public/letters';

const reveal = (delay: number) => ({
    initial: { opacity: 0, y: 24 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.8, delay, ease: [0.22, 1, 0.36, 1] as const },
});

/**
 * Bandeau des pages intérieures : un ciel plus calme que celui de l'accueil (jour
 * ou nuit, étoiles, quelques nuages), un grand titre à gauche, et à droite le
 * contenu propre à la page (`aside`). Il se termine par une vague.
 */
export default function PageHero({
    eyebrow,
    title,
    id,
    lead,
    children,
    aside,
}: {
    eyebrow: string;
    title: string;
    id: string;
    lead?: ReactNode;
    children?: ReactNode;
    aside?: ReactNode;
}) {
    return (
        <section className="pub-page-hero" aria-labelledby={id}>
            <HeroScene variant="soft" />

            <div className="pub-page-hero__grid">
                <div className="pub-page-hero__copy">
                    <motion.p className="pub-hero__eyebrow" {...reveal(0)}>
                        <span className="pub-hero__dot" />
                        {eyebrow}
                    </motion.p>

                    <motion.h1 id={id} className="pub-page-hero__title" {...reveal(0.1)}>
                        <Letters text={title} />
                    </motion.h1>

                    {(lead ?? children) && (
                        <motion.div className="pub-page-hero__lead" {...reveal(0.25)}>
                            {lead ?? children}
                        </motion.div>
                    )}
                </div>

                {aside}
            </div>

            <svg
                className="pub-page-hero__wave"
                viewBox="0 0 1440 120"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <path d="M0,70 C180,120 360,20 600,60 C840,100 1020,10 1240,52 C1330,68 1390,74 1440,66 L1440,121 L0,121 Z" />
            </svg>
        </section>
    );
}
