/**
 * Sous-ensemble du format d'avatar (fichiers *.avatar.json) que le rendu CSS du
 * chat sait lire : corps, oreillettes (nodes), expressions et animations.
 */
export type AvatarEye = {
    width: number;
    height: number;
    x: number;
    y: number;
    angle: number;
};

export type AvatarExpression = {
    head: { x: number; y: number; z: number };
    eyes: { left: AvatarEye; right: AvatarEye; spacing: number };
};

export type AvatarStep = {
    expression: string;
    holdMs: number;
    transitionMs: number;
};

export type AvatarAnimation = {
    playbackMode: 'loop' | 'once';
    steps: AvatarStep[];
    blink: {
        enabled: boolean;
        initialDelayMs: number;
        minIntervalMs: number;
        maxIntervalMs: number;
        durationMs: number;
    };
};

export type AvatarNode = {
    surface: { type: string; width: number; height: number };
    /** x, y, z par rapport au centre du corps. */
    position: number[];
};

export type AvatarDefinition = {
    name: string;
    body: {
        primary: { width: number; height: number };
        nodes: AvatarNode[];
    };
    colors: { body: string; eyes: string };
    expressions: Record<string, AvatarExpression>;
    animations: Record<string, AvatarAnimation>;
};

/** Part de la sphère que le regard parcourt quand la tête tourne (1 = jusqu'au bord). */
const GAZE_REACH = 0.7;
/** Décalage des oreillettes quand la tête tourne : elles bougent moins que le visage. */
const EAR_PARALLAX = 0.3;
const MIN_SQUASH = 0.6;

const radians = (degrees: number) => (degrees * Math.PI) / 180;

export type FaceLayout = {
    /** Transformation de tout le visage : la tête tourne sur la sphère. */
    faceTransform: string;
    earShift: number;
    eyes: { left: EyeLayout; right: EyeLayout };
};

export type EyeLayout = {
    left: number;
    top: number;
    width: number;
    height: number;
    rotate: number;
};

/**
 * Traduit une expression en positions à l'écran, pour un avatar de `size` pixels.
 *
 * Tourner la tête (x : haut/bas, y : gauche/droite, z : inclinaison) fait
 * glisser les yeux sur la sphère et les écrase sur les côtés, ce qui donne
 * l'impression d'un volume sans 3D.
 */
export function layoutFace(
    definition: AvatarDefinition,
    expression: AvatarExpression,
    size: number,
): FaceLayout {
    const scale = size / definition.body.primary.width;
    const radius = size / 2;
    const { head, eyes } = expression;
    const yaw = radians(head.y);
    const pitch = radians(head.x);
    const shiftX = Math.sin(yaw) * radius * GAZE_REACH;
    const shiftY = -Math.sin(pitch) * radius * GAZE_REACH;
    const squash = Math.max(MIN_SQUASH, Math.cos(yaw));

    const place = (eye: AvatarEye, side: -1 | 1): EyeLayout => ({
        left: radius + side * (eyes.spacing / 2) * scale + eye.x * scale,
        top: radius + eye.y * scale,
        width: eye.width * scale,
        height: eye.height * scale,
        // Un angle positif penche le haut de l'œil vers l'extérieur : à l'inverse de la rotation CSS.
        rotate: -eye.angle,
    });

    return {
        faceTransform: `translate(${shiftX}px, ${shiftY}px) rotate(${head.z}deg) scale(${squash}, 1)`,
        earShift: -shiftX * EAR_PARALLAX,
        eyes: { left: place(eyes.left, -1), right: place(eyes.right, 1) },
    };
}
