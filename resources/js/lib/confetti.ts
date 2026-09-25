const COLORS = ['#ffd93d', '#ff6b4a', '#3a86ff', '#34d399', '#f472b6', '#fff9e9'];
const COUNT = 90;
const GRAVITY = 0.32;
const DRAG = 0.985;

type Piece = {
    x: number;
    y: number;
    vx: number;
    vy: number;
    size: number;
    color: string;
    rotation: number;
    spin: number;
    life: number;
};

/**
 * Gerbe de confettis qui jaillit du point cliqué puis retombe.
 * Un canvas plein écran, créé pour l'occasion et retiré à la fin ; rien si
 * l'utilisateur préfère réduire les animations.
 */
export function burstConfetti(originX: number, originY: number): void {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const canvas = document.createElement('canvas');
    const ratio = window.devicePixelRatio || 1;

    canvas.width = window.innerWidth * ratio;
    canvas.height = window.innerHeight * ratio;
    canvas.setAttribute('aria-hidden', 'true');
    Object.assign(canvas.style, {
        position: 'fixed',
        inset: '0',
        width: '100vw',
        height: '100vh',
        zIndex: '2147483000',
        pointerEvents: 'none',
    });
    document.body.appendChild(canvas);

    const context = canvas.getContext('2d');

    if (!context) {
        canvas.remove();

        return;
    }

    context.scale(ratio, ratio);

    const pieces: Piece[] = Array.from({ length: COUNT }, () => {
        // Gerbe vers le haut, en éventail.
        const angle = -Math.PI / 2 + (Math.random() - 0.5) * Math.PI * 0.9;
        const speed = 9 + Math.random() * 10;

        return {
            x: originX,
            y: originY,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            size: 6 + Math.random() * 7,
            color: COLORS[Math.floor(Math.random() * COLORS.length)],
            rotation: Math.random() * Math.PI * 2,
            spin: (Math.random() - 0.5) * 0.4,
            life: 1,
        };
    });

    const frame = () => {
        context.clearRect(0, 0, window.innerWidth, window.innerHeight);

        let alive = 0;

        for (const piece of pieces) {
            piece.vx *= DRAG;
            piece.vy = piece.vy * DRAG + GRAVITY;
            piece.x += piece.vx;
            piece.y += piece.vy;
            piece.rotation += piece.spin;
            piece.life -= 0.0055;

            if (piece.life <= 0 || piece.y > window.innerHeight + 40) {
                continue;
            }

            alive++;
            context.save();
            context.globalAlpha = Math.min(1, piece.life * 2);
            context.translate(piece.x, piece.y);
            context.rotate(piece.rotation);
            context.fillStyle = piece.color;
            context.fillRect(-piece.size / 2, -piece.size / 4, piece.size, piece.size / 2);
            context.restore();
        }

        if (alive > 0) {
            requestAnimationFrame(frame);
        } else {
            canvas.remove();
        }
    };

    requestAnimationFrame(frame);
}
