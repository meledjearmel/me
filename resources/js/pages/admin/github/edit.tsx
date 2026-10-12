import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowLeft,
    ArrowRight,
    ArrowUp,
    Info,
    Lock,
    Plus,
    RefreshCw,
    SearchIcon,
    Star,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import GitHubController from '@/actions/App/Http/Controllers/Admin/GitHubController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { edit as pageEdit } from '@/routes/admin/github';
import ActionTooltip from '@/components/admin/action-tooltip';

type Repository = {
    full_name: string;
    name: string;
    description: string | null;
    language: string | null;
    stars: number;
    pushed_at: string | null;
    private: boolean;
    archived: boolean;
    contribution: boolean;
};

/** Dépôts par page, dans chaque onglet. */
const PER_PAGE = 6;

/** Recherche sans tenir compte des accents ni de la casse. */
const normalize = (text: string) =>
    text
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();

function RepositoryLabel({ repository }: { repository: Repository }) {
    return (
        <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-2">
                <span className="font-medium">{repository.name}</span>
                {repository.private && (
                    <Badge variant="secondary">
                        <Lock /> Privé
                    </Badge>
                )}
                {repository.contribution && (
                    <Badge variant="outline">Contribution</Badge>
                )}
                {repository.archived && (
                    <Badge variant="outline">Archivé</Badge>
                )}
            </div>
            {repository.description && (
                <p className="truncate text-sm text-muted-foreground">
                    {repository.description}
                </p>
            )}
        </div>
    );
}

/** Langage et étoiles, alignés à droite de la ligne. */
function RepositoryMeta({ repository }: { repository: Repository }) {
    return (
        <div className="hidden shrink-0 items-center gap-3 text-sm text-muted-foreground sm:flex">
            {repository.language && <span>{repository.language}</span>}
            {repository.stars > 0 && (
                <span className="inline-flex items-center gap-1 tabular-nums">
                    <Star className="size-3.5" /> {repository.stars}
                </span>
            )}
        </div>
    );
}

/** Une liste en carte, avec une ligne d'en-tête discrète. */
function ListCard({
    header,
    children,
}: {
    header: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="overflow-hidden rounded-xl border">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/50 px-4 py-2.5 text-xs font-medium text-muted-foreground">
                {header}
            </div>
            {children}
        </div>
    );
}

