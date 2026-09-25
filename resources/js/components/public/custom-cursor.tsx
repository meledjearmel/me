import { useEffect, useRef } from 'react';

const HOT_SELECTOR =
    'a, button, [role="button"], [role="slider"], input, textarea, select, label, summary, [data-cursor="hot"]';

/**
 * Curseur personnalisé : un point qui suit exactement la souris et un anneau
 * qui le suit avec un léger retard. L'anneau grossit sur les éléments
 * cliquables, se pince au clic et devient une bulle avec un texte quand
 * l'élément survolé porte data-cursor-label.
 * Actif seulement avec une vraie souris : le tactile garde le curseur natif.
 */
export default function CustomCursor() {
    const dotRef = useRef<HTMLDivElement>(null);
    const ringRef = useRef<HTMLDivElement>(null);
    const labelRef = useRef<HTMLSpanElement>(null);

    useEffect(() => {
        const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

        if (!finePointer.matches) {
            return;
        }

        const dot = dotRef.current;
        const ring = ringRef.current;
        const label = labelRef.current;

        if (!dot || !ring || !label) {
            return;
        }

        const root = document.documentElement;
        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        const target = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
        const ringPos = { ...target };
        let frame = 0;

        root.classList.add('pub-cursor-on');

        const render = () => {
            ringPos.x += (target.x - ringPos.x) * (reduceMotion ? 1 : 0.18);
            ringPos.y += (target.y - ringPos.y) * (reduceMotion ? 1 : 0.18);
            dot.style.transform = `translate3d(${target.x}px, ${target.y}px, 0)`;
            ring.style.transform = `translate3d(${ringPos.x}px, ${ringPos.y}px, 0)`;

            const settled =
                Math.abs(target.x - ringPos.x) < 0.1 &&
                Math.abs(target.y - ringPos.y) < 0.1;

            frame = settled ? 0 : requestAnimationFrame(render);
        };

        const schedule = () => {
            if (!frame) {
                frame = requestAnimationFrame(render);
            }
        };

        const onMove = (event: PointerEvent) => {
            if (event.pointerType && event.pointerType !== 'mouse') {
                return;
            }

            target.x = event.clientX;
            target.y = event.clientY;

            const element = event.target instanceof Element ? event.target : null;
            const labelled = element?.closest<HTMLElement>('[data-cursor-label]');
            const hot = !labelled && !!element?.closest(HOT_SELECTOR);

            dot.classList.add('is-visible');
            ring.classList.add('is-visible');
            ring.classList.toggle('is-hot', hot);
            ring.classList.toggle('is-labelled', !!labelled);
            dot.classList.toggle('is-hidden', !!labelled);

            if (labelled) {
                label.textContent = labelled.dataset.cursorLabel ?? '';
            }

            schedule();
        };

        const onDown = () => ring.classList.add('is-down');
        const onUp = () => ring.classList.remove('is-down');
        const onLeave = () => {
            dot.classList.remove('is-visible');
            ring.classList.remove('is-visible');
        };

        window.addEventListener('pointermove', onMove, { passive: true });
        window.addEventListener('pointerdown', onDown);
        window.addEventListener('pointerup', onUp);
        document.addEventListener('mouseleave', onLeave);

        return () => {
            cancelAnimationFrame(frame);
            root.classList.remove('pub-cursor-on');
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerdown', onDown);
            window.removeEventListener('pointerup', onUp);
            document.removeEventListener('mouseleave', onLeave);
        };
    }, []);

    return (
        <>
            <div ref={ringRef} className="pub-cursor__ring" aria-hidden="true">
                <span ref={labelRef} className="pub-cursor__label" />
            </div>
            <div ref={dotRef} className="pub-cursor__dot" aria-hidden="true" />
        </>
    );
}
