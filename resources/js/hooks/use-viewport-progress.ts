import { useMotionValue, type MotionValue } from 'framer-motion';
import { useEffect, type RefObject } from 'react';

/**
 * Progression (0 → 1) d'un élément pendant qu'il traverse la fenêtre, calculée
 * directement à partir de sa position à l'écran à chaque scroll.
 *
 * Remplace `useScroll` de framer-motion pour les révélations au scroll : ici la
 * position est relue à chaque événement (scroll, redimensionnement, chargement
 * des polices et images), donc elle reste juste même avec des sections épinglées
 * (sticky), des décalages de mise en page ou un navigateur qui suit mal le
 * défilement.
 *
 * `range` renvoie [haut à 0 %, haut à 100 %] : la position du haut de
 * l'élément (en px depuis le haut de la fenêtre) quand la progression vaut 0,
 * puis quand elle vaut 1.
 */
export function useViewportProgress(
    ref: RefObject<HTMLElement | null>,
    range: (height: number, viewportHeight: number) => [number, number],
): MotionValue<number> {
    const progress = useMotionValue(0);

    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        let frame = 0;

        const measure = () => {
            frame = 0;

            const rect = element.getBoundingClientRect();
            const viewport = window.innerHeight;
            const [atStart, atEnd] = range(rect.height, viewport);
            const span = atStart - atEnd;
            const value = span === 0 ? 1 : (atStart - rect.top) / span;

            progress.set(Math.min(1, Math.max(0, value)));
        };

        const schedule = () => {
            if (!frame) {
                frame = requestAnimationFrame(measure);
            }
        };

        measure();

        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        window.addEventListener('load', schedule);

        const observer = new ResizeObserver(schedule);
        observer.observe(element);
        observer.observe(document.body);

        // Les polices et images arrivent après le premier rendu : on remesure
        // pendant la première seconde pour ne pas rester sur une mesure périmée.
        const timers = [150, 500, 1000].map((delay) =>
            window.setTimeout(schedule, delay),
        );

        return () => {
            cancelAnimationFrame(frame);
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
            window.removeEventListener('load', schedule);
            observer.disconnect();
            timers.forEach((timer) => window.clearTimeout(timer));
        };
    }, [ref, range, progress]);

    return progress;
}
