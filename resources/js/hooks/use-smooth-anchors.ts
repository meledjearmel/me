import { useEffect } from 'react';

/**
 * Les liens d'ancre de la page (#section) défilent en douceur au lieu de sauter.
 * Un clic normal sur un lien vers un élément de la page courante est intercepté ;
 * tout le reste (autre page, nouvel onglet, touches Ctrl/Cmd...) garde son
 * comportement. Sans effet si l'utilisateur préfère réduire les animations
 * (le saut est alors instantané, mais l'URL est quand même mise à jour).
 */
export function useSmoothAnchors(): void {
    useEffect(() => {
        const onClick = (event: MouseEvent) => {
            if (
                event.defaultPrevented ||
                event.button !== 0 ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey
            ) {
                return;
            }

            const link =
                event.target instanceof Element
                    ? event.target.closest<HTMLAnchorElement>('a[href*="#"]')
                    : null;

            if (!link || link.target === '_blank' || link.hasAttribute('download')) {
                return;
            }

            const url = new URL(link.href, window.location.href);

            if (
                url.origin !== window.location.origin ||
                url.pathname !== window.location.pathname ||
                url.hash.length < 2
            ) {
                return;
            }

            const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));

            if (!target) {
                return;
            }

            event.preventDefault();

            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            target.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'start',
            });
            window.history.pushState(null, '', url.hash);
        };

        document.addEventListener('click', onClick);

        return () => document.removeEventListener('click', onClick);
    }, []);
}
