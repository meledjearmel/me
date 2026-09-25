import CloudBank from '@/components/public/cloud-bank';
import ScrollText from '@/components/public/scroll-text';

/**
 * Section d'introduction : elle défile par-dessus le hero fixe. Le banc de nuages
 * en haut lui appartient (et non au hero) pour se déplacer avec elle.
 */
export default function Intro({ text }: { text: string }) {
    return (
        <section className="pub-intro" aria-label="Introduction">
            <CloudBank className="pub-intro__crest" />

            <div className="site-wrap">
                <ScrollText text={text} className="pub-intro__text" />
            </div>
        </section>
    );
}
