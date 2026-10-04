import { noise, playSynth, tone } from '@/lib/synth';

/** Sons des réactions du blog : chacun suit le rythme de l'animation de son icône. */
export type ReactionSound = 'like' | 'love' | 'fire' | 'idea' | 'think';

const MASTER_VOLUME = 0.22;

const SOUNDS: Record<
    ReactionSound,
    (ctx: AudioContext, out: AudioNode) => void
> = {
    /** Pouce : petit « tok » boisé puis une note qui monte, comme un pouce levé. */
    like: (ctx, out) => {
        tone(ctx, out, {
            frequency: 220,
            to: 120,
            type: 'triangle',
            duration: 0.08,
            volume: 0.9,
        });
        tone(ctx, out, {
            frequency: 520,
            to: 880,
            start: 0.06,
            duration: 0.18,
            volume: 0.55,
        });
    },
    /** Cœur : battement « boum-boum » grave, puis un scintillement pour les éclats. */
    love: (ctx, out) => {
        tone(ctx, out, { frequency: 110, to: 60, duration: 0.14, volume: 1 });
        tone(ctx, out, {
            frequency: 120,
            to: 65,
            start: 0.16,
            duration: 0.16,
            volume: 0.9,
        });
        [1320, 1760, 2093].forEach((frequency, index) =>
            tone(ctx, out, {
                frequency,
                start: 0.28 + index * 0.05,
                duration: 0.22,
                volume: 0.22,
            }),
        );
    },
    /** Flamme : « fwoosh » d'allumage, puis quelques crépitements de braises. */
    fire: (ctx, out) => {
        noise(ctx, out, { from: 300, to: 2400, duration: 0.45, volume: 0.9 });
        [0.32, 0.41, 0.5].forEach((start, index) =>
            noise(ctx, out, {
                start,
                from: 3500 + index * 700,
                to: 2500,
                duration: 0.03,
                volume: 0.6,
            }),
        );
    },
    /** Ampoule : petit clic d'interrupteur, puis un « ding » clair qui résonne. */
    idea: (ctx, out) => {
        noise(ctx, out, { from: 4000, to: 3000, duration: 0.025, volume: 0.7 });
        tone(ctx, out, {
            frequency: 1568,
            start: 0.3,
            duration: 0.7,
            volume: 0.5,
        });
        tone(ctx, out, {
            frequency: 3136,
            start: 0.3,
            duration: 0.4,
            volume: 0.12,
        });
    },
    /** Réflexion : trois « plop » doux qui montent, au rythme des points de la bulle. */
    think: (ctx, out) => {
        tone(ctx, out, {
            frequency: 300,
            to: 520,
            type: 'sine',
            start: 0.1,
            duration: 0.12,
            volume: 0.5,
        });
        [0.65, 0.77, 0.89].forEach((start, index) =>
            tone(ctx, out, {
                frequency: 600 + index * 180,
                to: 900 + index * 220,
                start,
                duration: 0.09,
                volume: 0.45,
            }),
        );
    },
};

/** Joue le son d'une réaction qu'on vient d'ajouter. */
export function playReactionSound(type: ReactionSound): void {
    playSynth(SOUNDS[type], MASTER_VOLUME);
}
