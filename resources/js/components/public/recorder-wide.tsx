import { motion } from 'framer-motion';
import { Minimize2, SkipBack, SkipForward, Volume2 } from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent } from 'react';
import PlayerSlider from '@/components/public/player-slider';
import Disk from '@/components/public/disk';
import { formatTime, useAudioPlayer } from '@/lib/audio';
import { useTranslations } from '@/lib/i18n';

/**
 * Mode wide du lecteur : registres en onglets, liste des pistes, précédent /
 * suivant, volume. Au clavier : ↑ ↓ choisissent une piste, ← → changent de
 * registre, Entrée la lance, Espace lecture/pause, Échap referme.
 */
export default function RecorderWide({ onClose }: { onClose: () => void }) {
    const t = useTranslations();
    const {
        playing,
        time,
        duration,
        volume,
        genres,
        currentTrack,
        currentGenre,
        toggle,
        seek,
        setVolume,
        play,
        next,
        previous,
    } = useAudioPlayer();
    const rootRef = useRef<HTMLDivElement>(null);
    const [tabId, setTabId] = useState(currentGenre?.id ?? genres[0]?.id);
    const tab = genres.find((genre) => genre.id === tabId) ?? genres[0];
    const [cursor, setCursor] = useState(() =>
        Math.max(
            0,
            tab.tracks.findIndex((track) => track.id === currentTrack.id),
        ),
    );

    useEffect(() => {
        rootRef.current?.focus();
    }, []);

    const openTab = (index: number) => {
        const target = genres[(index + genres.length) % genres.length];

        setTabId(target.id);
        setCursor(0);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        const interactive =
            event.target instanceof HTMLButtonElement ||
            event.target instanceof HTMLInputElement ||
            (event.target as HTMLElement).getAttribute('role') === 'slider';

        switch (event.key) {
            case 'Escape':
                onClose();
                break;
            case 'ArrowDown':
                event.preventDefault();
                setCursor((index) => Math.min(index + 1, tab.tracks.length - 1));
                break;
            case 'ArrowUp':
                event.preventDefault();
                setCursor((index) => Math.max(index - 1, 0));
                break;
            case 'ArrowRight':
                if (!interactive) {
                    openTab(genres.indexOf(tab) + 1);
                }

                break;
            case 'ArrowLeft':
                if (!interactive) {
                    openTab(genres.indexOf(tab) - 1);
                }

                break;
            case 'Enter':
                if (!interactive) {
                    play(tab.tracks[cursor]?.id);
                }

                break;
            case ' ':
                if (!interactive) {
                    event.preventDefault();
                    toggle();
                }

                break;
        }
    };

    const title = currentTrack.title || t.hero.playerTitle;

    return (
        <motion.div
            ref={rootRef}
            className="pub-wide"
            role="region"
            aria-label={t.hero.playerTitle}
            tabIndex={-1}
            onKeyDown={onKeyDown}
            style={{ y: '-50%' }}
            initial={{ opacity: 0, scale: 0.86 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.9 }}
            transition={{ type: 'spring', stiffness: 380, damping: 32 }}
        >
            <button
                type="button"
                className="pub-wide__close"
                aria-label={t.hero.collapsePlayer}
                onClick={onClose}
            >
                <Minimize2 aria-hidden="true" />
            </button>

            <div className="pub-wide__deck">
                <Disk spinning={playing} />

                <div className="pub-wide__now">
                    <p className="pub-wide__eyebrow">{t.hero.nowPlaying}</p>
                    <p className="pub-wide__title">{title}</p>
                    {currentTrack.artist && (
                        <p className="pub-wide__artist">{currentTrack.artist}</p>
                    )}
                </div>

                <div className="pub-wide__progress">
                    <PlayerSlider
                        time={time}
                        duration={duration}
                        label={title}
                        onSeek={seek}
                    />
                    <div className="pub-recorder__times">
                        <span>{formatTime(time)}</span>
                        <span>{formatTime(duration)}</span>
                    </div>
                </div>

                <div className="pub-wide__controls">
                    <button
                        type="button"
                        className="pub-wide__skip"
                        aria-label={t.hero.previousTrack}
                        onClick={previous}
                    >
                        <SkipBack aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        className="pub-play"
                        aria-label={playing ? t.hero.pause : t.hero.play}
                        onClick={toggle}
                    >
                        {playing ? (
                            <span className="pub-play__bars" aria-hidden="true">
                                <span />
                                <span />
                            </span>
                        ) : (
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M8.5 5.5v13l10.5-6.5z"
                                    fill="currentColor"
                                />
                            </svg>
                        )}
                    </button>
                    <button
                        type="button"
                        className="pub-wide__skip"
                        aria-label={t.hero.nextTrack}
                        onClick={next}
                    >
                        <SkipForward aria-hidden="true" />
                    </button>
                </div>

                <label className="pub-wide__volume">
                    <Volume2 aria-hidden="true" />
                    <input
                        type="range"
                        min={0}
                        max={1}
                        step={0.01}
                        value={volume}
                        aria-label={t.hero.volume}
                        onChange={(event) =>
                            setVolume(Number(event.target.value))
                        }
                    />
                </label>
            </div>

            <div className="pub-wide__screen">
                <div
                    className="pub-wide__tabs"
                    role="tablist"
                    aria-label={t.hero.registers}
                >
                    {genres.map((genre, index) => (
                        <button
                            key={genre.id}
                            type="button"
                            role="tab"
                            aria-selected={genre.id === tab.id}
                            className={`pub-wide__tab${genre.id === tab.id ? ' is-active' : ''}`}
                            onClick={() => openTab(index)}
                        >
                            {genre.label}
                        </button>
                    ))}
                </div>

                <p className="pub-wide__count">
                    /{tab.key} · {tab.tracks.length} {t.hero.tracksCount}
                </p>

                <ol className="pub-wide__list" role="listbox">
                    {tab.tracks.map((track, index) => {
                        const isCurrent = track.id === currentTrack.id;

                        return (
                            <li
                                key={track.id}
                                role="option"
                                aria-selected={isCurrent}
                                className={`pub-wide__track${
                                    index === cursor ? ' is-cursor' : ''
                                }${isCurrent ? ' is-current' : ''}`}
                                onMouseEnter={() => setCursor(index)}
                                onClick={() => play(track.id)}
                            >
                                <span className="pub-wide__index">
                                    {isCurrent && playing
                                        ? '♪'
                                        : String(index + 1).padStart(2, '0')}
                                </span>
                                <span className="pub-wide__name">
                                    {track.title}
                                </span>
                                <span className="pub-wide__by">
                                    {track.artist}
                                </span>
                            </li>
                        );
                    })}
                </ol>
            </div>
        </motion.div>
    );
}
