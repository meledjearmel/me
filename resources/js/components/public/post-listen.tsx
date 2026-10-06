import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { Pause, Play, Square, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import type { RefObject } from 'react';
import { useAudioPlayer } from '@/lib/audio';
import { useTranslations } from '@/lib/i18n';

const RATES = [0.8, 1, 1.25, 1.5, 2];

const BLOCKS = 'h2, h3, h4, p, li, blockquote';

const VOICE_KEY = 'post-listen-voice';

/** Au-delà, certaines voix en ligne de Chrome se coupent en pleine phrase. */
const MAX_CHUNK = 220;

type Status = 'idle' | 'playing' | 'paused';

/** Blocs lisibles de l'article, dans l'ordre, sans doublons imbriqués ni code. */
function readableBlocks(article: HTMLElement): HTMLElement[] {
    return Array.from(article.querySelectorAll<HTMLElement>(BLOCKS)).filter(
        (element) =>
            !element.closest('pre, figure') &&
            !element.querySelector(BLOCKS) &&
            (element.innerText ?? '').trim() !== '',
    );
}

/** Découpe un texte en morceaux de phrases entières, sous MAX_CHUNK caractères. */
function chunkText(text: string): string[] {
    const sentences = text.match(/[^.!?…;:]+[.!?…;:]*\s*/g) ?? [text];
    const chunks: string[] = [];
    let current = '';

    for (const sentence of sentences) {
        if (current && (current + sentence).length > MAX_CHUNK) {
            chunks.push(current.trim());
            current = '';
        }

        current += sentence;
    }

    if (current.trim()) {
        chunks.push(current.trim());
    }

    return chunks;
}

/**
 * Note d'une voix : les voix neuronales (« Natural » d'Edge, voix Google,
 * voix améliorées d'Apple) passent devant les voix système robotiques.
 */
function voiceScore(voice: SpeechSynthesisVoice): number {
    if (/natural|neural/i.test(voice.name)) {
        return 4;
    }

    if (/online|google/i.test(voice.name)) {
        return 3;
    }

    if (/premium|enhanced|siri/i.test(voice.name)) {
        return 2;
    }

    return voice.localService ? 0 : 1;
}

/** Voix disponibles pour la langue de l'article, les meilleures d'abord. */
function voicesFor(lang: string): SpeechSynthesisVoice[] {
    const base = lang.split('-')[0].toLowerCase();

    return window.speechSynthesis
        .getVoices()
        .filter((voice) => voice.lang.toLowerCase().startsWith(base))
        .sort((a, b) => voiceScore(b) - voiceScore(a));
}

function readStoredVoice(): string | null {
    try {
        return window.localStorage.getItem(VOICE_KEY);
    } catch {
        return null;
    }
}

function storeVoice(name: string): void {
    try {
        window.localStorage.setItem(VOICE_KEY, name);
    } catch {
        // Stockage indisponible (navigation privée) : le choix vaut pour la page.
    }
}

/**
 * Lecture à voix haute de l'article par la synthèse vocale du navigateur.
 * Toute la suite de l'article est mise en file d'un coup pour que le moteur
 * enchaîne sans blanc ; le paragraphe lu est surligné et suivi à l'écran, et
 * un clic sur un paragraphe reprend la lecture à partir de lui. Quand le
 * lecteur sort de l'écran, un mini-lecteur translucide prend le relais
 * au-dessus du bouton « Passer à l'action ».
 */
export default function PostListen({
    article,
    lang,
    html,
}: {
    article: RefObject<HTMLElement | null>;
    lang: string;
    /** Corps de l'article, pour recalculer les blocs quand il change. */
    html: string;
}) {
    const t = useTranslations();
    const music = useAudioPlayer();
    const [supported, setSupported] = useState(false);
    const [status, setStatus] = useState<Status>('idle');
    const [rate, setRate] = useState(1);
    const [current, setCurrent] = useState(0);
    const [total, setTotal] = useState(0);
    const [voices, setVoices] = useState<SpeechSynthesisVoice[]>([]);
    const [voiceName, setVoiceName] = useState<string | null>(null);
    const blocksRef = useRef<HTMLElement[]>([]);
    const indexRef = useRef(0);
    const rateRef = useRef(1);
    const voiceRef = useRef<SpeechSynthesisVoice | null>(null);
    const statusRef = useRef<Status>('idle');
    const generationRef = useRef(0);
    const playerRef = useRef<HTMLDivElement>(null);
    const [playerHidden, setPlayerHidden] = useState(false);
    const [miniHost, setMiniHost] = useState<HTMLElement | null>(null);
    const reduceMotion = useReducedMotion();

    // Le mini-lecteur n'apparaît que lorsque le lecteur complet a quitté l'écran.
    useEffect(() => {
        const element = playerRef.current;

        if (!element) {
            return;
        }

        // Hors de .pub, le mini-lecteur perdrait les couleurs du thème.
        setMiniHost(element.closest<HTMLElement>('.pub') ?? document.body);

        if (!('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver(([entry]) =>
            setPlayerHidden(!entry.isIntersecting),
        );
        observer.observe(element);

        return () => observer.disconnect();
    }, [supported]);

    // Les voix arrivent de façon asynchrone, surtout sur Chrome.
    useEffect(() => {
        if (!('speechSynthesis' in window)) {
            return;
        }

        setSupported(true);

        const load = () => {
            const available = voicesFor(lang);
            const stored = readStoredVoice();
            const chosen =
                available.find((voice) => voice.name === stored) ??
                available[0] ??
                null;
            setVoices(available);
            voiceRef.current = chosen;
            setVoiceName(chosen?.name ?? null);
        };

        load();
        window.speechSynthesis.addEventListener('voiceschanged', load);

        return () =>
            window.speechSynthesis.removeEventListener('voiceschanged', load);
    }, [lang]);

    const updateStatus = (next: Status) => {
        statusRef.current = next;
        setStatus(next);
    };

    const highlight = (index: number | null) => {
        blocksRef.current.forEach((block, position) =>
            block.classList.toggle('is-speaking', position === index),
        );
    };

    const finish = () => {
        highlight(null);
        updateStatus('idle');
        indexRef.current = 0;
        setCurrent(0);
    };

    const speak = useCallback(
        (index: number) => {
            const synth = window.speechSynthesis;
            const generation = ++generationRef.current;
            synth.cancel();

            const blocks = blocksRef.current.slice(index);

            if (blocks.length === 0) {
                finish();

                return;
            }

            const isCurrent = () => generationRef.current === generation;

            blocks.forEach((block, offset) => {
                const position = index + offset;
                const chunks = chunkText(block.innerText.trim());

                chunks.forEach((chunk, chunkIndex) => {
                    const utterance = new SpeechSynthesisUtterance(chunk);
                    utterance.lang = lang;
                    utterance.voice = voiceRef.current;
                    utterance.rate = rateRef.current;

                    if (chunkIndex === 0) {
                        utterance.onstart = () => {
                            if (!isCurrent()) {
                                return;
                            }

                            indexRef.current = position;
                            setCurrent(position);
                            highlight(position);

                            const { top, bottom } =
                                block.getBoundingClientRect();

                            if (top < 120 || bottom > window.innerHeight - 40) {
                                block.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center',
                                });
                            }
                        };
                    }

                    if (
                        offset === blocks.length - 1 &&
                        chunkIndex === chunks.length - 1
                    ) {
                        utterance.onend = () => {
                            if (
                                isCurrent() &&
                                statusRef.current === 'playing'
                            ) {
                                finish();
                            }
                        };
                    }

                    synth.speak(utterance);
                });
            });

            indexRef.current = index;
            setCurrent(index);
            highlight(index);
        },
        [lang],
    );

    const play = (index: number) => {
        blocksRef.current = article.current
            ? readableBlocks(article.current)
            : [];
        setTotal(blocksRef.current.length);

        if (music.playing) {
            music.toggle();
        }

        updateStatus('playing');
        speak(index);
    };

    const pause = () => {
        updateStatus('paused');
        window.speechSynthesis.pause();
    };

    const resume = () => {
        updateStatus('playing');
        window.speechSynthesis.resume();

        // Certaines voix en ligne ne reprennent pas : on relit le paragraphe.
        window.setTimeout(() => {
            const synth = window.speechSynthesis;

            if (
                statusRef.current === 'playing' &&
                (!synth.speaking || synth.paused)
            ) {
                speak(indexRef.current);
            }
        }, 400);
    };

    const stop = () => {
        generationRef.current++;
        window.speechSynthesis.cancel();
        finish();
    };

    /** Relance le paragraphe en cours avec les nouveaux réglages. */
    const restartIfPlaying = () => {
        if (statusRef.current === 'playing') {
            speak(indexRef.current);
        }
    };

    const changeRate = () => {
        const next = RATES[(RATES.indexOf(rate) + 1) % RATES.length];
        rateRef.current = next;
        setRate(next);
        restartIfPlaying();
    };

    const changeVoice = (name: string) => {
        voiceRef.current = voices.find((voice) => voice.name === name) ?? null;
        setVoiceName(name);
        storeVoice(name);
        restartIfPlaying();
    };

    const togglePlayback = () => {
        if (status === 'playing') {
            pause();
        } else if (status === 'paused') {
            resume();
        } else {
            play(0);
        }
    };

    /** Ramène l'écran au paragraphe en cours de lecture. */
    const scrollToCurrent = () => {
        blocksRef.current[indexRef.current]?.scrollIntoView({
            behavior: reduceMotion ? 'auto' : 'smooth',
            block: 'center',
        });
    };

    // Un clic sur un paragraphe pendant la lecture la reprend à cet endroit.
    useEffect(() => {
        const element = article.current;

        if (!element || status === 'idle') {
            return;
        }

        const onClick = (event: MouseEvent) => {
            const target = event.target as HTMLElement;

            if (target.closest('a, button')) {
                return;
            }

            const index = blocksRef.current.findIndex((block) =>
                block.contains(target),
            );

            if (index !== -1) {
                updateStatus('playing');
                speak(index);
            }
        };

        element.classList.add('is-listening');
        element.addEventListener('click', onClick);

        return () => {
            element.classList.remove('is-listening');
            element.removeEventListener('click', onClick);
        };
    }, [article, status, speak]);

    // Le corps change (autre article) ou la page se ferme : on se tait.
    useEffect(() => {
        return () => {
            generationRef.current++;

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }

            blocksRef.current.forEach((block) =>
                block.classList.remove('is-speaking'),
            );
            statusRef.current = 'idle';
            setStatus('idle');
            indexRef.current = 0;
        };
    }, [html]);

    if (!supported) {
        return null;
    }

    const progress = total > 0 ? (current + 1) / total : 0;
    const showMini = status !== 'idle' && playerHidden;
    // Titre de la section lue : le dernier intertitre avant le paragraphe en cours.
    const sectionTitle =
        blocksRef.current
            .slice(0, current + 1)
            .reverse()
            .find((block) => /^H[234]$/.test(block.tagName))
            ?.innerText.trim() ?? null;

    return (
        <>
            <div
                ref={playerRef}
                className={`pub-listen${status !== 'idle' ? ' is-active' : ''}`}
                role="group"
                aria-label={t.blog.listenLabel}
            >
                <button
                    type="button"
                    className="pub-listen__play"
                    onClick={togglePlayback}
                    aria-label={
                        status === 'playing'
                            ? t.blog.listenPause
                            : t.blog.listenPlay
                    }
                >
                    {status === 'playing' ? (
                        <Pause className="size-4" aria-hidden="true" />
                    ) : (
                        <Play className="size-4" aria-hidden="true" />
                    )}
                </button>
                <div className="pub-listen__body">
                    <p className="pub-listen__title">
                        {status === 'idle'
                            ? t.blog.listenTitle
                            : t.blog.listenProgress(current + 1, total)}
                    </p>
                    {status === 'idle' ? (
                        <p className="pub-listen__hint">{t.blog.listenHint}</p>
                    ) : (
                        <span className="pub-listen__bar" aria-hidden="true">
                            <span
                                style={{ transform: `scaleX(${progress})` }}
                            />
                        </span>
                    )}
                </div>
                {voices.length > 1 && (
                    <select
                        className="pub-listen__voice"
                        value={voiceName ?? ''}
                        onChange={(event) => changeVoice(event.target.value)}
                        aria-label={t.blog.listenVoice}
                    >
                        {voices.map((voice) => (
                            <option key={voice.name} value={voice.name}>
                                {voice.name
                                    .replace(/^(Microsoft|Google)\s+/, '')
                                    .replace(/\s*\(.*\)$/, '')
                                    .replace(/\s+Online.*$/, '')}
                            </option>
                        ))}
                    </select>
                )}
                <button
                    type="button"
                    className="pub-listen__rate"
                    onClick={changeRate}
                    aria-label={t.blog.listenRate}
                >
                    {rate}×
                </button>
                {status !== 'idle' && (
                    <button
                        type="button"
                        className="pub-listen__stop"
                        onClick={stop}
                        aria-label={t.blog.listenStop}
                    >
                        <Square className="size-3.5" aria-hidden="true" />
                    </button>
                )}
            </div>
            {miniHost &&
                createPortal(
                    <AnimatePresence>
                        {showMini && (
                            <motion.div
                                className="pub-listen-mini"
                                role="group"
                                aria-label={t.blog.listenLabel}
                                initial={
                                    reduceMotion
                                        ? { opacity: 0 }
                                        : { opacity: 0, y: 16, scale: 0.94 }
                                }
                                animate={{ opacity: 1, y: 0, scale: 1 }}
                                exit={
                                    reduceMotion
                                        ? { opacity: 0 }
                                        : { opacity: 0, y: 12, scale: 0.96 }
                                }
                                transition={{
                                    type: 'spring',
                                    stiffness: 420,
                                    damping: 34,
                                }}
                            >
                                <button
                                    type="button"
                                    className="pub-listen-mini__play"
                                    onClick={togglePlayback}
                                    aria-label={
                                        status === 'playing'
                                            ? t.blog.listenPause
                                            : t.blog.listenPlay
                                    }
                                >
                                    {status === 'playing' ? (
                                        <Pause
                                            className="size-4"
                                            fill="currentColor"
                                            aria-hidden="true"
                                        />
                                    ) : (
                                        <Play
                                            className="size-4 translate-x-px"
                                            fill="currentColor"
                                            aria-hidden="true"
                                        />
                                    )}
                                </button>
                                <button
                                    type="button"
                                    className="pub-listen-mini__info"
                                    onClick={scrollToCurrent}
                                    aria-label={t.blog.listenWhere}
                                >
                                    <span className="pub-listen-mini__eyebrow">
                                        <span
                                            className={`pub-listen-mini__eq${status === 'playing' ? ' is-playing' : ''}`}
                                            aria-hidden="true"
                                        >
                                            <i />
                                            <i />
                                            <i />
                                        </span>
                                        {status === 'playing'
                                            ? t.blog.listenNow
                                            : t.blog.listenPaused}
                                        <span className="pub-listen-mini__count">
                                            {current + 1}/{total}
                                        </span>
                                    </span>
                                    <span className="pub-listen-mini__section">
                                        {sectionTitle ?? t.blog.listenTitle}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    className="pub-listen-mini__rate"
                                    onClick={changeRate}
                                    aria-label={t.blog.listenRate}
                                >
                                    {rate}×
                                </button>
                                <button
                                    type="button"
                                    className="pub-listen-mini__stop"
                                    onClick={stop}
                                    aria-label={t.blog.listenStop}
                                >
                                    <X className="size-4" aria-hidden="true" />
                                </button>
                                <span
                                    className="pub-listen-mini__progress"
                                    aria-hidden="true"
                                >
                                    <span
                                        style={{
                                            transform: `scaleX(${progress})`,
                                        }}
                                    />
                                </span>
                            </motion.div>
                        )}
                    </AnimatePresence>,
                    miniHost,
                )}
        </>
    );
}
