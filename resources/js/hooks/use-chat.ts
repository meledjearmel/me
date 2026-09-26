import { useCallback, useEffect, useRef, useState } from 'react';

/** Doivent rester alignés sur config/ai.php (chat.max_history et chat.max_message_length) et ChatRequest. */
export const CHAT_MAX_HISTORY = 10;
export const CHAT_MAX_LENGTH = 500;
const HISTORY_CONTENT_LIMIT = 4000;

const STORAGE_KEY = 'pub-chat';

export type ChatMessage = {
    id: number;
    role: 'user' | 'assistant';
    content: string;
};

export type ChatStatus = 'idle' | 'thinking' | 'error';
export type ChatError = 'rate' | 'unavailable' | 'network';

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function loadMessages(): ChatMessage[] {
    try {
        const stored = JSON.parse(
            window.sessionStorage.getItem(STORAGE_KEY) ?? '[]',
        );

        return Array.isArray(stored) ? stored : [];
    } catch {
        return [];
    }
}

function saveMessages(messages: ChatMessage[]): void {
    try {
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
    } catch {
        // Stockage indisponible (navigation privée...) : la conversation vit simplement en mémoire.
    }
}

/**
 * Conversation avec l'assistant du site.
 *
 * L'historique reste dans le navigateur (session) : le serveur ne stocke rien et
 * reçoit les derniers échanges avec chaque question. Les erreurs sont ramenées à
 * trois causes que l'interface sait expliquer au visiteur.
 */
export function useChat(endpoint: string) {
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [status, setStatus] = useState<ChatStatus>('idle');
    const [error, setError] = useState<ChatError | null>(null);
    const messagesRef = useRef<ChatMessage[]>([]);
    const nextId = useRef(1);
    const controller = useRef<AbortController | null>(null);
    const lastQuestion = useRef('');

    const commit = useCallback((next: ChatMessage[]) => {
        messagesRef.current = next;
        setMessages(next);
        saveMessages(next);
    }, []);

    // Rechargée après l'hydratation pour ne pas diverger du rendu serveur.
    useEffect(() => {
        const stored = loadMessages();

        messagesRef.current = stored;
        nextId.current =
            stored.reduce((max, message) => Math.max(max, message.id), 0) + 1;
        setMessages(stored);
    }, []);

    useEffect(() => () => controller.current?.abort(), []);

    const send = useCallback(
        async (text: string) => {
            const question = text.trim().slice(0, CHAT_MAX_LENGTH);

            if (question === '' || controller.current !== null) {
                return;
            }

            const history = messagesRef.current
                .slice(-CHAT_MAX_HISTORY)
                .map(({ role, content }) => ({
                    role,
                    content: content.slice(0, HISTORY_CONTENT_LIMIT),
                }));

            lastQuestion.current = question;
            commit([
                ...messagesRef.current,
                { id: nextId.current++, role: 'user', content: question },
            ]);
            setStatus('thinking');
            setError(null);

            const abort = new AbortController();

            controller.current = abort;

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    signal: abort.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ message: question, history }),
                });

                if (!response.ok) {
                    throw new Error(String(response.status));
                }

                const data: { reply: string } = await response.json();

                commit([
                    ...messagesRef.current,
                    {
                        id: nextId.current++,
                        role: 'assistant',
                        content: data.reply,
                    },
                ]);
                setStatus('idle');
            } catch (failure) {
                if (abort.signal.aborted) {
                    return;
                }

                const code = failure instanceof Error ? failure.message : '';

                setError(
                    code === '429'
                        ? 'rate'
                        : /^\d+$/.test(code)
                          ? 'unavailable'
                          : 'network',
                );
                setStatus('error');
            } finally {
                if (controller.current === abort) {
                    controller.current = null;
                }
            }
        },
        [commit, endpoint],
    );

    /** Renvoie la dernière question après une erreur, sans la dupliquer dans la conversation. */
    const retry = useCallback(() => {
        const last = messagesRef.current.at(-1);

        if (last?.role === 'user') {
            commit(messagesRef.current.slice(0, -1));
        }

        void send(lastQuestion.current);
    }, [commit, send]);

    const reset = useCallback(() => {
        controller.current?.abort();
        controller.current = null;
        commit([]);
        setStatus('idle');
        setError(null);
    }, [commit]);

    return { messages, status, error, send, retry, reset };
}
