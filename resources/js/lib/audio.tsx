import { usePage } from '@inertiajs/react';
import {
    createContext,
    useContext,
    useEffect,
    useRef,
    useState,
    type ReactNode,
} from 'react';

type AudioState = {
    playing: boolean;
    time: number;
    duration: number;
    toggle: () => void;
    seek: (ratio: number) => void;
};

const AudioContext = createContext<AudioState | null>(null);

export const FALLBACK_TRACK = '/audio/journey.mp3';

/**
 * Fournit un lecteur audio unique, monté dans le layout persistant des pages
 * publiques : la musique continue de jouer quand on change de page.
 */
export function AudioProvider({ children }: { children: ReactNode }) {
    const { musicUrl } = usePage<{ musicUrl?: string | null }>().props;
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const [playing, setPlaying] = useState(false);
    const [time, setTime] = useState(0);
    const [duration, setDuration] = useState(0);

    useEffect(() => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        const onPlay = () => setPlaying(true);
        const onPause = () => setPlaying(false);
        const onTime = () => setTime(audio.currentTime);
        const onMeta = () =>
            setDuration(Number.isFinite(audio.duration) ? audio.duration : 0);

        audio.addEventListener('play', onPlay);
        audio.addEventListener('pause', onPause);
        audio.addEventListener('timeupdate', onTime);
        audio.addEventListener('loadedmetadata', onMeta);
        audio.addEventListener('durationchange', onMeta);

        // Le <audio> est rendu par le serveur : ses métadonnées peuvent être
        // déjà chargées avant l'hydratation, donc l'événement est déjà passé.
        if (audio.readyState >= HTMLMediaElement.HAVE_METADATA) {
            onMeta();
        }

        return () => {
            audio.removeEventListener('play', onPlay);
            audio.removeEventListener('pause', onPause);
            audio.removeEventListener('timeupdate', onTime);
            audio.removeEventListener('loadedmetadata', onMeta);
            audio.removeEventListener('durationchange', onMeta);
        };
    }, []);

    const toggle = () => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        if (audio.paused) {
            void audio.play().catch(() => undefined);
        } else {
            audio.pause();
        }
    };

    const seek = (ratio: number) => {
        const audio = audioRef.current;

        if (audio && audio.duration) {
            audio.currentTime =
                Math.min(1, Math.max(0, ratio)) * audio.duration;
        }
    };

    return (
        <AudioContext.Provider
            value={{ playing, time, duration, toggle, seek }}
        >
            {children}
            <audio
                ref={audioRef}
                src={musicUrl || FALLBACK_TRACK}
                preload="metadata"
                loop
            />
        </AudioContext.Provider>
    );
}

export function useAudioPlayer(): AudioState {
    const context = useContext(AudioContext);

    if (!context) {
        throw new Error('useAudioPlayer doit être utilisé dans AudioProvider.');
    }

    return context;
}

export function formatTime(seconds: number): string {
    const safe = Number.isFinite(seconds) ? Math.max(0, seconds) : 0;
    const minutes = Math.floor(safe / 60);
    const rest = Math.floor(safe % 60);

    return `${minutes}:${rest.toString().padStart(2, '0')}`;
}
