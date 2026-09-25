import { useEffect, type RefObject } from 'react';

/**
 * Effet d'éloignement : en descendant, le contenu du hero recule (il rétrécit
 * et s'estompe) pendant que la suite vient le recouvrir. Comme la référence,
 * la progression va de 0 à 1 sur 80 % de la hauteur du hero, avec une
 * décélération (cubique) : le mouvement est rapide au début puis se calme.
 *
 * Écrit la variable CSS `--pub-away` (0 → 1) sur l'élément ; c'est le CSS qui
 * fait le reste, sans réafficher React à chaque défilement.
 */
export function useScrollAway(ref: RefObject<HTMLElement | null>): void {
    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        let frame = 0;
        let last = -1;

        const update = () => {
            frame = 0;

            const progress = Math.min(
                1,
                Math.max(0, window.scrollY / (element.offsetHeight * 0.8)),
            );
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = Math.round(eased * 1000) / 1000;

            // Inutile de réécrire la même valeur : la plupart du temps, on est en haut.
            if (value !== last) {
                element.style.setProperty('--pub-away', String(value));
                last = value;
            }
        };

        const schedule = () => {
            if (!frame) {
                frame = requestAnimationFrame(update);
            }
        };

        update();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        return () => {
            cancelAnimationFrame(frame);
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
        };
    }, [ref]);
}
