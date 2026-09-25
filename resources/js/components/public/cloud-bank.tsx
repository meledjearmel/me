/**
 * Banc de nuages : la forme qui sépare le hero de la suite. Il est dessiné à la
 * main à partir de disques qui se chevauchent (une silhouette de cumulus aux
 * lobes irréguliers), de « langues » étirées sur les côtés, et de trois
 * traînées de nuage séparées qui dérivent lentement au-dessus.
 */
const LOBES: [number, number, number][] = [
    [-20, 190, 80],
    [90, 172, 62],
    [175, 150, 88],
    [292, 180, 56],
    [372, 158, 74],
    [472, 186, 50],
    [562, 168, 64],
    [662, 184, 50],
    [748, 148, 84],
    [852, 178, 58],
    [942, 156, 72],
    [1042, 186, 50],
    [1124, 150, 86],
    [1232, 174, 60],
    [1322, 158, 76],
    [1424, 182, 60],
];

/** Langues étirées à l'horizontale : cx, cy, rx, ry, rotation. */
const TONGUES: [number, number, number, number, number][] = [
    [238, 128, 96, 13, -5],
    [452, 144, 84, 11, 4],
    [1010, 138, 104, 12, -4],
    [1268, 132, 78, 11, 5],
];

const WISPS = [
    { d: 'M150 62 Q 235 34 335 66', width: 13, duration: 13, delay: 0 },
    { d: 'M640 44 Q 700 18 780 46', width: 10, duration: 17, delay: -6 },
    { d: 'M1170 70 Q 1252 38 1336 56', width: 12, duration: 15, delay: -3 },
] as const;

export default function CloudBank({
    className = '',
    fill = 'var(--pub-bg)',
    wisp = 'var(--pub-wisp)',
    flip = false,
}: {
    className?: string;
    fill?: string;
    wisp?: string;
    /** Miroir horizontal, pour que deux bancs successifs ne se ressemblent pas. */
    flip?: boolean;
}) {
    return (
        <svg
            className={`pub-cloudbank ${className}`}
            viewBox="0 0 1440 340"
            preserveAspectRatio="none"
            aria-hidden="true"
            style={flip ? { transform: 'scaleX(-1)' } : undefined}
        >
            {WISPS.map((item, index) => (
                <path
                    key={index}
                    className="pub-cloudbank__wisp"
                    d={item.d}
                    fill="none"
                    stroke={wisp}
                    strokeWidth={item.width}
                    strokeLinecap="round"
                    style={{
                        animationDuration: `${item.duration}s`,
                        animationDelay: `${item.delay}s`,
                    }}
                />
            ))}

            <g fill={fill}>
                {LOBES.map(([cx, cy, r], index) => (
                    <circle key={index} cx={cx} cy={cy} r={r} />
                ))}
                {TONGUES.map(([cx, cy, rx, ry, rotation], index) => (
                    <ellipse
                        key={index}
                        cx={cx}
                        cy={cy}
                        rx={rx}
                        ry={ry}
                        transform={`rotate(${rotation} ${cx} ${cy})`}
                    />
                ))}
                <rect x="-40" y="180" width="1520" height="170" />
            </g>
        </svg>
    );
}
