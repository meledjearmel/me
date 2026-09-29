import { usePage } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import type { SharedCelebration } from '@/types';

/** Fermée à la main, la même surprise ne revient pas avant une semaine. */
const SNOOZE_MS = 7 * 24 * 60 * 60 * 1000;
const SESSION_KEY = 'pub-celebration-rolled';
const SNOOZE_KEY = 'pub-celebration-snoozed';

type CelebrationState = {
    celebration: SharedCelebration | null;
    visible: boolean;
    /** Le visiteur interagit : on annule le départ automatique. */
    engage: () => void;
    /** `snooze` : fermée volontairement, on ne la reproposera pas avant une semaine. */
    dismiss: (snooze?: boolean) => void;
};

const CelebrationContext = createContext<CelebrationState | null>(null);

function readSnoozed(): Record<string, number> {
    try {
        return JSON.parse(localStorage.getItem(SNOOZE_KEY) ?? '{}');
    } catch {
        return {};
    }
}

function snooze(id: number): void {
    try {
        localStorage.setItem(
            SNOOZE_KEY,
            JSON.stringify({ ...readSnoozed(), [id]: Date.now() }),
        );
    } catch {
        // Stockage indisponible (navigation privée) : tant pis, rien de grave.
    }
}

/** Le tirage n'a lieu qu'une fois par session ; renvoie false s'il a déjà eu lieu. */
function claimSessionRoll(): boolean {
    try {
        if (sessionStorage.getItem(SESSION_KEY)) {
            return false;
        }

        sessionStorage.setItem(SESSION_KEY, '1');
    } catch {
        // Sans stockage, on tire quand même : au pire une surprise par chargement.
    }

    return true;
}

/**
 * Surprise du site public : de temps en temps, Armi (l'avatar du chat) se
 * réveille pour annoncer une bonne nouvelle configurée dans l'admin.
 *
 * Vit dans le layout persistant pour survivre aux changements de page : le
 * tirage (une fois par session, avec la chance réglée dans l'admin), le délai
 * d'apparition et le compteur ne repartent pas de zéro à chaque navigation.
 * Jamais sur la page contact. `?surprise` dans l'URL la force, pour la tester.
 */
export function CelebrationProvider({ children }: { children: ReactNode }) {
    const { props, url } = usePage<{
        celebration?: SharedCelebration | null;
    }>();
    const initial = useRef(props.celebration ?? null);
    const [celebration, setCelebration] = useState<SharedCelebration | null>(
        null,
    );
    const [done, setDone] = useState(false);
    const [engaged, setEngaged] = useState(false);
    const onContactPage = /\/contact(?:[/?#]|$)/.test(url);
    const visible = celebration !== null && !done && !onContactPage;

    useEffect(() => {
        const candidate = initial.current;

        if (!candidate) {
            return;
        }

        const forced = new URLSearchParams(window.location.search).has(
            'surprise',
        );

        if (!forced) {
            const snoozedAt = readSnoozed()[candidate.id];

            if (
                !claimSessionRoll() ||
                (snoozedAt && Date.now() - snoozedAt < SNOOZE_MS) ||
                Math.random() >= candidate.chance
            ) {
                return;
            }
        }

        const timer = window.setTimeout(
            () => setCelebration(candidate),
            forced ? 1200 : candidate.delaySeconds * 1000,
        );

        return () => window.clearTimeout(timer);
    }, []);

    useEffect(() => {
        if (!visible || engaged) {
            return;
        }

        // Sans interaction, la bulle repart d'elle-même.
        const timer = window.setTimeout(
            () => setDone(true),
            (celebration?.displaySeconds ?? 15) * 1000,
        );

        return () => window.clearTimeout(timer);
    }, [visible, engaged, celebration]);

    const engage = useCallback(() => setEngaged(true), []);
    const dismiss = useCallback(
        (snoozeIt = false) => {
            if (snoozeIt && celebration) {
                snooze(celebration.id);
            }

            setDone(true);
        },
        [celebration],
    );

    return (
        <CelebrationContext.Provider
            value={{ celebration, visible, engage, dismiss }}
        >
            {children}
        </CelebrationContext.Provider>
    );
}

export function useCelebration(): CelebrationState {
    const context = useContext(CelebrationContext);

    if (!context) {
        throw new Error(
            'useCelebration doit être utilisé dans CelebrationProvider.',
        );
    }

    return context;
}
