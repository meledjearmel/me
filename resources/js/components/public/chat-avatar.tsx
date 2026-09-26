import type { CSSProperties } from 'react';
import armiDay from '@/avatar/armi-day.avatar.json';
import lumiNight from '@/avatar/lumi-night.avatar.json';
import { useAvatarPlayback } from '@/hooks/use-avatar-playback';
import { layoutFace } from '@/lib/avatar';
import type { AvatarDefinition } from '@/lib/avatar';

/** États que le chat pilote ; chacun est une animation des fichiers armi-day / lumi-night. */
export type ChatAvatarState =
    | 'sleeping'
    | 'waking'
    | 'idle'
    | 'listening'
    | 'thinking'
    | 'happy'
    | 'confused'
    | 'drowsy';

const DAY = armiDay as AvatarDefinition;
const NIGHT = lumiNight as AvatarDefinition;

/**
 * Avatar du chat : Armi le jour, Lumi la nuit.
 *
 * Dessiné en CSS et entièrement piloté par les fichiers `*.avatar.json` : les
 * couleurs, la taille du corps, les oreillettes, les expressions (tête et yeux)
 * et les animations (étapes, durées, clignements) y sont définis. Modifier un
 * fichier change l'avatar sans toucher au code.
 */
export default function ChatAvatar({
    state,
    size = 56,
    night = false,
    onAnimationEnd,
}: {
    state: ChatAvatarState;
    size?: number;
    night?: boolean;
    /** Appelé quand une animation jouée une seule fois (le réveil) arrive à son terme. */
    onAnimationEnd?: () => void;
}) {
    const definition = night ? NIGHT : DAY;
    const playback = useAvatarPlayback(definition, state, onAnimationEnd);
    const expression =
        definition.expressions[playback.expression] ??
        definition.expressions.neutral;
    const face = layoutFace(definition, expression, size);
    const scale = size / definition.body.primary.width;
    const { primary } = definition.body;

    const style = {
        '--avatar-size': `${size}px`,
        '--avatar-ms': `${playback.transitionMs}ms`,
        '--avatar-blink-ms': `${playback.blinkMs}ms`,
        '--avatar-body': definition.colors.body,
        '--avatar-eyes': definition.colors.eyes,
    } as CSSProperties;

    return (
        <span
            className={`pub-chatavatar is-${state}`}
            style={style}
            aria-hidden="true"
        >
            {definition.body.nodes
                .filter((node) => node.surface.type === 'sphere')
                .map((node, index) => (
                    <span
                        key={index}
                        className="pub-chatavatar__ear"
                        style={{
                            left: size / 2 + node.position[0] * scale,
                            top: size / 2 + node.position[1] * scale,
                            width: node.surface.width * scale,
                            height: node.surface.height * scale,
                            translate: `calc(-50% + ${face.earShift}px) -50%`,
                        }}
                    />
                ))}
            <span
                className="pub-chatavatar__body"
                style={{
                    left: size / 2,
                    top: size / 2,
                    width: primary.width * scale,
                    height: primary.height * scale,
                }}
            />
            <span
                className="pub-chatavatar__face"
                style={{ transform: face.faceTransform }}
            >
                {([face.eyes.left, face.eyes.right] as const).map(
                    (eye, index) => (
                        <span
                            key={index}
                            className="pub-chatavatar__eye"
                            style={{
                                left: eye.left,
                                top: eye.top,
                                width: eye.width,
                                height: eye.height,
                                rotate: `${eye.rotate}deg`,
                                scale: playback.blinking ? '1 0.1' : '1 1',
                            }}
                        />
                    ),
                )}
            </span>
        </span>
    );
}
