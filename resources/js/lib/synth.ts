/**
 * Petits sons synthétisés avec la Web Audio API (réactions du blog, Armi) : aucun fichier
 * à charger. Le son ne part qu'après un clic (le navigateur l'autorise alors toujours) ;
 * une erreur est ignorée.
 */
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
export function tone(
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
export function noise(
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

/** Joue un son construit avec `tone` et `noise`, au volume donné. */
export function playSynth(
    build: (ctx: AudioContext, output: AudioNode) => void,
    volume: number,
): void {
    try {
        const ctx = audio();

        if (!ctx) {
            return;
        }

        const master = ctx.createGain();
        master.gain.value = volume;
        master.connect(ctx.destination);
        build(ctx, master);
    } catch {
        // Un son raté ne doit jamais gêner le clic.
    }
}
