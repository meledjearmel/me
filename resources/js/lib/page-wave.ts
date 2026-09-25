const STEPS = 48;
const POINTS = 24;
const AMPLITUDE = 7;
const WAVES = 2;

/**
 * Images clés d'un clip-path dont le bord supérieur est une vague qui monte du
 * bas de l'écran jusqu'au-dessus du haut : la nouvelle page se dévoile derrière
 * elle. Tous les polygones ont le même nombre de points, ce qui permet au
 * navigateur de les interpoler. La vague ondule en avançant.
 */
export function waveKeyframes(): string[] {
    return Array.from({ length: STEPS + 1 }, (_, step) => {
        const progress = step / STEPS;
        // De 115 % (hors écran, en bas) à -15 % (hors écran, en haut).
        const base = 115 - progress * 130;
        const phase = progress * Math.PI * 3;

        const edge = Array.from({ length: POINTS + 1 }, (_, point) => {
            const x = (point / POINTS) * 100;
            const y = base + AMPLITUDE * Math.sin((x / 100) * WAVES * Math.PI * 2 + phase);

            return `${x.toFixed(2)}% ${y.toFixed(2)}%`;
        });

        return `polygon(${edge.join(', ')}, 100% 130%, 0% 130%)`;
    });
}
