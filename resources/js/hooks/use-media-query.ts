import { useCallback, useSyncExternalStore } from 'react';

/**
 * Suit une media query. Côté serveur (et à l'hydratation) elle vaut faux, pour
 * que le premier rendu soit identique à celui du serveur.
 */
export function useMediaQuery(query: string): boolean {
    const subscribe = useCallback(
        (notify: () => void) => {
            const list = window.matchMedia(query);

            list.addEventListener('change', notify);

            return () => list.removeEventListener('change', notify);
        },
        [query],
    );

    return useSyncExternalStore(
        subscribe,
        () => window.matchMedia(query).matches,
        () => false,
    );
}
