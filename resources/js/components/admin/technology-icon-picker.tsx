import { Check, Plus, Search, X } from 'lucide-react';
import { useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import TechnologyIconController from '@/actions/App/Http/Controllers/Admin/TechnologyIconController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { TechnologyIconOption } from '@/types';

type CatalogIcon = {
    id: string;
    name: string;
    collection: string;
    preview_url: string;
};

type ImportTheme = 'both' | 'light' | 'dark';

const THEME_CHOICES: { value: ImportTheme; label: string }[] = [
    { value: 'both', label: 'Clair et sombre' },
    { value: 'light', label: 'Thème clair seulement' },
    { value: 'dark', label: 'Thème sombre seulement' },
];

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/** Message d'erreur lisible d'une réponse JSON Laravel (422 de validation ou message simple). */
async function readError(response: Response): Promise<string> {
    const body = await response.json().catch(() => null);
    const firstError = body?.errors
        ? (Object.values(body.errors)[0] as string[] | undefined)?.[0]
        : null;

    return (
        firstError ??
        body?.message ??
        'Une erreur est survenue, réessaie dans un instant.'
    );
}

async function request<T>(url: string, init?: RequestInit): Promise<T> {
    const response = await fetch(url, {
        ...init,
        headers: {
            // Un FormData fixe lui-même son Content-Type (avec la frontière multipart).
            ...(init?.body instanceof FormData
                ? {}
                : { 'Content-Type': 'application/json' }),
            Accept: 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(await readError(response));
    }

    return response.json();
}

/** Logo d'un thème : deux images si le logo a une variante par thème, une seule sinon. */
function IconImage({ icon }: { icon: TechnologyIconOption }) {
    if (icon.light_url === icon.dark_url) {
        return (
            <img
                src={icon.light_url ?? undefined}
                alt=""
                className="size-full object-contain"
            />
        );
    }

    return (
        <>
            <img
                src={icon.light_url ?? undefined}
                alt=""
                className="size-full object-contain dark:hidden"
            />
            <img
                src={icon.dark_url ?? undefined}
                alt=""
                className="hidden size-full object-contain dark:block"
            />
        </>
    );
}

function slugFromName(name: string): string {
    return name
        .toLowerCase()
        .replace(/-(light|dark)$/, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * Choix du logo d'une technologie : grille des logos de la bibliothèque, et
 * ajout d'un nouveau logo depuis le catalogue Iconify. La valeur est envoyée
 * sous `name` (le slug du logo, ou vide).
 */
export default function TechnologyIconPicker({
    icons,
    defaultValue,
    name = 'icon',
    invalid = false,
}: {
    icons: TechnologyIconOption[];
    defaultValue?: string | null;
    name?: string;
    invalid?: boolean;
}) {
    const [library, setLibrary] = useState(icons);
    const [value, setValue] = useState(defaultValue ?? '');
    const [filter, setFilter] = useState('');

    const [catalogOpen, setCatalogOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<CatalogIcon[] | null>(null);
    const [searching, setSearching] = useState(false);
    const [picked, setPicked] = useState<CatalogIcon | null>(null);
    const [file, setFile] = useState<File | null>(null);
    const fileInput = useRef<HTMLInputElement>(null);
    const [slug, setSlug] = useState('');
    const [theme, setTheme] = useState<ImportTheme>('both');
    const [importing, setImporting] = useState(false);
    const [catalogError, setCatalogError] = useState<string | null>(null);

    const selected = library.find((icon) => icon.slug === value) ?? null;
    const visible = library.filter((icon) =>
        icon.slug.includes(filter.trim().toLowerCase()),
    );

    async function search() {
        if (query.trim().length < 2) {
            return;
        }

        setSearching(true);
        setCatalogError(null);
        setPicked(null);

        try {
            const data = await request<{ icons: CatalogIcon[] }>(
                TechnologyIconController.search.url({
                    query: { q: query.trim() },
                }),
            );

            setResults(data.icons);
        } catch (error) {
            setResults(null);
            setCatalogError((error as Error).message);
        } finally {
            setSearching(false);
        }
    }

    function pickResult(result: CatalogIcon) {
        setPicked(result);
        setFile(null);

        if (fileInput.current) {
            fileInput.current.value = '';
        }

        setSlug(slugFromName(result.name));
        setCatalogError(null);
    }

    function pickFile(selectedFile: File | null) {
        setFile(selectedFile);
        setPicked(null);
        setCatalogError(null);

        if (selectedFile) {
            setSlug(slugFromName(selectedFile.name.replace(/\.svg$/i, '')));
        }
    }

    /** Importe le logo du catalogue choisi, ou envoie le fichier SVG choisi. */
    async function importPicked() {
        if (!picked && !file) {
            return;
        }

        setImporting(true);
        setCatalogError(null);

        try {
            const themeValue = theme === 'both' ? null : theme;
            let init: RequestInit;
            let url: string;

            if (file) {
                const body = new FormData();

                body.append('file', file);
                body.append('slug', slugFromName(slug));

                if (themeValue) {
                    body.append('theme', themeValue);
                }

                url = TechnologyIconController.upload.url();
                init = { method: 'POST', body };
            } else {
                url = TechnologyIconController.store.url();
                init = {
                    method: 'POST',
                    body: JSON.stringify({
                        icon: picked?.id,
                        slug: slugFromName(slug),
                        theme: themeValue,
                    }),
                };
            }

            const data = await request<{ icon: TechnologyIconOption }>(
                url,
                init,
            );

            // Une variante ajoutée à un logo existant remplace son entrée.
            setLibrary((current) =>
                [
                    ...current.filter((icon) => icon.slug !== data.icon.slug),
                    data.icon,
                ].sort((a, b) => a.slug.localeCompare(b.slug)),
            );
            setValue(data.icon.slug);
            setPicked(null);
            pickFile(null);

            if (fileInput.current) {
                fileInput.current.value = '';
            }

            setCatalogOpen(false);
        } catch (error) {
            setCatalogError((error as Error).message);
        } finally {
            setImporting(false);
        }
    }

    function onEnter(
        event: KeyboardEvent<HTMLInputElement>,
        action: () => void,
    ) {
        if (event.key === 'Enter') {
            event.preventDefault();
            action();
        }
    }

    return (
        <div className="space-y-3">
            <input type="hidden" name={name} value={value} />

            <div
                className={cn(
                    'flex items-center gap-3 rounded-md border p-3',
                    invalid && 'border-destructive',
                )}
            >
                <div className="bg-muted flex size-12 shrink-0 items-center justify-center rounded-md p-2">
                    {selected && <IconImage icon={selected} />}
                </div>
                <div className="min-w-0 flex-1 text-sm">
                    {value === '' ? (
                        <span className="text-muted-foreground">
                            Aucun logo
                        </span>
                    ) : (
                        <>
                            <div className="truncate font-medium">{value}</div>
                            {!selected && (
                                <div className="text-destructive text-xs">
                                    Aucun fichier pour ce logo : choisis-en un
                                    ci-dessous.
                                </div>
                            )}
                        </>
                    )}
                </div>
                {value !== '' && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Retirer le logo"
                        onClick={() => setValue('')}
                    >
                        <X />
                    </Button>
                )}
            </div>

            <div className="space-y-2">
                <Input
                    type="search"
                    value={filter}
                    onChange={(event) => setFilter(event.target.value)}
                    onKeyDown={(event) => onEnter(event, () => {})}
                    placeholder={`Filtrer les ${library.length} logos…`}
                />

                <div className="grid max-h-60 grid-cols-3 gap-2 overflow-y-auto rounded-md border p-2 sm:grid-cols-4">
                    {visible.length === 0 && (
                        <p className="text-muted-foreground col-span-full py-4 text-center text-sm">
                            Aucun logo ne correspond. Ajoute-le depuis le
                            catalogue ci-dessous.
                        </p>
                    )}
                    {visible.map((icon) => (
                        <button
                            key={icon.slug}
                            type="button"
                            onClick={() => setValue(icon.slug)}
                            aria-pressed={icon.slug === value}
                            className={cn(
                                'hover:bg-accent relative flex flex-col items-center gap-1 rounded-md border p-2 text-xs',
                                icon.slug === value &&
                                    'border-primary bg-accent',
                            )}
                        >
                            <span className="flex size-9 items-center justify-center">
                                <IconImage icon={icon} />
                            </span>
                            <span className="w-full truncate text-center">
                                {icon.slug}
                            </span>
                            {icon.slug === value && (
                                <Check className="text-primary absolute top-1 right-1 size-3.5" />
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setCatalogOpen((open) => !open)}
                >
                    <Plus /> Ajouter un logo
                </Button>

                {catalogOpen && (
                    <div className="space-y-3 rounded-md border p-3">
                        <div className="flex gap-2">
                            <Input
                                type="search"
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                onKeyDown={(event) => onEnter(event, search)}
                                placeholder="Nom de la techno (ex. laravel, docker)…"
                            />
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={searching || query.trim().length < 2}
                                onClick={search}
                            >
                                {searching ? <Spinner /> : <Search />}
                                Chercher
                            </Button>
                        </div>

                        {catalogError && (
                            <p
                                className="text-destructive text-sm"
                                role="alert"
                            >
                                {catalogError}
                            </p>
                        )}

                        {results?.length === 0 && (
                            <p className="text-muted-foreground text-sm">
                                Aucun résultat pour « {query.trim()} ».
                            </p>
                        )}

                        {results && results.length > 0 && (
                            <div className="grid max-h-60 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4">
                                {results.map((result) => (
                                    <button
                                        key={result.id}
                                        type="button"
                                        onClick={() => pickResult(result)}
                                        aria-pressed={picked?.id === result.id}
                                        className={cn(
                                            'hover:bg-accent flex flex-col items-center gap-1 rounded-md border p-2 text-xs',
                                            picked?.id === result.id &&
                                                'border-primary bg-accent',
                                        )}
                                    >
                                        <span className="flex size-9 items-center justify-center rounded bg-white p-1">
                                            <img
                                                src={result.preview_url}
                                                alt=""
                                                loading="lazy"
                                                className="size-full object-contain"
                                            />
                                        </span>
                                        <span className="w-full truncate text-center">
                                            {result.name}
                                        </span>
                                        <span className="text-muted-foreground w-full truncate text-center">
                                            {result.collection}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        )}

                        <div className="border-t pt-3">
                            <label
                                htmlFor="technology-icon-file"
                                className="text-muted-foreground mb-1.5 block text-xs"
                            >
                                Ou envoyer ton propre fichier SVG (200 Ko max)
                            </label>
                            <Input
                                id="technology-icon-file"
                                ref={fileInput}
                                type="file"
                                accept=".svg,image/svg+xml"
                                onChange={(event) =>
                                    pickFile(event.target.files?.[0] ?? null)
                                }
                            />
                        </div>

                        {(picked || file) && (
                            <div className="space-y-2">
                                <div
                                    className="flex flex-wrap gap-2"
                                    role="group"
                                    aria-label="Thème du logo"
                                >
                                    {THEME_CHOICES.map((choice) => (
                                        <Button
                                            key={choice.value}
                                            type="button"
                                            size="sm"
                                            variant={
                                                theme === choice.value
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            aria-pressed={
                                                theme === choice.value
                                            }
                                            onClick={() =>
                                                setTheme(choice.value)
                                            }
                                        >
                                            {choice.label}
                                        </Button>
                                    ))}
                                </div>
                                <p className="text-muted-foreground text-xs">
                                    Pour ajouter la variante d&apos;un logo
                                    existant, reprends son nom et choisis le
                                    thème concerné.
                                </p>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Input
                                        value={slug}
                                        onChange={(event) =>
                                            setSlug(
                                                event.target.value
                                                    .toLowerCase()
                                                    .replace(/[^a-z0-9-]/g, ''),
                                            )
                                        }
                                        onKeyDown={(event) =>
                                            onEnter(event, importPicked)
                                        }
                                        aria-label="Nom du fichier du logo"
                                        className="max-w-56"
                                    />
                                    <Button
                                        type="button"
                                        disabled={importing || slug === ''}
                                        onClick={importPicked}
                                    >
                                        {importing && <Spinner />}
                                        Importer et utiliser
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
