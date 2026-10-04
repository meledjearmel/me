import { playSynth, tone } from '@/lib/synth';

const VOLUME = 0.2;

/**
 * Son d'Armi quand on clique dessus : un « mrrp ? » de chat qui monte à l'ouverture du chat,
 * un petit miaulement qui descend à la fermeture. La nuit, Lumi répond plus bas et plus
 * doucement, à moitié endormie.
 */
export function playArmiSound({
    opening,
    night,
}: {
    opening: boolean;
    night: boolean;
}): void {
    const pitch = night ? 0.78 : 1;
    const type: OscillatorType = night ? 'sine' : 'triangle';

    playSynth(
        (ctx, out) => {
            if (opening) {
                // Petit roulement « mr-rp » : deux notes rapides qui montent.
                tone(ctx, out, {
                    frequency: 520 * pitch,
                    to: 760 * pitch,
                    type,
                    duration: 0.08,
                    volume: 0.7,
                });
                tone(ctx, out, {
                    frequency: 640 * pitch,
                    to: 1040 * pitch,
                    type,
                    start: 0.075,
                    duration: 0.14,
                    volume: 0.8,
                });

                return;
            }

            // « mew » : une note qui monte à peine puis redescend.
            tone(ctx, out, {
                frequency: 700 * pitch,
                to: 860 * pitch,
                type,
                duration: 0.06,
                volume: 0.6,
            });
            tone(ctx, out, {
                frequency: 860 * pitch,
                to: 460 * pitch,
                type,
                start: 0.06,
                duration: 0.18,
                volume: 0.55,
            });
        },
        night ? VOLUME * 0.7 : VOLUME,
    );
}
