import { motion } from 'framer-motion';
import ShootingStar from '@/components/public/shooting-star';
import StarField from '@/components/public/star-field';

/**
 * Nuages du ciel (images fournies). Chacun dérive lentement vers la droite à sa
 * propre vitesse, sur trois plans : les grands et pâles au fond, les petits et
 * plus nets devant.
 */
const CLOUDS = [
    { src: '/images/hero/cloud-1.png', top: '4%', width: 'clamp(320px, 46vw, 760px)', opacity: 0.95, duration: 140, delay: 0 },
    { src: '/images/hero/cloud-3.png', top: '30%', width: 'clamp(240px, 32vw, 520px)', opacity: 0.85, duration: 105, delay: -55 },
    { src: '/images/hero/cloud-2.png', top: '12%', width: 'clamp(200px, 26vw, 420px)', opacity: 0.8, duration: 120, delay: -25 },
    { src: '/images/hero/cloud-1.png', top: '52%', width: 'clamp(260px, 36vw, 600px)', opacity: 0.6, duration: 170, delay: -110 },
    { src: '/images/hero/cloud-3.png', top: '66%', width: 'clamp(200px, 28vw, 460px)', opacity: 0.55, duration: 150, delay: -70 },
] as const;

/**
 * `full` : le ciel de l'accueil (aurore, soleil, lune, étoile filante).
 * `soft` : version calme pour les autres pages — étoiles et quelques nuages seulement.
 */
export default function HeroScene({
    variant = 'full',
}: {
    variant?: 'full' | 'soft';
}) {
    const isSoft = variant === 'soft';
    const clouds = isSoft ? CLOUDS.slice(0, 3) : CLOUDS;

    return (
        <div className="pub-scene" aria-hidden="true">
            {!isSoft && <div className="pub-scene__aurora" />}

            <StarField />
            {!isSoft && <ShootingStar />}

            {!isSoft && <div className="pub-scene__sun" />}
            {!isSoft && <div className="pub-scene__moon" />}

            {clouds.map((cloud, index) => (
                <motion.img
                    key={index}
                    className="pub-scene__cloud"
                    src={cloud.src}
                    alt=""
                    style={{
                        top: cloud.top,
                        width: cloud.width,
                        opacity: cloud.opacity,
                    }}
                    initial={{ x: '-45vw' }}
                    animate={{ x: '110vw' }}
                    transition={{
                        duration: cloud.duration,
                        delay: cloud.delay,
                        repeat: Infinity,
                        ease: 'linear',
                    }}
                />
            ))}
        </div>
    );
}
