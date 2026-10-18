import { AnimatePresence, motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { useCongratulations } from '@/hooks/use-congratulations';
import { usePrefersReducedMotion } from '@/hooks/use-media-query';
import { burstConfetti } from '@/lib/confetti';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicDistinction } from '@/types';

/** Temps d'affichage d'une distinction avant la suivante. */
const ROTATE_EVERY_MS = 7000;

/**
 * Carte « Distinction » de la page À propos : les distinctions publiées y alternent
 * lentement. La rotation s'arrête au survol et au focus (on félicite celle qu'on voit),
 * et sans animation quand le visiteur les a réduites ; les points permettent de choisir.
 */
export default function DistinctionCard({
    distinctions,
    motionProps,
}: {
    distinctions: PublicDistinction[];
    motionProps: Record<string, unknown>;
}) {
    const t = useTranslations();
    const reduceMotion = usePrefersReducedMotion();
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const total = distinctions.length;
    const rotates = total > 1 && !paused && !reduceMotion;

    useEffect(() => {
        if (!rotates) {
            return;
        }

        const timer = window.setTimeout(
            () => setIndex((current) => (current + 1) % total),
            ROTATE_EVERY_MS,
        );

        return () => window.clearTimeout(timer);
    }, [rotates, index, total]);

    if (total === 0) {
        return null;
    }

    const distinction = distinctions[index % total];

    return (
        <motion.article
            className="pub-fact pub-fact--coral pub-fact--action pub-distinction"
            onPointerEnter={() => setPaused(true)}
            onPointerLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setPaused(false);
                }
            }}
            aria-roledescription={total > 1 ? 'carousel' : undefined}
            {...motionProps}
        >
            <p className="pub-fact__label">
                {total > 1
                    ? t.about.distinctionsLabel
                    : t.about.distinctionLabel}
            </p>

            <AnimatePresence mode="wait" initial={false}>
                <motion.div
                    key={distinction.id}
                    className="pub-distinction__slide"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -10 }}
                    transition={{ duration: 0.35 }}
                    aria-label={
                        total > 1
                            ? t.about.distinctionPosition(index + 1, total)
                            : undefined
                    }
                >
                    <DistinctionSlide distinction={distinction} />
                </motion.div>
            </AnimatePresence>

            {total > 1 && (
                <div className="pub-distinction__dots">
                    {distinctions.map((item, dot) => (
                        <button
                            key={item.id}
                            type="button"
                            aria-label={t.about.distinctionShow(dot + 1)}
                            aria-current={dot === index ? 'true' : undefined}
                            onClick={() => setIndex(dot)}
                        />
                    ))}
                </div>
            )}
        </motion.article>
    );
}

/**
 * Une distinction : son intitulé, son contexte (lien vers l'expérience, la formation ou le
 * projet) et son propre compteur de félicitations. Toute la carte sert de bouton « Féliciter ».
 */
function DistinctionSlide({ distinction }: { distinction: PublicDistinction }) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();
    const congrats = useCongratulations(
        distinction.congratulations,
        path(`distinctions/${distinction.id}/congratulations`),
    );
    const year = new Date(`${distinction.awarded_on}T12:00:00`).getFullYear();
    const context = distinction.context;

    return (
        <>
            <button
                type="button"
                className="pub-distinction__congrats"
                data-cursor-label={t.about.congrats}
                onClick={(event) => {
                    const box = event.currentTarget.getBoundingClientRect();
                    // Au clavier, le clic n'a pas de position : on part du centre de la carte.
                    const x = event.clientX || box.left + box.width / 2;
                    const y = event.clientY || box.top + box.height / 2;

                    burstConfetti(x, y);
                    congrats.add();
                }}
            >
                <span className="pub-fact__medium">{distinction.title}</span>
            </button>

            <p className="pub-distinction__context">
                {context ? (
                    <>
                        {context.type === 'project'
                            ? t.about.distinctionFor
                            : t.about.distinctionAt}{' '}
                        <a href={context.url}>{context.label}</a>
                    </>
                ) : (
                    distinction.issuer
                )}{' '}
                · {year}
            </p>

            <span className="pub-fact__count" aria-live="polite">
                {congrats.total === 0 ? (
                    t.about.congratsFirst
                ) : (
                    <>
                        <strong>{congrats.total.toLocaleString(locale)}</strong>{' '}
                        {t.about.congratsReceived(congrats.total)}
                    </>
                )}
            </span>
        </>
    );
}

/** Petits badges 🏆 des distinctions d'une expérience ou d'une formation. */
export function DistinctionBadges({
    distinctions = [],
}: {
    distinctions?: PublicDistinction[];
}) {
    if (distinctions.length === 0) {
        return null;
    }

    return (
        <span className="pub-award-badges">
            {distinctions.map((distinction) => (
                <span key={distinction.id} className="pub-award-badge">
                    <span aria-hidden="true">🏆</span> {distinction.title}
                </span>
            ))}
        </span>
    );
}
