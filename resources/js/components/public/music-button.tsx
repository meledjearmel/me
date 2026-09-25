import { useAudioPlayer } from '@/lib/audio';
import { useTranslations } from '@/lib/i18n';

export default function MusicButton() {
    const t = useTranslations();
    const { playing, toggle } = useAudioPlayer();

    return (
        <button
            type="button"
            className={`pub-round pub-wave${playing ? ' is-playing' : ''}`}
            aria-label={playing ? t.hero.musicOff : t.hero.musicOn}
            aria-pressed={playing}
            onClick={toggle}
        >
            <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
                <defs>
                    <clipPath id="pub-wave-clip">
                        <circle cx="20" cy="20" r="14.5" />
                    </clipPath>
                </defs>
                <g clipPath="url(#pub-wave-clip)">
                    <g className="pub-wave__travel">
                        <path
                            className="pub-wave__path"
                            d="M-20 20 C -17.5 11.1 -12.5 11.1 -10 20 C -7.5 28.9 -2.5 28.9 0 20 C 2.5 11.1 7.5 11.1 10 20 C 12.5 28.9 17.5 28.9 20 20 C 22.5 11.1 27.5 11.1 30 20 C 32.5 28.9 37.5 28.9 40 20 C 42.5 11.1 47.5 11.1 50 20 C 52.5 28.9 57.5 28.9 60 20"
                        />
                    </g>
                </g>
            </svg>
        </button>
    );
}
