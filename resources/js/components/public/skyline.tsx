/**
 * Ligne d'horizon de bâtiments : le haut du pied de page (« Construisons… »).
 * Les silhouettes ont la couleur du pied de page, quelques fenêtres sont
 * allumées et certaines clignotent doucement. Dessinée à la main : une liste de
 * bâtiments, chacun avec un type de toit.
 */
type Roof = 'flat' | 'step' | 'antenna' | 'dome' | 'tower';

/** x, largeur, hauteur, toit. */
const BUILDINGS: [number, number, number, Roof][] = [
    [0, 72, 90, 'flat'],
    [70, 70, 140, 'antenna'],
    [138, 66, 110, 'step'],
    [202, 86, 170, 'tower'],
    [286, 60, 100, 'flat'],
    [344, 82, 150, 'step'],
    [424, 70, 120, 'dome'],
    [492, 86, 180, 'antenna'],
    [576, 76, 110, 'flat'],
    [650, 80, 160, 'tower'],
    [728, 80, 130, 'step'],
    [806, 70, 100, 'flat'],
    [874, 86, 175, 'antenna'],
    [958, 76, 120, 'dome'],
    [1032, 80, 150, 'step'],
    [1110, 80, 105, 'flat'],
    [1188, 76, 140, 'tower'],
    [1262, 66, 95, 'flat'],
    [1326, 62, 120, 'antenna'],
    [1386, 70, 80, 'flat'],
];

const HEIGHT = 240;
const WINDOW_W = 7;
const WINDOW_H = 10;

function Roofs({
    x,
    width,
    top,
    roof,
}: {
    x: number;
    width: number;
    top: number;
    roof: Roof;
}) {
    const middle = x + width / 2;

    switch (roof) {
        case 'step':
            return (
                <rect
                    x={x + width * 0.2}
                    y={top - 22}
                    width={width * 0.6}
                    height={24}
                />
            );
        case 'tower':
            return (
                <rect
                    x={x + width * 0.28}
                    y={top - 38}
                    width={width * 0.44}
                    height={40}
                />
            );
        case 'dome':
            return <circle cx={middle} cy={top} r={width * 0.34} />;
        case 'antenna':
            return (
                <path
                    d={`M${middle} ${top} V${top - 34}`}
                    stroke="currentColor"
                    strokeWidth={3}
                    strokeLinecap="round"
                />
            );
        default:
            return null;
    }
}

export default function Skyline({
    className = '',
    fill = 'var(--pub-surface)',
}: {
    className?: string;
    fill?: string;
}) {
    return (
        <svg
            className={`pub-skyline ${className}`}
            viewBox={`0 0 1440 ${HEIGHT}`}
            preserveAspectRatio="xMidYMax slice"
            aria-hidden="true"
            style={{ color: fill }}
        >
            <g fill={fill}>
                {BUILDINGS.map(([x, width, height, roof], index) => {
                    const top = HEIGHT - height;

                    return (
                        <g key={index}>
                            <rect x={x} y={top} width={width} height={height + 4} />
                            <Roofs x={x} width={width} top={top} roof={roof} />
                        </g>
                    );
                })}
                <rect x="-20" y={HEIGHT - 30} width="1480" height="40" />
            </g>

            {BUILDINGS.map(([x, width, height], index) => {
                const top = HEIGHT - height;
                const columns = Math.floor((width - 14) / 18);
                // Pas de fenêtres dans les 70 derniers pixels : les bâtiments s'y
                // fondent dans le pied de page, elles y flotteraient sur un mur uni.
                const rows = Math.floor((height - 14 - 70) / 24);
                const lit = [];

                for (let row = 0; row < rows; row++) {
                    for (let column = 0; column < columns; column++) {
                        // Une fenêtre sur quatre est allumée, de façon fixe.
                        if ((index * 7 + column * 3 + row * 5) % 4 !== 0) {
                            continue;
                        }

                        lit.push(
                            <rect
                                key={`${row}-${column}`}
                                className={
                                    (index + row + column) % 3 === 0
                                        ? 'pub-skyline__window is-blinking'
                                        : 'pub-skyline__window'
                                }
                                x={x + 9 + column * 18}
                                y={top + 14 + row * 24}
                                width={WINDOW_W}
                                height={WINDOW_H}
                                rx={1.5}
                                style={{
                                    animationDelay: `${(index * 0.7 + row) % 5}s`,
                                }}
                            />,
                        );
                    }
                }

                return <g key={index}>{lit}</g>;
            })}
        </svg>
    );
}
