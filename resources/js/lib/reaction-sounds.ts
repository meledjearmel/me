/**
 * Sons des réactions du blog, synthétisés avec la Web Audio API : aucun fichier à charger,
 * et chaque son suit le rythme de l'animation de son icône. Le son ne part qu'après un clic
 * (le navigateur l'autorise alors toujours) ; une erreur est ignorée.
 */
export type ReactionSound = 'like' | 'love' | 'fire' | 'idea' | 'think';

const MASTER_VOLUME = 0.22;

let context: AudioContext | null = null;

function audio(): AudioContext | null {
    if (typeof window === 'undefined' || !('AudioContext' in window)) {
        return null;
    }

    context ??= new AudioContext();

    if (context.state === 'suspended') {
        void context.resume();
    }

    return context;
}

/** Une note : oscillateur avec attaque rapide et extinction exponentielle. */
function tone(
    ctx: AudioContext,
    output: AudioNode,
    {
        frequency,
        to,
        type = 'sine',
        start = 0,
        duration = 0.2,
        volume = 1,
    }: {
        frequency: number;
        to?: number;
        type?: OscillatorType;
        start?: number;
        duration?: number;
        volume?: number;
    },
): void {
    const at = ctx.currentTime + start;
    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();

    oscillator.type = type;
    oscillator.frequency.setValueAtTime(frequency, at);

    if (to !== undefined) {
        oscillator.frequency.exponentialRampToValueAtTime(to, at + duration);
    }

    gain.gain.setValueAtTime(0.0001, at);
    gain.gain.exponentialRampToValueAtTime(volume, at + 0.012);
    gain.gain.exponentialRampToValueAtTime(0.0001, at + duration);

    oscillator.connect(gain).connect(output);
    oscillator.start(at);
    oscillator.stop(at + duration + 0.02);
}

/** Un souffle : bruit blanc filtré dont la fréquence de coupure balaie. */
function noise(
    ctx: AudioContext,
    output: AudioNode,
    {
        start = 0,
        duration = 0.4,
        from,
        to,
        volume = 1,
    }: {
        start?: number;
        duration?: number;
        from: number;
        to: number;
        volume?: number;
    },
): void {
    const at = ctx.currentTime + start;
    const buffer = ctx.createBuffer(
        1,
        Math.ceil(ctx.sampleRate * duration),
        ctx.sampleRate,
    );
    const data = buffer.getChannelData(0);

    for (let index = 0; index < data.length; index++) {
        data[index] = Math.random() * 2 - 1;
    }

    const source = ctx.createBufferSource();
    const filter = ctx.createBiquadFilter();
    const gain = ctx.createGain();

    source.buffer = buffer;
    filter.type = 'bandpass';
    filter.Q.value = 0.9;
    filter.frequency.setValueAtTime(from, at);
    filter.frequency.exponentialRampToValueAtTime(to, at + duration);
    gain.gain.setValueAtTime(0.0001, at);
    gain.gain.exponentialRampToValueAtTime(volume, at + duration * 0.25);
    gain.gain.exponentialRampToValueAtTime(0.0001, at + duration);

    source.connect(filter).connect(gain).connect(output);
    source.start(at);
}

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
    try {
        const ctx = audio();

        if (!ctx) {
            return;
        }

        const master = ctx.createGain();
        master.gain.value = MASTER_VOLUME;
        master.connect(ctx.destination);
        SOUNDS[type](ctx, master);
    } catch {
        // Un son raté ne doit jamais gêner le clic.
    }
}
