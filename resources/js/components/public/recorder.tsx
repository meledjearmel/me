import { AnimatePresence } from 'framer-motion';
import { Maximize2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import Disk from '@/components/public/disk';
import PlayerSlider from '@/components/public/player-slider';
import RecorderWide from '@/components/public/recorder-wide';
import { useMediaQuery } from '@/hooks/use-media-query';
import { formatTime, useAudioPlayer } from '@/lib/audio';
import { useTranslations } from '@/lib/i18n';

/** Le mode wide et le mini lecteur n'existent qu'à partir de cette largeur. */
export const WIDE_PLAYER_QUERY = '(min-width: 1024px)';

export default function Recorder() {
    const t = useTranslations();
    const { playing, time, duration, toggle, seek, genres } = useAudioPlayer();
    const isWideScreen = useMediaQuery(WIDE_PLAYER_QUERY);
    const [expanded, setExpanded] = useState(false);
    const canExpand = isWideScreen && genres.length > 0;
    const isExpanded = expanded && canExpand;

    // Sous la largeur du mode wide, l'état ouvert est oublié.
    useEffect(() => {
        if (!canExpand) {
            setExpanded(false);
        }
    }, [canExpand]);

    return (
        <div className="pub-recorder-slot">
            <div
                className={`pub-recorder${isExpanded ? ' is-hidden' : ''}`}
                role="region"
                aria-label={t.hero.playerTitle}
                aria-hidden={isExpanded || undefined}
                data-cursor-label={t.hero.cursorPlay}
            >
                <Disk spinning={playing} />

                <div className="pub-recorder__center">
                    <p className="pub-recorder__title">{t.hero.playerTitle}</p>

                    <PlayerSlider
                        time={time}
                        duration={duration}
                        label={t.hero.playerTitle}
                        onSeek={seek}
                    />

                    <div className="pub-recorder__times">
                        <span>{formatTime(time)}</span>
                        <span>{formatTime(duration)}</span>
                    </div>

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
                </div>

                <Disk spinning={playing} />

                {canExpand && (
                    <button
                        type="button"
                        className="pub-recorder__expand"
                        aria-label={t.hero.expandPlayer}
                        onClick={() => setExpanded(true)}
                    >
                        <Maximize2 aria-hidden="true" />
                    </button>
                )}
            </div>

            <AnimatePresence>
                {isExpanded && (
                    <RecorderWide onClose={() => setExpanded(false)} />
                )}
            </AnimatePresence>
        </div>
    );
}
