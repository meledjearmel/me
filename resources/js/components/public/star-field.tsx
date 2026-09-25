/**
 * Ciel étoilé : des centaines d'étoiles de tailles et de teintes variées,
 * générées une fois avec un générateur pseudo-aléatoire à graine fixe (même
 * résultat côté serveur et côté navigateur, donc pas de décalage à
 * l'hydratation). Chaque couche est un seul point dont les `box-shadow`
 * dessinent les étoiles, positionnées en vw/vh pour suivre l'écran.
 */
function mulberry32(seed: number): () => number {
    let state = seed;

    return () => {
        state = (state + 0x6d2b79f5) | 0;
        let value = Math.imul(state ^ (state >>> 15), 1 | state);

        value = (value + Math.imul(value ^ (value >>> 7), 61 | value)) ^ value;

        return ((value ^ (value >>> 14)) >>> 0) / 4294967296;
    };
}

/** Teintes du champ d'étoiles : cœur blanc, halo bleuté, lueur bleue. */
const TINTS = ['#fffbff', '#fffbff', '#d6ebff', '#a5caf6', '#fff1d6'] as const;

function shadows(seed: number, count: number, glow: boolean): string {
    const random = mulberry32(seed);

    return Array.from({ length: count }, () => {
        const x = (random() * 100).toFixed(2);
        const y = (random() * 100).toFixed(2);
        const tint = TINTS[Math.floor(random() * TINTS.length)];

        // Les grosses étoiles ont un halo : un second ombrage flou autour du cœur.
        return glow
            ? `${x}vw ${y}vh 0 0 ${tint}, ${x}vw ${y}vh 6px 2px ${tint}55`
            : `${x}vw ${y}vh 0 0 ${tint}`;
    }).join(', ');
}

/** Couches : petites (nombreuses), moyennes, grosses avec halo. */
const LAYERS = [
    { seed: 11, count: 260, size: 1, glow: false, duration: 5 },
    { seed: 23, count: 260, size: 1, glow: false, duration: 8 },
    { seed: 37, count: 110, size: 1.6, glow: false, duration: 6 },
    { seed: 41, count: 90, size: 1.6, glow: false, duration: 10 },
    { seed: 53, count: 22, size: 2.4, glow: true, duration: 7 },
    { seed: 67, count: 18, size: 2.4, glow: true, duration: 11 },
] as const;

const LAYER_SHADOWS = LAYERS.map((layer) =>
    shadows(layer.seed, layer.count, layer.glow),
);

export default function StarField() {
    return (
        <div className="pub-stars" aria-hidden="true">
            {LAYERS.map((layer, index) => (
                <span
                    key={layer.seed}
                    className="pub-stars__layer"
                    style={{
                        width: layer.size,
                        height: layer.size,
                        boxShadow: LAYER_SHADOWS[index],
                        animationDuration: `${layer.duration}s`,
                        animationDelay: `${-index * 1.3}s`,
                    }}
                />
            ))}
        </div>
    );
}
