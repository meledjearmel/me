import { animate, useInView, useReducedMotion } from 'framer-motion';
import { useEffect, useRef } from 'react';
import { useTranslations } from '@/lib/i18n';

/**
 * Années d'expérience, en grand : le chiffre monte jusqu'à sa valeur quand la
 * section entre dans l'écran (il s'affiche directement si le visiteur préfère
 * réduire les animations).
 */
export default function AboutYears({ years }: { years: number }) {
    const t = useTranslations();
    const numberRef = useRef<HTMLSpanElement>(null);
    const sectionRef = useRef<HTMLElement>(null);
    const inView = useInView(sectionRef, { once: true, amount: 0.4 });
    const reduceMotion = useReducedMotion();

    // Le serveur affiche la vraie valeur (utile sans JavaScript) ; on repart de 0 pour le décompte.
    useEffect(() => {
        if (!reduceMotion && numberRef.current) {
            numberRef.current.textContent = '0';
        }
    }, [reduceMotion]);

    useEffect(() => {
        if (!inView || reduceMotion || !numberRef.current) {
            return;
        }

        const element = numberRef.current;
        const controls = animate(0, years, {
            duration: 1.6,
            ease: [0.22, 1, 0.36, 1],
            onUpdate: (value) => {
                element.textContent = String(Math.round(value));
            },
        });

        return () => controls.stop();
    }, [inView, reduceMotion, years]);

    if (years <= 0) {
        return null;
    }

    return (
        <section ref={sectionRef} className="pub-years" aria-label={t.about.yearsLabel}>
            <div className="site-wrap">
                <div className="pub-years__inner">
                    <p className="pub-years__num" aria-hidden="true">
                        <span ref={numberRef}>{years}</span>
                    </p>

                    <div>
                        <p className="pub-years__label">
                            <span className="sr-only">{years} </span>
                            {t.about.yearsLabel}
                        </p>
                        <p className="pub-years__note">{t.about.yearsNote}</p>
                    </div>
                </div>
            </div>
        </section>
    );
}
