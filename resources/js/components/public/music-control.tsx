import { SkipBack, SkipForward } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import MusicButton from '@/components/public/music-button';
import { useMediaQuery } from '@/hooks/use-media-query';
import { useAudioPlayer } from '@/lib/audio';
import { useTranslations } from '@/lib/i18n';

/** Grand écran avec un vrai pointeur : le mini lecteur s'ouvre au survol. */
const MINI_PLAYER_QUERY = '(min-width: 1024px) and (hover: hover)';

const CLOSE_DELAY = 180;

/**
 * Le bouton musique du header ; sur grand écran, le survol (ou le focus
 * clavier) ouvre un mini lecteur sous le bouton.
 */
export default function MusicControl() {
    const t = useTranslations();
    const { available, playing, currentTrack, toggle, next, previous } =
        useAudioPlayer();
    const enabled = useMediaQuery(MINI_PLAYER_QUERY);
    const [open, setOpen] = useState(false);
    const closeTimer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(closeTimer.current), []);

    const show = () => {
        window.clearTimeout(closeTimer.current);
        setOpen(true);
    };

    const hide = () => {
        window.clearTimeout(closeTimer.current);
        closeTimer.current = window.setTimeout(
            () => setOpen(false),
            CLOSE_DELAY,
        );
    };

    if (!available) {
        return null;
    }

    const title = currentTrack.title || t.hero.playerTitle;

    return (
        <div
            className="pub-mini-host"
            onMouseEnter={show}
            onMouseLeave={hide}
            onFocus={show}
            onBlur={hide}
        >
            <MusicButton />

            {enabled && open && (
                <div className="pub-mini" role="group" aria-label={title}>
                    <div className="pub-mini__controls">
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
                                <span
                                    className="pub-play__bars"
                                    aria-hidden="true"
                                >
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
                </div>
            )}
        </div>
    );
}
