import { AnimatePresence, motion } from 'framer-motion';
import { PartyPopper, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useCongratulations } from '@/hooks/use-congratulations';
import { useCelebration } from '@/lib/celebration';
import { burstConfetti } from '@/lib/confetti';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import { playSoundEffect, preloadSoundEffects } from '@/lib/sound-effects';
import type { SharedCelebration } from '@/types';
import { usePrefersReducedMotion } from '@/hooks/use-media-query';

/** Au-delà, la première phrase n'est plus une accroche : tout reste en texte. */
const MAX_TITLE_LENGTH = 40;

/**
 * Sépare l'accroche du reste (« Le saviez-vous ? » / « Armel a été… ») pour
 * l'afficher en titre, comme les cartes de compétences.
 */
function splitMessage(message: string): { title: string | null; body: string } {
    const match = message.match(/^(.{2,}?[?!.])\s+(.+)$/s);

    if (!match || match[1].length > MAX_TITLE_LENGTH) {
        return { title: null, body: message };
    }

    return { title: match[1], body: match[2] };
}

/** Temps pendant lequel Armi « écrit » avant que son message n'apparaisse. */
const TYPING_DURATION = 700;

/**
 * Bulle de parole d'Armi (en bas à gauche) : elle sort de l'avatar avec un
 * « pop » et montre Armi en train d'écrire, puis l'annonce arrive avec un
 * « whoosh », un bouton pour
 * féliciter (confettis et compteur partagé) et de quoi la fermer.
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
    const [typing, setTyping] = useState(true);
    const { title, body } = splitMessage(celebration.message);

    // Monté dès le tirage, avant l'apparition : les sons ont le temps de charger.
    useEffect(() => preloadSoundEffects(), []);

    useEffect(() => {
        if (!visible) {
            return;
        }

        // Sans la phase « Armi écrit », la surprise arrive directement.
        if (reduceMotion) {
            setTyping(false);
            playSoundEffect('whoosh');

            return;
        }

        setTyping(true);
        playSoundEffect('bubblePop');

        const timer = window.setTimeout(() => {
            setTyping(false);
            playSoundEffect('whoosh');
        }, TYPING_DURATION);

        return () => window.clearTimeout(timer);
    }, [visible, reduceMotion]);

    return (
        <AnimatePresence>
            {visible && (
                <motion.aside
                    // `layout` : la bulle grandit des trois points au message.
                    layout={!reduceMotion}
                    className={`pub-surprise${typing ? ' is-typing' : ''}`}
                    role="status"
                    aria-live="polite"
                    style={{ transformOrigin: 'bottom left' }}
                    initial={
                        reduceMotion
                            ? { opacity: 0 }
                            : { opacity: 0, x: -12, y: 24, scale: 0.2 }
                    }
                    animate={
                        reduceMotion
                            ? { opacity: 1 }
                            : { opacity: 1, x: 0, y: 0, scale: 1 }
                    }
                    exit={
                        reduceMotion
                            ? { opacity: 0 }
                            : { opacity: 0, x: -8, y: 16, scale: 0.4 }
                    }
                    transition={{ type: 'spring', stiffness: 420, damping: 24 }}
                    onPointerEnter={engage}
                    onPointerMove={(event) => {
                        const box = event.currentTarget.getBoundingClientRect();

                        // Halo et contour lumineux sous le pointeur, comme les cartes de compétences.
                        event.currentTarget.style.setProperty(
                            '--mx',
                            `${event.clientX - box.left}px`,
                        );
                        event.currentTarget.style.setProperty(
                            '--my',
                            `${event.clientY - box.top}px`,
                        );
                        event.currentTarget.style.setProperty('--glow', '1');
                    }}
                    onPointerLeave={(event) =>
                        event.currentTarget.style.setProperty('--glow', '0')
                    }
                    onFocus={engage}
                >
                    <span className="pub-surprise__glow" aria-hidden="true" />
                    <span className="pub-surprise__tail" aria-hidden="true" />

                    <button
                        type="button"
                        className="pub-surprise__close"
                        aria-label={t.celebration.close}
                        onClick={() => dismiss(true)}
                    >
                        <X size={16} aria-hidden="true" />
                    </button>

                    {typing ? (
                        <p className="pub-surprise__typing" aria-label="…">
                            <span />
                            <span />
                            <span />
                        </p>
                    ) : (
                        <motion.div
                            className="pub-surprise__body"
                            initial={reduceMotion ? false : { opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ duration: 0.25 }}
                        >
                            {title && (
                                <p className="pub-surprise__title">{title}</p>
                            )}
                            <p className="pub-surprise__message">{body}</p>

                            <div className="pub-surprise__actions">
                                <button
                                    type="button"
                                    className="pub-surprise__cta"
                                    onClick={(event) => {
                                        const box =
                                            event.currentTarget.getBoundingClientRect();
                                        // Au clavier, le clic n'a pas de position : on part du centre du bouton.
                                        const x =
                                            event.clientX ||
                                            box.left + box.width / 2;
                                        const y =
                                            event.clientY ||
                                            box.top + box.height / 2;

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
                                            {congrats.total.toLocaleString(
                                                locale,
                                            )}
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
                        </motion.div>
                    )}
                </motion.aside>
            )}
        </AnimatePresence>
    );
}
