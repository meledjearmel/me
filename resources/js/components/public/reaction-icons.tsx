import { motion, useAnimationControls } from 'framer-motion';
import { useEffect, useId } from 'react';

/**
 * Icônes animées des réactions du blog. Au repos : un contour. Actives : remplies de leur
 * couleur. `play` augmente à chaque ajout de la réaction et rejoue son animation.
 */
type IconProps = {
    active: boolean;
    play: number;
    reduceMotion: boolean;
};

const EASE_OUT_BACK = [0.22, 1, 0.36, 1] as const;

const STROKE = {
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.8,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
} as const;

/** Identifiant utilisable dans `url(#…)` (useId peut contenir des « : »). */
function useSvgId(): string {
    return useId().replace(/[^a-zA-Z0-9-]/g, '');
}

/** Rejoue une animation impérative à chaque nouvelle valeur de `play`. */
function useReplay(play: number, reduceMotion: boolean, run: () => void): void {
    useEffect(() => {
        if (play > 0 && !reduceMotion) {
            run();
        }
        // `run` change à chaque rendu : seul `play` doit relancer l'animation.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [play, reduceMotion]);
}

function fillTransition(reduceMotion: boolean, delay = 0) {
    return reduceMotion
        ? { duration: 0 }
        : { duration: 0.3, delay, ease: 'easeOut' as const };
}

const THUMB =
    'M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z';

/** Pouce qui se lève depuis le poignet ; le doré monte de bas en haut. */
export function ThumbIcon({ active, play, reduceMotion }: IconProps) {
    const id = useSvgId();
    const hand = useAnimationControls();

    useReplay(play, reduceMotion, () => {
        void hand.start({
            rotate: [-28, 10, -4, 0],
            y: [2, -1.5, 0.5, 0],
            transition: { duration: 0.65, ease: EASE_OUT_BACK },
        });
    });

    return (
        <svg
            viewBox="0 0 24 24"
            className="pub-reaction-icon"
            aria-hidden="true"
        >
            <defs>
                <clipPath id={`${id}-rise`}>
                    <motion.rect
                        x="0"
                        width="24"
                        height="24"
                        initial={false}
                        animate={{ y: active ? 0 : 24 }}
                        transition={
                            reduceMotion
                                ? { duration: 0 }
                                : {
                                      duration: 0.45,
                                      delay: 0.12,
                                      ease: 'easeOut',
                                  }
                        }
                    />
                </clipPath>
            </defs>
            <motion.g animate={hand} style={{ originX: 0.15, originY: 0.95 }}>
                <path
                    d={THUMB}
                    fill="var(--reaction-like)"
                    clipPath={`url(#${id}-rise)`}
                />
                <path d={THUMB} {...STROKE} />
                <path d="M7 10v12" {...STROKE} />
            </motion.g>
        </svg>
    );
}

const HEART =
    'M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z';

const HEART_SPARKS = [0, 1, 2, 3, 4, 5, 6].map((index) => {
    const angle = (index / 7) * Math.PI * 2 - Math.PI / 2;

    return { x: Math.cos(angle), y: Math.sin(angle), hue: index % 2 };
});

/** Le cœur se rétracte, un anneau s'étend, puis il surgit plein avec des éclats. */
export function HeartIcon({ active, play, reduceMotion }: IconProps) {
    const heart = useAnimationControls();

    useReplay(play, reduceMotion, () => {
        void heart.start({
            scale: [1, 0.2, 1.3, 0.92, 1],
            transition: {
                duration: 0.7,
                times: [0, 0.18, 0.55, 0.8, 1],
                ease: 'easeOut',
            },
        });
    });

    const burst = play > 0 && active && !reduceMotion;

    return (
        <svg
            viewBox="0 0 24 24"
            className="pub-reaction-icon"
            aria-hidden="true"
        >
            {burst && (
                <g key={play}>
                    <motion.circle
                        cx="12"
                        cy="12"
                        fill="none"
                        stroke="var(--reaction-love)"
                        initial={{ r: 2, strokeWidth: 6, opacity: 0.9 }}
                        animate={{ r: 13, strokeWidth: 0, opacity: 0 }}
                        transition={{
                            duration: 0.5,
                            delay: 0.08,
                            ease: 'easeOut',
                        }}
                    />
                    {HEART_SPARKS.map((spark, index) => (
                        <motion.circle
                            key={index}
                            fill={
                                spark.hue
                                    ? 'var(--reaction-love)'
                                    : 'var(--reaction-like)'
                            }
                            initial={{
                                cx: 12 + spark.x * 7,
                                cy: 12 + spark.y * 7,
                                r: 1.8,
                                opacity: 0,
                            }}
                            animate={{
                                cx: 12 + spark.x * 15,
                                cy: 12 + spark.y * 15,
                                r: 0,
                                opacity: [0, 1, 1],
                            }}
                            transition={{
                                duration: 0.55,
                                delay: 0.25,
                                ease: 'easeOut',
                            }}
                        />
                    ))}
                </g>
            )}
            <motion.g animate={heart} style={{ originX: 0.5, originY: 0.55 }}>
                <motion.path
                    d={HEART}
                    fill="var(--reaction-love)"
                    initial={false}
                    animate={{ opacity: active ? 1 : 0 }}
                    transition={fillTransition(reduceMotion, 0.12)}
                />
                <motion.path
                    d={HEART}
                    {...STROKE}
                    initial={false}
                    animate={{
                        stroke: active
                            ? 'var(--reaction-love)'
                            : 'currentColor',
                    }}
                    transition={fillTransition(reduceMotion, 0.12)}
                />
            </motion.g>
        </svg>
    );
}

const FLAME =
    'M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z';

const EMBERS = [
    { x: 9, drift: -2.5, delay: 0.3 },
    { x: 13, drift: 1.5, delay: 0.42 },
    { x: 15.5, drift: 3, delay: 0.36 },
];

/** La flamme naît à la base, grandit en ondulant, puis quelques braises s'envolent. */
export function FlameIcon({ active, play, reduceMotion }: IconProps) {
    const id = useSvgId();
    const flame = useAnimationControls();

    useReplay(play, reduceMotion, () => {
        void flame.start({
            scaleY: [0.25, 1.18, 0.94, 1.04, 1],
            scaleX: [0.6, 0.92, 1.05, 0.98, 1],
            skewX: [0, 7, -6, 3, 0],
            transition: { duration: 0.8, ease: 'easeOut' },
        });
    });

    const burst = play > 0 && active && !reduceMotion;

    return (
        <svg
            viewBox="0 0 24 24"
            className="pub-reaction-icon"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id={`${id}-fire`} x1="0" y1="1" x2="0" y2="0">
                    <stop offset="0%" stopColor="var(--reaction-fire-core)" />
                    <stop offset="100%" stopColor="var(--reaction-fire)" />
                </linearGradient>
            </defs>
            {burst &&
                EMBERS.map((ember, index) => (
                    <motion.circle
                        key={`${play}-${index}`}
                        fill="var(--reaction-fire)"
                        initial={{ cx: ember.x, cy: 6, r: 1.2, opacity: 0 }}
                        animate={{
                            cx: ember.x + ember.drift,
                            cy: -4,
                            r: 0.3,
                            opacity: [0, 1, 0],
                        }}
                        transition={{
                            duration: 0.7,
                            delay: ember.delay,
                            ease: 'easeOut',
                        }}
                    />
                ))}
            <motion.g animate={flame} style={{ originX: 0.5, originY: 1 }}>
                <motion.path
                    d={FLAME}
                    fill={`url(#${id}-fire)`}
                    initial={false}
                    animate={{ opacity: active ? 1 : 0 }}
                    transition={fillTransition(reduceMotion, 0.1)}
                />
                <motion.path
                    d={FLAME}
                    {...STROKE}
                    initial={false}
                    animate={{
                        stroke: active
                            ? 'var(--reaction-fire)'
                            : 'currentColor',
                    }}
                    transition={fillTransition(reduceMotion, 0.1)}
                />
            </motion.g>
        </svg>
    );
}

const BULB =
    'M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5';

const RAYS = [-150, -115, -90, -65, -30].map((degrees) => {
    const angle = (degrees * Math.PI) / 180;

    return {
        x1: 12 + Math.cos(angle) * 9.5,
        y1: 8 + Math.sin(angle) * 9.5,
        x2: 12 + Math.cos(angle) * 12.5,
        y2: 8 + Math.sin(angle) * 12.5,
    };
});

/** Le filament se trace, l'ampoule s'allume avec un halo, puis les rayons se dessinent. */
export function BulbIcon({ active, play, reduceMotion }: IconProps) {
    const filament = useAnimationControls();
    const burst = play > 0 && active && !reduceMotion;

    useReplay(play, reduceMotion, () => {
        void filament.start({
            pathLength: [0, 1],
            transition: { duration: 0.35, ease: 'easeInOut' },
        });
    });

    return (
        <svg
            viewBox="0 0 24 24"
            className="pub-reaction-icon"
            aria-hidden="true"
        >
            {burst && (
                <g key={play}>
                    <motion.circle
                        cx="12"
                        cy="8"
                        fill="var(--reaction-idea)"
                        initial={{ r: 4, opacity: 0 }}
                        animate={{ r: 11, opacity: [0, 0.45, 0] }}
                        transition={{
                            duration: 0.7,
                            delay: 0.3,
                            ease: 'easeOut',
                        }}
                    />
                    {RAYS.map((ray, index) => (
                        <motion.line
                            key={index}
                            {...ray}
                            {...STROKE}
                            stroke="var(--reaction-idea)"
                            initial={{ pathLength: 0, opacity: 1 }}
                            animate={{ pathLength: 1, opacity: [1, 1, 0] }}
                            transition={{
                                pathLength: {
                                    duration: 0.25,
                                    delay: 0.4 + index * 0.05,
                                },
                                opacity: {
                                    duration: 0.8,
                                    delay: 0.4 + index * 0.05,
                                    times: [0, 0.6, 1],
                                },
                            }}
                        />
                    ))}
                </g>
            )}
            <motion.path
                d={`${BULB}Z`}
                fill="var(--reaction-idea)"
                stroke="none"
                initial={false}
                animate={{ opacity: active ? 1 : 0 }}
                transition={fillTransition(reduceMotion, 0.3)}
            />
            <path d={BULB} {...STROKE} />
            <path d="M9 18h6M10 22h4" {...STROKE} />
            <motion.path
                d="M10 14v-2.5l1-1.5 1 1.5 1-1.5 1 1.5V14"
                {...STROKE}
                strokeWidth={1.4}
                animate={filament}
            />
        </svg>
    );
}

const THOUGHT =
    'M7.5 13.5a3.5 3.5 0 0 1-.4-6.97A4.5 4.5 0 0 1 15.4 4.6a4 4 0 0 1 4.1 6.4 3 3 0 0 1-2.9 3.9 3.5 3.5 0 0 1-4.6.6 3.6 3.6 0 0 1-4.5-2Z';

const THOUGHT_TRAIL = [
    { cx: 6.5, cy: 17.5, r: 1.6 },
    { cx: 3.8, cy: 20.6, r: 1 },
];

const THOUGHT_DOTS = [9.5, 12.5, 15.5];

/** Les petites bulles montent, la bulle de pensée gonfle, puis trois points s'allument. */
export function ThinkIcon({ active, play, reduceMotion }: IconProps) {
    const cloud = useAnimationControls();
    const burst = play > 0 && active && !reduceMotion;

    useReplay(play, reduceMotion, () => {
        void cloud.start({
            scale: [0.3, 1.15, 0.95, 1],
            opacity: [0, 1, 1, 1],
            transition: { duration: 0.55, delay: 0.25, ease: EASE_OUT_BACK },
        });
    });

    return (
        <svg viewBox="0 0 24 24" className="pub-reaction-icon" aria-hidden="true">
            {THOUGHT_TRAIL.map((bubble, index) => (
                <motion.circle
                    key={`${play}-${index}`}
                    {...bubble}
                    {...STROKE}
                    fill={active ? 'var(--reaction-think)' : 'none'}
                    stroke={active ? 'var(--reaction-think)' : 'currentColor'}
                    initial={burst ? { scale: 0, opacity: 0 } : false}
                    animate={{ scale: 1, opacity: 1 }}
                    transition={{
                        duration: 0.25,
                        delay: burst ? 0.12 - index * 0.1 : 0,
                        ease: EASE_OUT_BACK,
                    }}
                />
            ))}
            <motion.g animate={cloud} style={{ originX: 0.2, originY: 0.9 }}>
                <motion.path
                    d={THOUGHT}
                    fill="var(--reaction-think)"
                    initial={false}
                    animate={{ opacity: active ? 1 : 0 }}
                    transition={fillTransition(reduceMotion, 0.25)}
                />
                <motion.path
                    d={THOUGHT}
                    {...STROKE}
                    initial={false}
                    animate={{
                        stroke: active ? 'var(--reaction-think)' : 'currentColor',
                    }}
                    transition={fillTransition(reduceMotion, 0.25)}
                />
                {THOUGHT_DOTS.map((cx, index) => (
                    <motion.circle
                        key={`${play}-${index}`}
                        cx={cx}
                        cy="9.6"
                        r="1.1"
                        fill={active ? '#fff' : 'currentColor'}
                        initial={burst ? { opacity: 0, scale: 0 } : false}
                        animate={
                            burst
                                ? { opacity: 1, scale: [0, 1.5, 1] }
                                : { opacity: 1, scale: 1 }
                        }
                        transition={{
                            duration: 0.3,
                            delay: burst ? 0.65 + index * 0.12 : 0,
                        }}
                    />
                ))}
            </motion.g>
        </svg>
    );
}
