import { useEffect, useState } from "react";
import PostController from "@/actions/App/Http/Controllers/Admin/PostController";
import type { Mentionable } from "@/components/admin/post-editor/mention";

/** Délai avant de chercher, pour ne pas lancer une requête par lettre tapée. */
const SEARCH_DELAY_MS = 150;

/**
 * Éléments du site mentionnables dont le nom contient la recherche. `null` ferme la
 * recherche (liste vide).
 */
export function useMentionSearch(query: string | null): Mentionable[] {
    const [items, setItems] = useState<Mentionable[]>([]);

    useEffect(() => {
        if (query === null) {
            setItems([]);

            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            fetch(PostController.mentions.url({ query: { q: query } }), {
                headers: { Accept: "application/json" },
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((body: { data: Mentionable[] }) => setItems(body.data))
                .catch(() => {});
        }, SEARCH_DELAY_MS);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [query]);

    return items;
}
