import { useEffect, useRef } from 'react';

/**
 * Étoile filante : une seule à la fois, qui traverse le hero de gauche à droite
 * toutes les 5 à 13 secondes, avec un trait qui s'efface derrière une tête
 * lumineuse. Ce n'est volontairement pas une animation CSS infinie : elle ne
 * dure qu'environ 1,5 s puis attend, donc rien ne tourne entre deux passages.
 * Elle ne part que la nuit et quand le hero est encore à l'écran.
 */
export default function ShootingStar() {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const star = ref.current;
        const hero = star?.parentElement;

        if (!star || !hero) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const root = document.documentElement;
        let timer = 0;
        let flying = false;
        let wasNight = root.classList.contains('dark');

        const random = (min: number, max: number) =>
            min + Math.random() * (max - min);
        const isNight = () => root.classList.contains('dark');
        // Le hero est épinglé : il reste « visible » même une fois recouvert par
        // la suite, donc on regarde plutôt si la page a défilé au-delà de sa hauteur.
        const onHero = () => window.scrollY < hero.offsetHeight * 0.9;
        const schedule = (delay: number) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(fire, delay);
        };

        function fire() {
            if (!star || !hero) {
                return;
            }

            if (!isNight() || !onHero()) {
                schedule(4000);

                return;
            }

            const height = hero.offsetHeight;
            const width = hero.offsetWidth;

            // Départ en haut et descente peu profonde, pour ne pas traverser le titre.
            star.style.setProperty('--m-top', `${Math.round(random(0.05, 0.28) * height)}px`);
            star.style.setProperty('--m-len', `${Math.round(random(130, 260))}px`);
            star.style.setProperty('--m-angle', `${random(7, 13).toFixed(1)}deg`);
            star.style.setProperty('--m-travel', `${Math.round(width * 1.15 + 260)}px`);
            star.style.animation = 'none';
            void star.offsetWidth; // relecture : sans elle, la même animation ne repart pas
            star.style.animation = `pub-meteor-fly ${random(1.1, 1.8).toFixed(2)}s linear`;
        }

        const onStart = () => {
            flying = true;
        };
        const onEnd = () => {
            flying = false;
            star.style.animation = 'none';
            schedule(random(5200, 13000));
        };

        star.addEventListener('animationstart', onStart);
        star.addEventListener('animationend', onEnd);

        // Au passage en mode nuit, la première étoile part tout de suite.
        const observer = new MutationObserver(() => {
            const night = isNight();

            if (night && !wasNight && !flying) {
                schedule(600);
            }

            wasNight = night;
        });

        observer.observe(root, { attributes: true, attributeFilter: ['class'] });
        schedule(1500);

        return () => {
            window.clearTimeout(timer);
            observer.disconnect();
            star.removeEventListener('animationstart', onStart);
            star.removeEventListener('animationend', onEnd);
        };
    }, []);

    return <div ref={ref} className="pub-meteor" aria-hidden="true" />;
}
