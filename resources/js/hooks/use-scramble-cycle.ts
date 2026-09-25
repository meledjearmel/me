import { useEffect, useState } from 'react';

const GLYPHS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

/**
 * Fait défiler une liste de mots avec un effet de brouillage lettre par lettre.
 * Ne bouge pas si l'utilisateur préfère réduire les animations.
 */
export function useScrambleCycle(words: string[], holdMs = 2800): string {
    const [text, setText] = useState(words[0] ?? '');
    const signature = words.join('|');

    useEffect(() => {
        const list = signature.split('|');
        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        setText(list[0] ?? '');

        if (list.length < 2 || reduceMotion) {
            return;
        }

        let index = 0;
        let frame = 0;
        let timer = 0;

        const next = () => {
            index = (index + 1) % list.length;
            const target = list[index];
            const start = performance.now();
            const duration = 750;

            const tick = (now: number) => {
                const progress = Math.min(1, (now - start) / duration);
                const revealed = Math.floor(progress * target.length);

                setText(
                    target
                        .split('')
                        .map((char, position) =>
                            position < revealed || char === ' ' || char === '—'
                                ? char
                                : GLYPHS[Math.floor(Math.random() * GLYPHS.length)],
                        )
                        .join(''),
                );

                if (progress < 1) {
                    frame = requestAnimationFrame(tick);

                    return;
                }

                setText(target);
                timer = window.setTimeout(next, holdMs);
            };

            frame = requestAnimationFrame(tick);
        };

        timer = window.setTimeout(next, holdMs);

        return () => {
            window.clearTimeout(timer);
            cancelAnimationFrame(frame);
        };
    }, [signature, holdMs]);

    return text;
}
