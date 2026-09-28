import { useCallback, useState } from "react";
import AiAssistController from "@/actions/App/Http/Controllers/Admin/AiAssistController";

export type Locale = "fr" | "en";
export type TextTone = "formal" | "friendly" | "concise" | "enthusiastic";

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : "";
}

async function post<T = { text: string }>(
    url: string,
    body: unknown,
): Promise<T> {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-XSRF-TOKEN": csrfToken(),
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(String(response.status));
    }

    return response.json();
}

/**
 * Traduction et amélioration de texte via l'assistance IA de l'admin (voir
 * AiAssistController et config/ai.php: text_assist).
 */
export function useAiTextAssist() {
    const [pending, setPending] = useState<
        "translate" | "improve" | "describe" | null
    >(null);

    const translate = useCallback(
        async (
            text: string,
            sourceLocale: Locale,
            targetLocale: Locale,
        ): Promise<string | null> => {
            setPending("translate");

            try {
                const { text: translated } = await post(
                    AiAssistController.translate.url(),
                    {
                        text,
                        source_locale: sourceLocale,
                        target_locale: targetLocale,
                    },
                );

                return translated;
            } catch {
                return null;
            } finally {
                setPending(null);
            }
        },
        [],
    );

    const improve = useCallback(
        async (
            text: string,
            locale: Locale,
            tone: TextTone | null,
            instructions: string,
        ): Promise<string | null> => {
            setPending("improve");

            try {
                const { text: improved } = await post(
                    AiAssistController.improve.url(),
                    {
                        text,
                        locale,
                        tone,
                        instructions: instructions.trim() || null,
                    },
                );

                return improved;
            } catch {
                return null;
            } finally {
                setPending(null);
            }
        },
        [],
    );

    const describeTechnology = useCallback(
        async (
            name: string,
            category: string | null,
        ): Promise<Record<Locale, string> | null> => {
            setPending("describe");

            try {
                const { description } = await post<{
                    description: Record<Locale, string>;
                }>(AiAssistController.describeTechnology.url(), {
                    name,
                    category,
                });

                return description;
            } catch {
                return null;
            } finally {
                setPending(null);
            }
        },
        [],
    );

    return { translate, improve, describeTechnology, pending };
}
