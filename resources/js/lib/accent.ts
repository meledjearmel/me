/** Couleurs d'accent utilisées quand un projet n'en définit pas. */
const FALLBACK_ACCENTS = [
    '#3456c8',
    '#e0714f',
    '#2f8f6b',
    '#8b5cc7',
    '#c79a1f',
] as const;

export function accentFor(accent: string | null, index: number): string {
    return accent ?? FALLBACK_ACCENTS[index % FALLBACK_ACCENTS.length];
}

/**
 * Renvoie une couleur de texte lisible (encre ou crème) sur le fond donné,
 * d'après sa luminance perçue.
 */
export function readableTextOn(hex: string): string {
    const value = hex.replace('#', '');
    const [red, green, blue] = [0, 2, 4].map((start) =>
        parseInt(value.slice(start, start + 2), 16),
    );
    const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;

    return luminance > 0.62 ? '#060606' : '#fff9e9';
}
