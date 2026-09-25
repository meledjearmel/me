import { AnimatePresence, motion } from 'framer-motion';
import { ArrowUp, Handshake, MessageSquareQuote, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import EngageDialog from '@/components/public/engage-dialog';
import ReviewDialog from '@/components/public/review-dialog';
import { useTranslations } from '@/lib/i18n';

/** Événement qui ouvre la fenêtre « Laisser un avis » depuis n'importe où dans le site. */
export const OPEN_REVIEW_EVENT = 'pub:open-review';

/** Distance à partir de laquelle « Remonter » apparaît. */
const SCROLL_THRESHOLD = 480;
/** Rayon de l'éventail autour du bouton principal. */
const RADIUS = 92;

type Action = {
    key: string;
    label: string;
    icon: ReactNode;
    run: () => void;
};

/**
 * Bouton d'action flottant (en bas à droite). Au clic, ses actions s'ouvrent en
 * éventail autour de lui : laisser un avis, collaborer (projet freelance ou
 * recrutement) et, une fois la page défilée, remonter en haut.
 */
export default function ActionMenu() {
    const t = useTranslations();
    const root = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const [reviewOpen, setReviewOpen] = useState(false);
    const [engageOpen, setEngageOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > SCROLL_THRESHOLD);

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    // Un bouton d'une page (ex. la page des avis) peut demander l'ouverture de la fenêtre d'avis.
    useEffect(() => {
        const openReview = () => setReviewOpen(true);

        window.addEventListener(OPEN_REVIEW_EVENT, openReview);

        return () => window.removeEventListener(OPEN_REVIEW_EVENT, openReview);
    }, []);

    // Un clic à l'extérieur ou Échap referme l'éventail.
    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: PointerEvent) => {
            if (root.current && !root.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const actions: Action[] = [
        {
            key: 'review',
            label: t.fab.review,
            icon: <MessageSquareQuote size={22} aria-hidden="true" />,
            run: () => setReviewOpen(true),
        },
        {
            key: 'engage',
            label: t.fab.engage,
            icon: <Handshake size={22} aria-hidden="true" />,
            run: () => setEngageOpen(true),
        },
        ...(scrolled
            ? [
                  {
                      key: 'top',
                      label: t.fab.top,
                      icon: <ArrowUp size={22} aria-hidden="true" />,
                      run: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
                  },
              ]
            : []),
    ];

    // Les actions se répartissent sur un quart de cercle, de la gauche (180°) vers le haut (90°).
    const position = (index: number) => {
        const angle = ((actions.length === 1 ? 135 : 180 - (90 / (actions.length - 1)) * index) * Math.PI) / 180;

        return { x: Math.cos(angle) * RADIUS, y: -Math.sin(angle) * RADIUS };
    };

    return (
        <>
            <div ref={root} className={`pub-fab${open ? ' is-open' : ''}`}>
                <AnimatePresence>
                    {open &&
                        actions.map((action, index) => {
                            const { x, y } = position(index);

                            return (
                                <motion.button
                                    key={action.key}
                                    type="button"
                                    className="pub-fab__item"
                                    aria-label={action.label}
                                    initial={{ x: 0, y: 0, scale: 0.3, opacity: 0 }}
                                    animate={{ x, y, scale: 1, opacity: 1 }}
                                    exit={{ x: 0, y: 0, scale: 0.3, opacity: 0 }}
                                    transition={{ type: 'spring', stiffness: 420, damping: 26, delay: index * 0.05 }}
                                    onClick={() => {
                                        setOpen(false);
                                        action.run();
                                    }}
                                >
                                    {action.icon}
                                    <span className="pub-fab__label">{action.label}</span>
                                </motion.button>
                            );
                        })}
                </AnimatePresence>

                <button
                    type="button"
                    className="pub-fab__main"
                    aria-expanded={open}
                    aria-haspopup="true"
                    aria-label={t.fab.label}
                    onClick={() => setOpen((current) => !current)}
                >
                    <Plus size={28} aria-hidden="true" />
                    <span className="pub-fab__tip" aria-hidden="true">
                        {t.fab.label}
                    </span>
                </button>
            </div>

            <ReviewDialog open={reviewOpen} onOpenChange={setReviewOpen} />
            <EngageDialog open={engageOpen} onOpenChange={setEngageOpen} />
        </>
    );
}
