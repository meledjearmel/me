import { motion } from 'framer-motion';
import { RotateCcw } from 'lucide-react';
import { useState } from 'react';
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
 * Photos en polaroïd empilées, qu'on peut faire glisser (comme la référence),
 * avec un bouton pour les remettre en place. Toucher une photo la passe au-dessus.
 */
export default function Polaroids({ photos }: { photos: Polaroid[] }) {
    const t = useTranslations();
    const [order, setOrder] = useState(() => photos.map((_, index) => index));
    // Changer la clé remonte les photos : elles reprennent leur position de départ.
    const [resetKey, setResetKey] = useState(0);

    const bringToFront = (index: number) =>
        setOrder((current) => [...current.filter((i) => i !== index), index]);

    const reset = () => {
        setOrder(photos.map((_, index) => index));
        setResetKey((key) => key + 1);
    };

    return (
        <div className="pub-polaroids">
            {photos.map((photo, index) => (
                <motion.figure
                    key={`${resetKey}-${photo.src}`}
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
                    whileDrag={{ scale: 1.04, cursor: 'grabbing' }}
                    onPointerDown={() => bringToFront(index)}
                    data-cursor-label={t.about.cursorDrag}
                >
                    <span
                        className="pub-polaroid__photo"
                        style={{ aspectRatio: `${photo.width} / ${photo.height}` }}
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

            <button
                type="button"
                className="pub-polaroids__reset"
                aria-label={t.about.resetPhotos}
                title={t.about.resetPhotos}
                onClick={reset}
            >
                <RotateCcw size={18} aria-hidden="true" />
            </button>
        </div>
    );
}
