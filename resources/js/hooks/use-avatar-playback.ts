import { useReducedMotion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';
import type { AvatarDefinition } from '@/lib/avatar';

type Playback = {
    expression: string;
    transitionMs: number;
    blinking: boolean;
    blinkMs: number;
};

const between = (min: number, max: number) =>
    min + Math.random() * Math.max(0, max - min);

/**
 * Lit une animation de l'avatar : enchaîne ses étapes (transition puis pause),
 * boucle ou s'arrête à la fin selon son mode, et déclenche les clignements à
 * intervalles aléatoires.
 *
 * Avec « réduire les animations », seule une expression fixe est affichée
 * (la première, ou la dernière pour une animation jouée une fois) : rien ne
 * bouge en continu, mais l'état reste lisible.
 */
export function useAvatarPlayback(
    definition: AvatarDefinition,
    animation: string,
    onEnd?: () => void,
): Playback {
    const reduceMotion = useReducedMotion() ?? false;
    const [current, setCurrent] = useState({
        expression: 'neutral',
        transitionMs: 0,
    });
    const [blinking, setBlinking] = useState(false);
    const onEndRef = useRef(onEnd);

    useEffect(() => {
        onEndRef.current = onEnd;
    }, [onEnd]);

    useEffect(() => {
        const timeline = definition.animations[animation];

        if (!timeline || timeline.steps.length === 0) {
            return;
        }

        const timers = new Set<number>();
        const later = (callback: () => void, delay: number) => {
            const id = window.setTimeout(() => {
                timers.delete(id);
                callback();
            }, delay);

            timers.add(id);
        };
        const once = timeline.playbackMode === 'once';
        const last = timeline.steps.length - 1;

        const show = (index: number) => {
            const step = timeline.steps[index];

            setCurrent({
                expression: step.expression,
                transitionMs: reduceMotion ? 0 : step.transitionMs,
            });

            if (reduceMotion) {
                if (once) {
                    later(() => onEndRef.current?.(), 0);
                }

                return;
            }

            const duration = step.transitionMs + step.holdMs;

            if (once && index === last) {
                later(() => onEndRef.current?.(), duration);

                return;
            }

            later(() => show((index + 1) % timeline.steps.length), duration);
        };

        const blink = timeline.blink;
        const scheduleBlink = (delay: number) =>
            later(() => {
                setBlinking(true);
                later(() => {
                    setBlinking(false);
                    scheduleBlink(
                        between(blink.minIntervalMs, blink.maxIntervalMs),
                    );
                }, blink.durationMs);
            }, delay);

        setBlinking(false);
        show(reduceMotion && once ? last : 0);

        if (blink.enabled && !reduceMotion) {
            scheduleBlink(blink.initialDelayMs);
        }

        return () => timers.forEach((id) => window.clearTimeout(id));
    }, [definition, animation, reduceMotion]);

    const blinkMs = definition.animations[animation]?.blink.durationMs ?? 200;

    return { ...current, blinking, blinkMs: blinkMs / 2 };
}
