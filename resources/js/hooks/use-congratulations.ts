import { useCallback, useEffect, useRef, useState } from 'react';

/** Délai de regroupement des clics avant l'envoi au serveur (30 requêtes/min au maximum, sous la limite de débit). */
const FLUSH_DELAY = 2000;
/** Doit rester aligné sur CongratulationController::MAX_PER_REQUEST. */
const MAX_PER_REQUEST = 25;

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Compteur de félicitations partagé entre tous les visiteurs.
 *
 * Le clic est instantané (le total local augmente tout de suite) ; les clics
 * sont regroupés et envoyés en une seule requête, ce qui ménage le serveur et
 * reste fluide même si on clique vingt fois de suite. Le total renvoyé par le
 * serveur remplace ensuite l'estimation locale.
 */
export function useCongratulations(initialTotal: number, endpoint: string) {
    const [total, setTotal] = useState(initialTotal);
    const pending = useRef(0);
    const timer = useRef<number | null>(null);

    const flush = useCallback(async () => {
        timer.current = null;

        while (pending.current > 0) {
            const count = Math.min(pending.current, MAX_PER_REQUEST);

            pending.current -= count;

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ count }),
                });

                if (!response.ok) {
                    throw new Error(String(response.status));
                }

                const data: { total: number } = await response.json();

                setTotal(data.total + pending.current);
            } catch {
                // Envoi refusé (limite de débit, réseau) : on retire l'estimation locale.
                setTotal((current) => Math.max(0, current - count));
            }
        }
    }, [endpoint]);

    const add = useCallback(() => {
        pending.current += 1;
        setTotal((current) => current + 1);

        if (timer.current === null) {
            timer.current = window.setTimeout(flush, FLUSH_DELAY);
        }
    }, [flush]);

    useEffect(() => {
        const sendRemaining = () => {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
            }

            void flush();
        };

        window.addEventListener('pagehide', sendRemaining);

        return () => {
            window.removeEventListener('pagehide', sendRemaining);
            sendRemaining();
        };
    }, [flush]);

    return { total, add };
}