/** Pied de liste : « Précédent », les pages, « Suivant » ; masqué sur une seule page. */
function Pager({
    page,
    count,
    onChange,
}: {
    page: number;
    count: number;
    onChange: (page: number) => void;
}) {
    if (count <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-3 border-t px-4 py-3">
            <Button
                variant="outline"
                size="sm"
                disabled={page === 1}
                onClick={() => onChange(page - 1)}
            >
                <ArrowLeft data-icon="inline-start" />
                <span className="hidden sm:inline">Précédent</span>
            </Button>
            <div className="flex items-center gap-1">
                {Array.from({ length: count }, (_, index) => index + 1).map(
                    (number) => (
                        <Button
                            key={number}
                            variant={number === page ? 'secondary' : 'ghost'}
                            size="sm"
                            className="min-w-8"
                            aria-current={number === page ? 'page' : undefined}
                            onClick={() => onChange(number)}
                        >
                            {number}
                        </Button>
                    ),
                )}
            </div>
            <Button
                variant="outline"
                size="sm"
                disabled={page === count}
                onClick={() => onChange(page + 1)}
            >
                <span className="hidden sm:inline">Suivant</span>
                <ArrowRight data-icon="inline-end" />
            </Button>
        </div>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return (
        <p className="px-4 py-10 text-center text-sm text-muted-foreground">
            {children}
        </p>
    );
}

/**
 * Choix des dépôts GitHub de la page À propos, dans l'ordre d'affichage. Sans choix,
 * le site montre automatiquement mes dépôts publics les plus étoilés et récents.
 */
export default function GitHubEdit({
    available,
    selected: initialSelection,
    syncedAt,
    hasToken,
    maxSelected,
}: {
    available: Repository[];
    selected: string[];
    syncedAt: string | null;
    hasToken: boolean;
    maxSelected: number;
}) {
    const byName = new Map(available.map((repo) => [repo.full_name, repo]));
    const [savedSelection, setSavedSelection] = useState(() =>
        initialSelection.filter((name) => byName.has(name)),
    );
    const [selected, setSelected] = useState(savedSelection);
    const [query, setQuery] = useState('');
    const [page, setPage] = useState(1);
    const [selectedPage, setSelectedPage] = useState(1);
    const [processing, setProcessing] = useState(false);
    const [syncing, setSyncing] = useState(false);

    const isDirty = selected.join('|') !== savedSelection.join('|');
    const isFull = selected.length >= maxSelected;
    const terms = normalize(query.trim());
    const others = available.filter(
        (repo) =>
            !selected.includes(repo.full_name) &&
            (terms === '' ||
                normalize(
                    `${repo.name} ${repo.description ?? ''} ${repo.language ?? ''}`,
                ).includes(terms)),
    );
    const otherCount = available.length - selected.length;
    const pageCount = Math.max(1, Math.ceil(others.length / PER_PAGE));
    const currentPage = Math.min(page, pageCount);
    const shownOthers = others.slice(
        (currentPage - 1) * PER_PAGE,
        currentPage * PER_PAGE,
    );
    const selectedPageCount = Math.max(
        1,
        Math.ceil(selected.length / PER_PAGE),
    );
    const currentSelectedPage = Math.min(selectedPage, selectedPageCount);
    const selectedStart = (currentSelectedPage - 1) * PER_PAGE;
    const shownSelected = selected.slice(
        selectedStart,
        selectedStart + PER_PAGE,
    );

    const move = (index: number, offset: number) =>
        setSelected((current) => {
            const next = [...current];
            [next[index], next[index + offset]] = [
                next[index + offset],
                next[index],
            ];

            return next;
        });

    const save = () =>
        router.put(
            GitHubController.update.url(),
            { repositories: selected },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onSuccess: () => setSavedSelection(selected),
                onFinish: () => setProcessing(false),
            },
        );

    const sync = () =>
        router.post(
            GitHubController.sync.url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setSyncing(true),
                onFinish: () => setSyncing(false),
            },
        );

    return (
        <>
            <Head title="Dépôts GitHub" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Dépôts GitHub"
                        description="Les dépôts de la section « Sur GitHub » de la page À propos, dans l'ordre choisi."
                    />
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            disabled={syncing}
                            onClick={sync}
                        >
                            <RefreshCw
                                className={syncing ? 'animate-spin' : undefined}
                            />
                            Synchroniser
                        </Button>
                        <Button
                            disabled={!isDirty || processing}
                            onClick={save}
                        >
                            Enregistrer
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <span>
                        {syncedAt
                            ? `Synchronisé le ${new Date(syncedAt).toLocaleString('fr-FR')}`
                            : 'Jamais synchronisé'}
                    </span>
                    {isDirty && (
                        <span className="text-amber-600 dark:text-amber-400">
                            Modifications non enregistrées
                        </span>
                    )}
                </div>

                {!hasToken && (
                    <div className="flex gap-3 rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        <p>
                            Sans jeton GitHub (<code>GITHUB_TOKEN</code>), seuls
                            tes dépôts publics sont proposés. Avec un jeton
                            (permission « Metadata : read-only »), s’ajoutent
                            tes dépôts privés et ceux auxquels tu as contribué.
                        </p>
                    </div>
                )}

                <Tabs defaultValue="selected" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="selected">
                            Affichés
                            <Badge variant="secondary">
                                {selected.length}/{maxSelected}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="others">
                            Disponibles
                            <Badge variant="secondary">{otherCount}</Badge>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="selected" className="space-y-3">
                        <ListCard
                            header={
                                <>
                                    <span>Ordre d'affichage</span>
                                    <span>
                                        {selected.length} sur {maxSelected}
                                    </span>
                                </>
                            }
                        >
                            {selected.length === 0 ? (
                                <Empty>
                                    Aucun choix : le site affiche
                                    automatiquement tes 6 dépôts publics les
                                    plus étoilés et récents.
                                </Empty>
                            ) : (
                                <ol className="divide-y">
                                    {shownSelected.map((name, offset) => {
                                        const index = selectedStart + offset;
                                        const repository = byName.get(name)!;

                                        return (
                                            <li
                                                key={name}
                                                className="flex items-center gap-3 px-4 py-3"
                                            >
                                                <span className="w-5 text-sm text-muted-foreground tabular-nums">
                                                    {index + 1}
                                                </span>
                                                <RepositoryLabel
                                                    repository={repository}
                                                />
                                                <RepositoryMeta
                                                    repository={repository}
                                                />
                                                <div className="flex shrink-0 text-muted-foreground">
                                                    <ActionTooltip label="Monter">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Monter"
                                                            disabled={
                                                                index === 0
                                                            }
                                                            onClick={() =>
                                                                move(index, -1)
                                                            }
                                                        >
                                                            <ArrowUp />
                                                        </Button>
                                                    </ActionTooltip>
                                                    <ActionTooltip label="Descendre">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Descendre"
                                                            disabled={
                                                                index ===
                                                                selected.length -
                                                                    1
                                                            }
                                                            onClick={() =>
                                                                move(index, 1)
                                                            }
                                                        >
                                                            <ArrowDown />
                                                        </Button>
                                                    </ActionTooltip>
                                                    <ActionTooltip label="Retirer">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Retirer"
                                                            onClick={() =>
                                                                setSelected(
                                                                    (current) =>
                                                                        current.filter(
                                                                            (
                                                                                item,
                                                                            ) =>
                                                                                item !==
                                                                                name,
                                                                        ),
                                                                )
                                                            }
                                                        >
                                                            <X />
                                                        </Button>
                                                    </ActionTooltip>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ol>
                            )}
                            <Pager
                                page={currentSelectedPage}
                                count={selectedPageCount}
                                onChange={setSelectedPage}
                            />
                        </ListCard>
                        {selected.some((name) => byName.get(name)?.private) && (
                            <p className="text-sm text-muted-foreground">
                                Les dépôts privés s’affichent sans lien, mais
                                leur nom et leur description deviennent publics.
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="others">
                        <ListCard
                            header={
                                <>
                                    <InputGroup className="w-full bg-background sm:max-w-xs">
                                        <InputGroupAddon>
                                            <SearchIcon />
                                        </InputGroupAddon>
                                        <InputGroupInput
                                            type="search"
                                            value={query}
                                            onChange={(event) => {
                                                setQuery(event.target.value);
                                                setPage(1);
                                            }}
                                            placeholder="Nom, description, langage…"
                                            aria-label="Rechercher"
                                        />
                                    </InputGroup>
                                    {isFull && (
                                        <span>
                                            Maximum atteint : retire un dépôt
                                            pour en ajouter un autre.
                                        </span>
                                    )}
                                </>
                            }
                        >
                            {others.length === 0 ? (
                                <Empty>
                                    {terms === ''
                                        ? 'Tous les dépôts sont déjà affichés.'
                                        : 'Aucun dépôt ne correspond.'}
                                </Empty>
                            ) : (
                                <ul className="divide-y">
                                    {shownOthers.map((repository) => (
                                        <li
                                            key={repository.full_name}
                                            className="flex items-center gap-3 px-4 py-3"
                                        >
                                            <RepositoryLabel
                                                repository={repository}
                                            />
                                            <RepositoryMeta
                                                repository={repository}
                                            />
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={isFull}
                                                onClick={() =>
                                                    setSelected((current) => [
                                                        ...current,
                                                        repository.full_name,
                                                    ])
                                                }
                                            >
                                                <Plus /> Afficher
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <Pager
                                page={currentPage}
                                count={pageCount}
                                onChange={setPage}
                            />
                        </ListCard>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

GitHubEdit.layout = {
    breadcrumbs: [{ title: 'Dépôts GitHub', href: pageEdit() }],
};
