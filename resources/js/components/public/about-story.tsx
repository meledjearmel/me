import ScrollText from '@/components/public/scroll-text';

/**
 * Texte de présentation, centré : chaque paragraphe s'écrit mot après mot
 * pendant qu'on défile (même mécanique que l'introduction de l'accueil).
 */
export default function AboutStory({ text }: { text: string }) {
    const paragraphs = text
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);

    return (
        <section className="pub-story" aria-label="Présentation">
            <div className="site-wrap">
                <div className="pub-story__inner">
                    {paragraphs.map((paragraph, index) => (
                        <ScrollText
                            key={index}
                            text={paragraph}
                            className="pub-story__text"
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
