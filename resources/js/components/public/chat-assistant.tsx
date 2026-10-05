import { AnimatePresence, motion } from 'framer-motion';
import { RotateCcw, SendHorizontal, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import ChatAvatar from '@/components/public/chat-avatar';
import type { ChatAvatarState } from '@/components/public/chat-avatar';
import ChatRichText from '@/components/public/chat-rich-text';
import PostMentionCard from '@/components/public/post-mention-card';
import { useAppearance } from '@/hooks/use-appearance';
import { CHAT_MAX_LENGTH, useChat } from '@/hooks/use-chat';
import {
    useMediaQuery,
    usePrefersReducedMotion,
} from '@/hooks/use-media-query';
import { useAudioPlayer } from '@/lib/audio';
import { useCelebration } from '@/lib/celebration';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import { playArmiSound } from '@/lib/armi-sounds';
import { playSoundEffect } from '@/lib/sound-effects';

/**
 * Le réveil s'arrête à la fin de son animation ; ce délai n'est qu'un filet de
 * sécurité (par exemple si la saisie interrompt l'animation). Le sourire qui
 * suit une réponse dure HAPPY_MS.
 */
const WAKING_MS = 4500;
const HAPPY_MS = 3500;

/** Thème nuit : Lumi ; jour : Armi. Lu après l'hydratation pour ne pas diverger du rendu serveur. */
function useNight(): boolean {
    const { resolvedAppearance } = useAppearance();
    const [night, setNight] = useState(false);

    useEffect(
        () => setNight(resolvedAppearance === 'dark'),
        [resolvedAppearance],
    );

    return night;
}

/**
 * Assistant de discussion du site : un avatar en bas à gauche qui ouvre un
 * panneau de conversation. L'avatar réagit à ce qui se passe : il dort tant que
 * le panneau est fermé (ou kiffe si la musique du site joue), se réveille à
 * l'ouverture, écoute pendant la saisie, réfléchit pendant que le modèle
 * répond, puis se réjouit ou s'étonne si le service est indisponible.
 */
export default function ChatAssistant() {
    const t = useTranslations();
    const path = useLocalizedPath();
    const night = useNight();
    const compact = useMediaQuery('(max-width: 707px)');
    const name = night ? 'Lumi' : 'Armi';
    const reduceMotion = usePrefersReducedMotion();
    const chat = useChat(path('chat'));
    const { playing: musicPlaying } = useAudioPlayer();
    const surprise = useCelebration();
    const dismissSurprise = surprise.dismiss;

    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState('');
    const [focused, setFocused] = useState(false);
    const [waking, setWaking] = useState(false);
    const [happy, setHappy] = useState(false);
    // Survolé à la souris (panneau fermé) : il réfléchit, avec son petit son.
    const [hovered, setHovered] = useState(false);
    const launcher = useRef<HTMLButtonElement>(null);
    const input = useRef<HTMLInputElement>(null);
    const list = useRef<HTMLDivElement>(null);
    const answered = useRef(0);

    const fill = (text: string) => text.replace('{name}', name);
    const thinking = chat.status === 'thinking';
    const empty = chat.messages.length === 0;

    useEffect(() => {
        if (!open) {
            return;
        }

        setWaking(true);
        input.current?.focus();
        // Ouvrir le chat range la surprise, sans la bouder pour autant.
        dismissSurprise();

        const timer = window.setTimeout(() => setWaking(false), WAKING_MS);

        return () => window.clearTimeout(timer);
    }, [open, dismissSurprise]);

    // Une nouvelle réponse fait sourire l'avatar un instant.
    useEffect(() => {
        const answers = chat.messages.filter(
            (message) => message.role === 'assistant',
        ).length;

        if (open && answers > answered.current) {
            setHappy(true);
        }

        answered.current = answers;

        const timer = window.setTimeout(() => setHappy(false), HAPPY_MS);

        return () => window.clearTimeout(timer);
    }, [chat.messages, open]);

    useEffect(() => {
        list.current?.scrollTo({
            top: list.current.scrollHeight,
            behavior: reduceMotion ? 'auto' : 'smooth',
        });
    }, [chat.messages, chat.status, reduceMotion]);

    let avatarState: ChatAvatarState = 'idle';

    if (!open && surprise.visible) {
        // Il annonce une bonne nouvelle : il sautille de joie.
        avatarState = 'happy';
    } else if (!open && hovered) {
        avatarState = 'thinking';
    } else if (!open) {
        // Panneau fermé : il dort, à moins que la musique du site ne joue.
        avatarState = musicPlaying ? 'grooving' : 'sleeping';
    } else if (chat.status === 'error') {
        avatarState = chat.error === 'rate' ? 'drowsy' : 'confused';
    } else if (thinking) {
        avatarState = 'thinking';
    } else if (happy) {
        avatarState = 'happy';
    } else if (waking) {
        avatarState = 'waking';
    } else if (focused && draft !== '') {
        avatarState = 'listening';
    }

    const close = () => {
        playArmiSound({ opening: false, night });
        setOpen(false);
        launcher.current?.focus();
    };

    const submit = (event?: FormEvent) => {
        event?.preventDefault();

        if (draft.trim() === '' || thinking) {
            return;
        }

        void chat.send(draft);
        setDraft('');
    };

    const onKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            close();
        }
    };

    const errorText = {
        rate: t.chat.errorRate,
        unavailable: t.chat.errorUnavailable,
        network: t.chat.errorNetwork,
    };

    return (
        <div
            className={`pub-chat${open ? ' is-open' : ''}${surprise.visible ? ' has-surprise' : ''}`}
        >
            <AnimatePresence>
                {open && (
                    <motion.section
                        id="pub-chat-panel"
                        className="pub-chat__panel"
                        role="dialog"
                        aria-label={fill(t.chat.title)}
                        style={{ transformOrigin: 'bottom left' }}
                        initial={
                            reduceMotion
                                ? { opacity: 0 }
                                : { opacity: 0, y: 18, scale: 0.96 }
                        }
                        animate={
                            reduceMotion
                                ? { opacity: 1 }
                                : { opacity: 1, y: 0, scale: 1 }
                        }
                        exit={
                            reduceMotion
                                ? { opacity: 0 }
                                : { opacity: 0, y: 12, scale: 0.97 }
                        }
                        transition={{
                            type: 'spring',
                            stiffness: 420,
                            damping: 32,
                        }}
                        onKeyDown={onKeyDown}
                    >
                        <header className="pub-chat__head">
                            <ChatAvatar
                                state={avatarState}
                                size={48}
                                night={night}
                                onAnimationEnd={() => setWaking(false)}
                            />
                            <div className="pub-chat__who">
                                <strong>{fill(t.chat.title)}</strong>
                                <span>{t.chat.subtitle}</span>
                            </div>
                            {!empty && (
                                <button
                                    type="button"
                                    className="pub-chat__icon"
                                    aria-label={t.chat.clear}
                                    title={t.chat.clear}
                                    onClick={chat.reset}
                                >
                                    <RotateCcw size={18} aria-hidden="true" />
                                </button>
                            )}
                            <button
                                type="button"
                                className="pub-chat__icon"
                                aria-label={t.chat.close}
                                onClick={close}
                            >
                                <X size={20} aria-hidden="true" />
                            </button>
                        </header>

                        <div
                            ref={list}
                            className="pub-chat__list"
                            role="log"
                            aria-live="polite"
                        >
                            <PostMentionCard
                                article={list}
                                mentions={Object.assign(
                                    {},
                                    ...chat.messages.map(
                                        (message) => message.mentions ?? {},
                                    ),
                                )}
                            />
                            <div className="pub-chat__msg is-assistant">
                                <p>{fill(t.chat.welcome)}</p>
                            </div>

                            {chat.messages.map((message) => (
                                <div
                                    key={message.id}
                                    className={`pub-chat__msg is-${message.role}`}
                                >
                                    {message.role === 'assistant' ? (
                                        <ChatRichText
                                            text={message.content}
                                            mentions={message.mentions}
                                        />
                                    ) : (
                                        <p>{message.content}</p>
                                    )}
                                </div>
                            ))}

                            {empty && chat.status === 'idle' && (
                                <div className="pub-chat__suggestions">
                                    {t.chat.suggestions.map((suggestion) => (
                                        <button
                                            key={suggestion}
                                            type="button"
                                            onClick={() =>
                                                void chat.send(suggestion)
                                            }
                                        >
                                            {suggestion}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {thinking && (
                                <div className="pub-chat__msg is-assistant is-thinking">
                                    <span
                                        className="pub-chat__dots"
                                        aria-hidden="true"
                                    >
                                        <i />
                                        <i />
                                        <i />
                                    </span>
                                    <span className="sr-only">
                                        {fill(t.chat.thinking)}
                                    </span>
                                </div>
                            )}

                            {chat.status === 'error' && chat.error && (
                                <div className="pub-chat__error" role="alert">
                                    <p>{errorText[chat.error]}</p>
                                    <button type="button" onClick={chat.retry}>
                                        {t.chat.retry}
                                    </button>
                                </div>
                            )}
                        </div>

                        <form className="pub-chat__form" onSubmit={submit}>
                            <input
                                ref={input}
                                type="text"
                                value={draft}
                                maxLength={CHAT_MAX_LENGTH}
                                placeholder={t.chat.placeholder}
                                aria-label={t.chat.placeholder}
                                autoComplete="off"
                                onChange={(event) =>
                                    setDraft(event.target.value)
                                }
                                onFocus={() => setFocused(true)}
                                onBlur={() => setFocused(false)}
                            />
                            <button
                                type="submit"
                                className="pub-chat__send"
                                aria-label={t.chat.send}
                                disabled={draft.trim() === '' || thinking}
                            >
                                <SendHorizontal size={20} aria-hidden="true" />
                            </button>
                        </form>

                        <p className="pub-chat__note">{t.chat.disclaimer}</p>
                    </motion.section>
                )}
            </AnimatePresence>

            {/* Survolé, Armi réfléchit dans la même bulle que celle de la surprise. */}
            <AnimatePresence>
                {hovered && !open && !surprise.visible && (
                    <motion.div
                        className="pub-surprise pub-surprise--thinking is-typing"
                        aria-hidden="true"
                        style={{ transformOrigin: 'bottom left' }}
                        initial={
                            reduceMotion
                                ? { opacity: 0 }
                                : { opacity: 0, x: -12, y: 24, scale: 0.2 }
                        }
                        animate={
                            reduceMotion
                                ? { opacity: 1 }
                                : { opacity: 1, x: 0, y: 0, scale: 1 }
                        }
                        exit={
                            reduceMotion
                                ? { opacity: 0 }
                                : { opacity: 0, x: -8, y: 16, scale: 0.4 }
                        }
                        transition={{
                            type: 'spring',
                            stiffness: 420,
                            damping: 24,
                        }}
                    >
                        <span className="pub-surprise__tail" />
                        <p className="pub-surprise__typing">
                            <span />
                            <span />
                            <span />
                        </p>
                    </motion.div>
                )}
            </AnimatePresence>

            <button
                ref={launcher}
                type="button"
                className="pub-chat__launcher"
                aria-label={fill(t.chat.open)}
                aria-expanded={open}
                aria-controls="pub-chat-panel"
                onClick={() => {
                    if (open) {
                        close();
                    } else {
                        playArmiSound({ opening: true, night });
                        setHovered(false);
                        setOpen(true);
                    }
                }}
                onPointerEnter={(event) => {
                    // Au doigt, pas de survol : le tap ouvre directement le chat.
                    if (event.pointerType !== 'mouse' || open) {
                        return;
                    }

                    setHovered(true);
                    playSoundEffect('muffledReaction', 0.45);
                }}
                onPointerLeave={() => setHovered(false)}
            >
                <ChatAvatar
                    state={avatarState}
                    size={compact ? 48 : 64}
                    night={night}
                />
                <span className="pub-chat__hint" aria-hidden="true">
                    {fill(t.chat.open)}
                </span>
            </button>
        </div>
    );
}
