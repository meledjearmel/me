/**
 * Découpe un texte en lettres : chacune s'incline, se soulève et se colorie
 * au survol (voir .pub-letter dans public.css). Les lettres d'un même mot sont
 * regroupées dans un bloc insécable : le texte ne peut passer à la ligne
 * qu'entre deux mots, jamais au milieu d'un mot.
 */
export default function Letters({ text }: { text: string }) {
    return (
        <>
            {text.split(' ').map((word, wordIndex) => (
                <span key={wordIndex}>
                    {wordIndex > 0 && ' '}
                    <span className="pub-word">
                        {Array.from(word).map((char, index) => (
                            <span key={index} className="pub-letter">
                                {char}
                            </span>
                        ))}
                    </span>
                </span>
            ))}
        </>
    );
}
