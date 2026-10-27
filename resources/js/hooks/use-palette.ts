import { useSyncExternalStore } from 'react';

/** Les quatre palettes de l'app mobile, chacune en clair et en sombre (console.css). */
export const PALETTES = [
    {
        value: 'gold-graphite',
        label: 'Or & graphite',
        swatch: ['#111114', '#ffd23f', '#f4f3ef'],
    },
    {
        value: 'sage-amber',
        label: 'Sauge & ambre',
        swatch: ['#15201a', '#f5b83d', '#f1f3ee'],
    },
    {
        value: 'lime-charcoal',
        label: 'Lime & charbon',
        swatch: ['#121212', '#d4f25a', '#efefef'],
    },
    {
        value: 'sky-amber',
        label: 'Ciel & ambre',
        swatch: ['#0f1a2a', '#ffd23f', '#f3f6fa'],
    },
] as const;

export type Palette = (typeof PALETTES)[number]['value'];

const listeners = new Set<() => void>();

const isPalette = (value: string | null | undefined): value is Palette =>
    PALETTES.some((palette) => palette.value === value);

/** La palette affichée est celle posée par le serveur sur <html> (cookie). */
const readPalette = (): Palette => {
    if (typeof document === 'undefined') {
        return PALETTES[0].value;
    }

    const current = document.documentElement.dataset.palette;

    return isPalette(current) ? current : PALETTES[0].value;
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

export function usePalette(): {
    readonly palette: Palette;
    readonly updatePalette: (palette: Palette) => void;
} {
    const palette = useSyncExternalStore(
        subscribe,
        readPalette,
        () => PALETTES[0].value,
    );

    const updatePalette = (value: Palette): void => {
        document.documentElement.dataset.palette = value;
        document.cookie = `palette=${value};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
        listeners.forEach((listener) => listener());
    };

    return { palette, updatePalette } as const;
}

/** Passe à la palette suivante (bouton de l'en-tête). */
export function nextPalette(current: Palette): Palette {
    const index = PALETTES.findIndex((palette) => palette.value === current);

    return PALETTES[(index + 1) % PALETTES.length].value;
}
