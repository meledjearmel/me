import { AnimatePresence, motion } from 'framer-motion';
import { PartyPopper, X } from 'lucide-react';
import { useState } from 'react';
import { useCongratulations } from '@/hooks/use-congratulations';
import { useCelebration } from '@/lib/celebration';
import { burstConfetti } from '@/lib/confetti';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { SharedCelebration } from '@/types';
import { usePrefersReducedMotion } from '@/hooks/use-media-query';

/**
 * Bulle de la surprise, au-dessus d'Armi (en bas à gauche) : l'annonce, un
 * bouton pour féliciter (confettis et compteur partagé) et de quoi la fermer.
 */
export default function CelebrationBubble() {
    const { celebration, visible } = useCelebration();

    // Montée dès le tirage et jamais démontée ensuite : le compteur garde ses
    // clics en attente et la sortie peut s'animer.
    return celebration ? (
        <Bubble celebration={celebration} visible={visible} />
    ) : null;
}

function Bubble({
    celebration,
    visible,
}: {
    celebration: SharedCelebration;
    visible: boolean;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();
    const reduceMotion = usePrefersReducedMotion();
    const { engage, dismiss } = useCelebration();
    const congrats = useCongratulations(
        celebration.total,
        path(`celebrations/${celebration.id}/congratulations`),
    );
    const [thanked, setThanked] = useState(false);

    return (
        <AnimatePresence>
            {visible && (
                <motion.aside
                    className="pub-surprise"
                    role="status"
                    aria-live="polite"
                    style={{ transformOrigin: 'bottom left' }}
                    initial={
                        reduceMotion
                            ? { opacity: 0 }
                            : { opacity: 0, y: 16, scale: 0.85 }
                    }
                    animate={
                        reduceMotion
                            ? { opacity: 1 }
                            : { opacity: 1, y: 0, scale: 1 }
                    }
                    exit={
                        reduceMotion
                            ? { opacity: 0 }
                            : { opacity: 0, y: 10, scale: 0.94 }
                    }
                    transition={{ type: 'spring', stiffness: 380, damping: 26 }}
                    onPointerEnter={engage}
                    onFocus={engage}
                >
                    <button
                        type="button"
                        className="pub-surprise__close"
                        aria-label={t.celebration.close}
                        onClick={() => dismiss(true)}
                    >
                        <X size={16} aria-hidden="true" />
                    </button>

                    <p className="pub-surprise__message">
                        {celebration.message}
                    </p>

                    <div className="pub-surprise__actions">
                        <button
                            type="button"
                            className="pub-surprise__cta"
                            onClick={(event) => {
                                const box =
                                    event.currentTarget.getBoundingClientRect();
                                // Au clavier, le clic n'a pas de position : on part du centre du bouton.
                                const x =
                                    event.clientX || box.left + box.width / 2;
                                const y =
                                    event.clientY || box.top + box.height / 2;

                                engage();
                                burstConfetti(x, y);
                                congrats.add();
                                setThanked(true);
                            }}
                        >
                            <PartyPopper size={16} aria-hidden="true" />
                            {celebration.buttonLabel}
                        </button>

                        {congrats.total > 0 && (
                            <span className="pub-surprise__count">
                                <strong>
                                    {congrats.total.toLocaleString(locale)}
                                </strong>{' '}
                                {t.celebration.countLabel}
                            </span>
                        )}
                    </div>

                    {thanked && (
                        <p className="pub-surprise__thanks">
                            {t.celebration.thanks}
                        </p>
                    )}
                </motion.aside>
            )}
        </AnimatePresence>
    );
}
