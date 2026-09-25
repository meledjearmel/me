import { useRef, useState, type PointerEvent } from 'react';
import { formatTime, useAudioPlayer } from '@/lib/audio';
import { useTranslations } from '@/lib/i18n';

function Disk({ spinning }: { spinning: boolean }) {
    return (
        <div
            className={`pub-disk${spinning ? ' is-spinning' : ''}`}
            aria-hidden="true"
        >
            <span className="pub-disk__face" />
            <span className="pub-disk__cap pub-disk__cap--1" />
            <span className="pub-disk__cap pub-disk__cap--2" />
            <span className="pub-disk__cap pub-disk__cap--3" />
        </div>
    );
}

export default function Recorder() {
    const t = useTranslations();
    const { playing, time, duration, toggle, seek } = useAudioPlayer();
    const sliderRef = useRef<HTMLDivElement>(null);
    const [scrubbing, setScrubbing] = useState(false);
    const progress = duration ? (time / duration) * 100 : 0;

    const seekFromPointer = (event: PointerEvent<HTMLDivElement>) => {
        const rect = sliderRef.current?.getBoundingClientRect();

        if (rect) {
            seek((event.clientX - rect.left) / rect.width);
        }
    };

    return (
        <div
            className="pub-recorder"
            role="region"
            aria-label={t.hero.playerTitle}
            data-cursor-label={t.hero.cursorPlay}
        >
            <Disk spinning={playing} />

            <div className="pub-recorder__center">
                <p className="pub-recorder__title">{t.hero.playerTitle}</p>

                <div
                    ref={sliderRef}
                    className={`pub-slider${scrubbing ? ' is-scrubbing' : ''}`}
                    role="slider"
                    tabIndex={0}
                    aria-label={t.hero.playerTitle}
                    aria-valuemin={0}
                    aria-valuemax={Math.round(duration)}
                    aria-valuenow={Math.round(time)}
                    onPointerDown={(event) => {
                        event.currentTarget.setPointerCapture(event.pointerId);
                        setScrubbing(true);
                        seekFromPointer(event);
                    }}
                    onPointerMove={(event) => scrubbing && seekFromPointer(event)}
                    onPointerUp={() => setScrubbing(false)}
                    onKeyDown={(event) => {
                        if (event.key === 'ArrowRight') {
                            seek((time + 5) / (duration || 1));
                        } else if (event.key === 'ArrowLeft') {
                            seek((time - 5) / (duration || 1));
                        }
                    }}
                >
                    <div className="pub-slider__track">
                        <div className="pub-slider__fill" style={{ width: `${progress}%` }} />
                    </div>
                    <div
                        className="pub-slider__knob"
                        style={{
                            left: `calc(${progress}% - ${progress * 0.014656}em)`,
                        }}
                    />
                </div>

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
                            <path d="M8.5 5.5v13l10.5-6.5z" fill="currentColor" />
                        </svg>
                    )}
                </button>
            </div>

            <Disk spinning={playing} />
        </div>
    );
}
