import { useRef, useState, type PointerEvent } from 'react';

type PlayerSliderProps = {
    time: number;
    duration: number;
    label: string;
    onSeek: (ratio: number) => void;
};

/** Barre de progression du lecteur : clic, glissement et flèches ←/→ (5 s). */
export default function PlayerSlider({
    time,
    duration,
    label,
    onSeek,
}: PlayerSliderProps) {
    const sliderRef = useRef<HTMLDivElement>(null);
    const [scrubbing, setScrubbing] = useState(false);
    const progress = duration ? (time / duration) * 100 : 0;

    const seekFromPointer = (event: PointerEvent<HTMLDivElement>) => {
        const rect = sliderRef.current?.getBoundingClientRect();

        if (rect) {
            onSeek((event.clientX - rect.left) / rect.width);
        }
    };

    return (
        <div
            ref={sliderRef}
            className={`pub-slider${scrubbing ? ' is-scrubbing' : ''}`}
            role="slider"
            tabIndex={0}
            aria-label={label}
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
                    onSeek((time + 5) / (duration || 1));
                } else if (event.key === 'ArrowLeft') {
                    onSeek((time - 5) / (duration || 1));
                }
            }}
        >
            <div className="pub-slider__track">
                <div
                    className="pub-slider__fill"
                    style={{ width: `${progress}%` }}
                />
            </div>
            <div
                className="pub-slider__knob"
                style={{
                    left: `calc(${progress}% - ${progress * 0.014656}em)`,
                }}
            />
        </div>
    );
}
