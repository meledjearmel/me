import { usePage } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import type { PlaylistGenre, PlaylistTrack } from '@/types';

type AudioState = {
    playing: boolean;
    time: number;
    duration: number;
    volume: number;
    /** Registres jouables ; vide quand le site n'a que sa bande audio unique. */
    genres: PlaylistGenre[];
    currentTrack: PlaylistTrack;
    /** Le registre de la piste en cours (null en mode piste unique). */
    currentGenre: PlaylistGenre | null;
    hasQueue: boolean;
    toggle: () => void;
    seek: (ratio: number) => void;
    setVolume: (volume: number) => void;
    /** Lance une piste précise. */
    play: (trackId: number) => void;
    next: () => void;
    previous: () => void;
};

const AudioContext = createContext<AudioState | null>(null);

export const FALLBACK_TRACK = '/audio/journey.mp3';

/** Sous cette durée écoulée, « précédent » revient à la piste d'avant ; au-delà, il rembobine. */
const RESTART_THRESHOLD = 3;

/**
 * Fournit un lecteur audio unique, monté dans le layout persistant des pages
 * publiques : la musique continue de jouer quand on change de page.
 *
 * Avec des registres en base, la file d'attente est celle du registre de la
 * piste en cours (elle reboucle sur elle-même) ; sans registre, le lecteur
 * joue la bande audio du profil ou la piste par défaut, comme avant.
 */
export function AudioProvider({ children }: { children: ReactNode }) {
    const { musicUrl, playlist } = usePage<{
        musicUrl?: string | null;
        playlist?: PlaylistGenre[];
    }>().props;
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const resumeRef = useRef(false);
    const [playing, setPlaying] = useState(false);
    const [time, setTime] = useState(0);
    const [duration, setDuration] = useState(0);
    const [volume, setVolumeState] = useState(1);

    const genres = useMemo(() => playlist ?? [], [playlist]);
    const singleTrack = useMemo<PlaylistTrack>(
        () => ({
            id: 0,
            title: '',
            artist: null,
            url: musicUrl || FALLBACK_TRACK,
        }),
        [musicUrl],
    );
    const allTracks = useMemo(
        () => genres.flatMap((genre) => genre.tracks),
        [genres],
    );
    const [currentId, setCurrentId] = useState<number | null>(null);

    const currentTrack =
        allTracks.find((track) => track.id === currentId) ??
        allTracks[0] ??
        singleTrack;
    const currentGenre =
        genres.find((genre) =>
            genre.tracks.some((track) => track.id === currentTrack.id),
        ) ?? null;
    const queue = currentGenre?.tracks ?? [];

    // Rejoue la nouvelle piste si la précédente était en lecture (ou finie).
    useEffect(() => {
        const audio = audioRef.current;

        if (audio && resumeRef.current) {
            resumeRef.current = false;
            void audio.play().catch(() => undefined);
        }
    }, [currentTrack.url]);

    const selectTrack = useCallback(
        (track: PlaylistTrack, autoplay: boolean) => {
            resumeRef.current = autoplay;
            setTime(0);
            setCurrentId(track.id);
        },
        [],
    );

    const step = useCallback(
        (direction: 1 | -1, autoplay: boolean) => {
            if (queue.length < 2) {
                const audio = audioRef.current;

                if (audio) {
                    audio.currentTime = 0;

                    if (autoplay) {
                        void audio.play().catch(() => undefined);
                    }
                }

                return;
            }

            const index = queue.findIndex(
                (track) => track.id === currentTrack.id,
            );
            const target =
                queue[(index + direction + queue.length) % queue.length];

            selectTrack(target, autoplay);
        },
        [queue, currentTrack.id, selectTrack],
    );

    // Les écouteurs relisent toujours la dernière version de `step`.
    const stepRef = useRef(step);

    useEffect(() => {
        stepRef.current = step;
    }, [step]);

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
        const onEnded = () => stepRef.current(1, true);

        audio.addEventListener('play', onPlay);
        audio.addEventListener('pause', onPause);
        audio.addEventListener('timeupdate', onTime);
        audio.addEventListener('loadedmetadata', onMeta);
        audio.addEventListener('durationchange', onMeta);
        audio.addEventListener('ended', onEnded);

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
            audio.removeEventListener('ended', onEnded);
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

    const setVolume = (next: number) => {
        const clamped = Math.min(1, Math.max(0, next));

        if (audioRef.current) {
            audioRef.current.volume = clamped;
        }

        setVolumeState(clamped);
    };

    const play = (trackId: number) => {
        const audio = audioRef.current;
        const track = allTracks.find((candidate) => candidate.id === trackId);

        if (!track) {
            return;
        }

        if (track.id === currentTrack.id) {
            if (audio?.paused) {
                void audio.play().catch(() => undefined);
            }

            return;
        }

        selectTrack(track, true);
    };

    const next = () => step(1, !audioRef.current?.paused);

    const previous = () => {
        if (time > RESTART_THRESHOLD) {
            seek(0);

            return;
        }

        step(-1, !audioRef.current?.paused);
    };

    return (
        <AudioContext.Provider
            value={{
                playing,
                time,
                duration,
                volume,
                genres,
                currentTrack,
                currentGenre,
                hasQueue: queue.length > 1,
                toggle,
                seek,
                setVolume,
                play,
                next,
                previous,
            }}
        >
            {children}
            <audio
                ref={audioRef}
                src={currentTrack.url}
                preload="metadata"
                loop={genres.length === 0}
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
