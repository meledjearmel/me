import { motion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';
import { useTranslations } from '@/lib/i18n';

type Polaroid = {
    src: string;
    alt: string;
    width: number;
    height: number;
    rotate: number;
    x: number;
    y: number;
    /** Point d'ancrage de l'image dans son cadre (garde le visage dans le cadre). */
    focus?: string;
};

/**
 * Photos en polaroïd empilées, qu'on peut faire glisser (comme la référence).
 * Elles passent sous le titre et restent dans le bandeau : pas besoin de les
 * remettre en place. Toucher une photo la passe au-dessus des autres.
 */
export default function Polaroids({ photos }: { photos: Polaroid[] }) {
    const t = useTranslations();
    const containerRef = useRef<HTMLDivElement>(null);
    // Le bandeau qui les contient : les photos ne peuvent pas en sortir.
    const boundsRef = useRef<HTMLElement | null>(null);
    const [order, setOrder] = useState(() => photos.map((_, index) => index));

    const bringToFront = (index: number) =>
        setOrder((current) => [...current.filter((i) => i !== index), index]);

    useEffect(() => {
        boundsRef.current =
            containerRef.current?.closest('section') ?? containerRef.current;
    }, []);

    return (
        <div ref={containerRef} className="pub-polaroids">
            {photos.map((photo, index) => (
                <motion.figure
                    key={photo.src}
                    className="pub-polaroid"
                    style={{
                        width: photo.width,
                        zIndex: order.indexOf(index) + 1,
                        rotate: photo.rotate,
                    }}
                    initial={{ x: photo.x, y: photo.y + 40, opacity: 0 }}
                    animate={{ x: photo.x, y: photo.y, opacity: 1 }}
                    transition={{ duration: 0.7, delay: 0.15 * index }}
                    drag
                    dragMomentum={false}
                    dragConstraints={boundsRef}
                    dragElastic={0.15}
                    whileDrag={{ scale: 1.04, cursor: 'grabbing' }}
                    onPointerDown={() => bringToFront(index)}
                    data-cursor-label={t.about.cursorDrag}
                >
                    <span
                        className="pub-polaroid__photo"
                        style={{
                            aspectRatio: `${photo.width} / ${photo.height}`,
                        }}
                    >
                        <img
                            src={photo.src}
                            alt={photo.alt}
                            width={photo.width}
                            height={photo.height}
                            draggable={false}
                            style={{ objectPosition: photo.focus }}
                        />
                    </span>
                </motion.figure>
            ))}
        </div>
    );
}
