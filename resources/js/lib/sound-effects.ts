const SOUNDS = {
    /** Les trois points d'Armi sortent. */
    bubblePop: '/audio/sfx/bubble-pop.mp3',
    /** La surprise apparaît. */
    whoosh: '/audio/sfx/whoosh.mp3',
} as const;

export type SoundEffect = keyof typeof SOUNDS;

const players: Partial<Record<SoundEffect, HTMLAudioElement>> = {};

/**
 * Charge les effets à l'avance pour qu'ils partent sans délai au moment voulu.
 */
export function preloadSoundEffects(): void {
    if (typeof window === 'undefined') {
        return;
    }

    (Object.keys(SOUNDS) as SoundEffect[]).forEach((name) => {
        if (!players[name]) {
            const audio = new Audio(SOUNDS[name]);

            audio.preload = 'auto';
            players[name] = audio;
        }
    });
}

/**
 * Joue un effet sonore. On tente toujours : si le navigateur bloque le son
 * (aucune interaction avec la page et site peu fréquenté), l'erreur est ignorée.
 */
export function playSoundEffect(name: SoundEffect, volume = 0.5): void {
    if (typeof window === 'undefined') {
        return;
    }

    preloadSoundEffects();

    const audio = players[name]!;

    audio.volume = volume;
    audio.currentTime = 0;
    void audio.play().catch(() => undefined);
}
