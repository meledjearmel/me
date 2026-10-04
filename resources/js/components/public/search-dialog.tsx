import { router, useHttp } from '@inertiajs/react';
import { useEffect, useId, useState } from 'react';
import type { KeyboardEvent } from 'react';
import SearchController from '@/actions/App/Http/Controllers/SearchController';
import PubDialog from '@/components/public/pub-dialog';
import { useLocale, useTranslations } from '@/lib/i18n';

type SearchResult = {
    type: 'post' | 'project' | 'skill';
    title: string;
    excerpt: string | null;
    url: string;
};

const MIN_LENGTH = 2;
const DEBOUNCE_MS = 200;

/**
 * Recherche globale (Ctrl+K ou ⌘K) : articles, projets et compétences.
 * Les flèches parcourent les résultats, Entrée ouvre celui qui est surligné.
 */
export default function SearchDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { submit } = useHttp();
    const listId = useId();
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [active, setActive] = useState(0);
    const term = query.trim();

    useEffect(() => {
        if (term.length < MIN_LENGTH) {
            setResults(null);

            return;
        }

        let cancelled = false;
        const timer = window.setTimeout(async () => {
            try {
                const response = (await submit(
                    SearchController(locale, { query: { q: term } }),
                )) as { results: SearchResult[] };

                if (!cancelled) {
                    setResults(response.results);
                    setFailed(false);
                    setActive(0);
                }
            } catch {
                if (!cancelled) {
                    setFailed(true);
                }
            }
        }, DEBOUNCE_MS);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [term, locale, submit]);

    const close = (next: boolean) => {
        onOpenChange(next);

        if (!next) {
            setQuery('');
            setResults(null);
        }
    };

    const go = (result: SearchResult) => {
        close(false);
        router.visit(result.url);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (!results || results.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            setActive(
                (current) =>
                    (current + step + results.length) % results.length,
            );
        } else if (event.key === 'Enter') {
            event.preventDefault();
            go(results[active]);
        }
    };

    const status =
        term.length < MIN_LENGTH
            ? t.search.hint
            : failed
              ? t.search.failed
              : results?.length === 0
                ? t.search.empty
                : null;

    return (
        <PubDialog
            open={open}
            onOpenChange={close}
            title={t.search.title}
            className="pub-modal--search"
        >
            <input
                type="search"
                className="pub-search__input"
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                onKeyDown={onKeyDown}
                placeholder={t.search.placeholder}
                aria-label={t.search.label}
                role="combobox"
                aria-expanded={!!results?.length}
                aria-controls={listId}
                aria-activedescendant={
                    results?.length ? `${listId}-${active}` : undefined
                }
                autoComplete="off"
                autoFocus
            />

            <p className="pub-search__status" role="status">
                {status}
            </p>

            {results && results.length > 0 && (
                <ul id={listId} className="pub-search__results" role="listbox">
                    {results.map((result, index) => (
                        <li
                            key={`${result.type}-${result.url}`}
                            id={`${listId}-${index}`}
                            role="option"
                            aria-selected={index === active}
                            className="pub-search__result"
                            onMouseEnter={() => setActive(index)}
                            onClick={() => go(result)}
                        >
                            <span className="pub-search__type">
                                {t.search.types[result.type]}
                            </span>
                            <strong>{result.title}</strong>
                            {result.excerpt && <span>{result.excerpt}</span>}
                        </li>
                    ))}
                </ul>
            )}
        </PubDialog>
    );
}

/** Ouvre la recherche au raccourci Ctrl+K (⌘K sur Mac). */
export function useSearchShortcut(onOpen: () => void) {
    useEffect(() => {
        const onKeyDown = (event: globalThis.KeyboardEvent) => {
            if (
                (event.ctrlKey || event.metaKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                onOpen();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [onOpen]);
}
