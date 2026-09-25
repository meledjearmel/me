import { useEffect, type RefObject } from 'react';

/**
 * Épingle une section par le BAS (position: sticky) : elle défile normalement
 * puis reste en place quand son bas atteint le bas de l'écran, pendant que la
 * section suivante glisse par-dessus. Comme la référence, `top` vaut donc
 * « hauteur de l'écran - hauteur de la section » (négatif si elle est plus
 * haute que l'écran). Désactivé sous 1101px : le mobile suit le flux normal.
 */
export function usePinAtBottom(ref: RefObject<HTMLElement | null>): void {
    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        const desktop = window.matchMedia('(min-width: 1101px)');

        const update = () => {
            if (!desktop.matches) {
                element.style.removeProperty('position');
                element.style.removeProperty('top');

                return;
            }

            const top = Math.min(0, window.innerHeight - element.offsetHeight);

            element.style.position = 'sticky';
            element.style.top = `${top}px`;
        };

        update();

        const observer = new ResizeObserver(update);
        observer.observe(element);
        window.addEventListener('resize', update);

        return () => {
            observer.disconnect();
            window.removeEventListener('resize', update);
            element.style.removeProperty('position');
            element.style.removeProperty('top');
        };
    }, [ref]);
}
