import { AnimatePresence, motion } from 'framer-motion';
import { router, usePage } from '@inertiajs/react';
import {
    ArrowUp,
    CalendarDays,
    Handshake,
    MessageSquareQuote,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import EngageDialog from '@/components/public/engage-dialog';
import ReviewDialog from '@/components/public/review-dialog';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import { OPEN_REVIEW_EVENT, REVIEW_QUERY_PARAM } from '@/lib/review';
import type { ReviewContext } from '@/lib/review';

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
    const path = useLocalizedPath();
    const { bookingOpen } = usePage<{ bookingOpen?: boolean }>().props;
    const root = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const [reviewOpen, setReviewOpen] = useState(false);
    const [reviewContext, setReviewContext] = useState<ReviewContext>();
    const [engageOpen, setEngageOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > SCROLL_THRESHOLD);

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    // Un bouton d'une page peut demander l'ouverture de la fenêtre d'avis, avec un projet ou une
    // expérience prérempli ; le lien ?avis=1 (celui que j'envoie) l'ouvre dès l'arrivée.
    useEffect(() => {
        const openReview = (event: Event) => {
            setReviewContext(
                (event as CustomEvent<ReviewContext | undefined>).detail,
            );
            setReviewOpen(true);
        };

        if (
            new URL(window.location.href).searchParams.has(REVIEW_QUERY_PARAM)
        ) {
            setReviewOpen(true);
        }

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
            run: () => {
                setReviewContext(undefined);
                setReviewOpen(true);
            },
        },
        {
            key: 'engage',
            label: t.fab.engage,
            icon: <Handshake size={22} aria-hidden="true" />,
            run: () => setEngageOpen(true),
        },
        ...(bookingOpen
            ? [
                  {
                      key: 'booking',
                      label: t.booking.cta,
                      icon: <CalendarDays size={22} aria-hidden="true" />,
                      run: () => router.visit(path('appointments')),
                  },
              ]
            : []),
        ...(scrolled
            ? [
                  {
                      key: 'top',
                      label: t.fab.top,
                      icon: <ArrowUp size={22} aria-hidden="true" />,
                      run: () =>
                          window.scrollTo({ top: 0, behavior: 'smooth' }),
                  },
              ]
            : []),
    ];

    // Les actions se répartissent sur un quart de cercle, de la gauche (180°) vers le haut (90°).
    const position = (index: number) => {
        const angle =
            ((actions.length === 1
                ? 135
                : 180 - (90 / (actions.length - 1)) * index) *
                Math.PI) /
            180;

        // Au-delà de trois actions, l'éventail s'élargit pour que les pastilles ne se chevauchent pas.
        const radius = RADIUS + Math.max(0, actions.length - 3) * 30;

        return { x: Math.cos(angle) * radius, y: -Math.sin(angle) * radius };
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
                                    initial={{
                                        x: 0,
                                        y: 0,
                                        scale: 0.3,
                                        opacity: 0,
                                    }}
                                    animate={{ x, y, scale: 1, opacity: 1 }}
                                    exit={{
                                        x: 0,
                                        y: 0,
                                        scale: 0.3,
                                        opacity: 0,
                                    }}
                                    transition={{
                                        type: 'spring',
                                        stiffness: 420,
                                        damping: 26,
                                        delay: index * 0.05,
                                    }}
                                    onClick={() => {
                                        setOpen(false);
                                        action.run();
                                    }}
                                >
                                    <span className="pub-fab__pill">
                                        <span className="pub-fab__glyph">
                                            {action.icon}
                                        </span>
                                        <span className="pub-fab__label">
                                            {action.label}
                                        </span>
                                    </span>
                                </motion.button>
                            );
                        })}
                </AnimatePresence>

                <motion.button
                    type="button"
                    className="pub-fab__main"
                    aria-expanded={open}
                    aria-haspopup="true"
                    aria-label={t.fab.label}
                    layout
                    style={{ borderRadius: 999 }}
                    transition={{ type: 'spring', stiffness: 380, damping: 30 }}
                    whileTap={{ scale: 0.94 }}
                    onClick={() => setOpen((current) => !current)}
                >
                    <AnimatePresence mode="popLayout" initial={false}>
                        {open ? (
                            <motion.span
                                key="icon"
                                className="pub-fab__icon"
                                aria-hidden="true"
                                initial={{
                                    opacity: 0,
                                    rotate: -90,
                                    scale: 0.4,
                                }}
                                animate={{ opacity: 1, rotate: 0, scale: 1 }}
                                exit={{ opacity: 0, rotate: 90, scale: 0.4 }}
                                transition={{ duration: 0.25 }}
                            >
                                <X size={26} />
                            </motion.span>
                        ) : (
                            <motion.span
                                key="title"
                                className="pub-fab__title"
                                initial={{ opacity: 0, x: -12 }}
                                animate={{ opacity: 1, x: 0 }}
                                exit={{ opacity: 0, x: 12 }}
                                transition={{ duration: 0.25 }}
                            >
                                {t.fab.label}
                            </motion.span>
                        )}
                    </AnimatePresence>
                </motion.button>
            </div>

            <ReviewDialog
                open={reviewOpen}
                onOpenChange={setReviewOpen}
                context={reviewContext}
            />
            <EngageDialog open={engageOpen} onOpenChange={setEngageOpen} />
        </>
    );
}
