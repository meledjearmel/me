import { router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, SearchIcon, XIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { ListFilters, Paginated } from '@/types';

const ALL = 'all';
const PER_PAGE_OPTIONS = [10, 25, 50];

/**
 * État d'une liste (recherche, filtres, taille de page) synchronisé avec l'URL :
 * chaque changement relance la requête Inertia en conservant la position dans la page.
 */
export function useListState(filters: ListFilters) {
    const [search, setSearch] = useState(filters.search);
    const first = useRef(true);
    const current = useRef(filters);
    current.current = filters;

    const visit = (changes: Record<string, string | number>) => {
        const next: Record<string, string | number> = {
            ...current.current,
            ...changes,
        };
        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([key, value]) =>
                    value !== '' && !(key === 'per_page' && value === 10),
            ),
        );

        router.get(window.location.pathname, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const timer = setTimeout(() => {
            if (search !== current.current.search) {
                visit({ search, page: 1 });
            }
        }, 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const activeFilterCount = Object.entries(filters).filter(
        ([key, value]) => key !== 'per_page' && value !== '',
    ).length;

    return {
        search,
        setSearch,
        visit,
        activeFilterCount,
        reset: () => {
            setSearch('');
            router.get(
                window.location.pathname,
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        },
    };
}

type ListToolbarProps = {
    state: ReturnType<typeof useListState>;
    placeholder?: string;
    children?: ReactNode;
};

export function ListToolbar({
    state,
    placeholder = 'Rechercher…',
    children,
}: ListToolbarProps) {
    return (
        <div className="flex flex-1 flex-wrap items-center gap-2">
            <InputGroup className="w-full sm:max-w-xs">
                <InputGroupAddon>
                    <SearchIcon />
                </InputGroupAddon>
                <InputGroupInput
                    type="search"
                    value={state.search}
                    onChange={(event) => state.setSearch(event.target.value)}
                    placeholder={placeholder}
                    aria-label="Rechercher"
                />
            </InputGroup>

            {children}

            {state.activeFilterCount > 0 && (
                <Button variant="ghost" size="sm" onClick={state.reset}>
                    <XIcon data-icon="inline-start" />
                    Réinitialiser
                </Button>
            )}
        </div>
    );
}

type FilterSelectProps = {
    state: ReturnType<typeof useListState>;
    name: string;
    value: string | number;
    label: string;
    options: { value: string; label: string }[];
};

/** Filtre à choix unique ; « Tous » retire le filtre de l'URL. */
export function FilterSelect({
    state,
    name,
    value,
    label,
    options,
}: FilterSelectProps) {
    return (
        <Select
            value={String(value) === '' ? ALL : String(value)}
            onValueChange={(next) =>
                state.visit({ [name]: next === ALL ? '' : next, page: 1 })
            }
        >
            <SelectTrigger className="w-auto min-w-36" aria-label={label}>
                <SelectValue placeholder={label} />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectItem value={ALL}>{label} : tous</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectGroup>
            </SelectContent>
        </Select>
    );
}

function pageWindow(current: number, last: number): (number | 'gap')[] {
    const pages = new Set(
        [1, last, current - 1, current, current + 1].filter(
            (page) => page >= 1 && page <= last,
        ),
    );
    const sorted = [...pages].sort((a, b) => a - b);
    const result: (number | 'gap')[] = [];

    sorted.forEach((page, index) => {
        if (index > 0 && page - sorted[index - 1] > 1) {
            result.push('gap');
        }

        result.push(page);
    });

    return result;
}

/** Pied du tableau : « Précédent » à gauche, les pages au centre, « Suivant » à droite. */
export function ListPagination({
    paginator,
    state,
}: {
    paginator: Paginated<unknown>;
    state: ReturnType<typeof useListState>;
}) {
    const { current_page: current, last_page: last } = paginator;
    const go = (page: number) => state.visit({ page });

    return (
        <div className="flex items-center justify-between gap-3 border-t px-4 py-3">
            <Button
                variant="outline"
                size="sm"
                disabled={current === 1}
                onClick={() => go(current - 1)}
            >
                <ArrowLeft data-icon="inline-start" />
                <span className="hidden sm:inline">Précédent</span>
            </Button>

            <div className="flex items-center gap-1">
                {last > 1 ? (
                    pageWindow(current, last).map((page, index) =>
                        page === 'gap' ? (
                            <span
                                key={`gap-${index}`}
                                className="px-2 text-sm text-muted-foreground"
                            >
                                …
                            </span>
                        ) : (
                            <Button
                                key={page}
                                variant={
                                    page === current ? 'secondary' : 'ghost'
                                }
                                size="sm"
                                className="min-w-8"
                                aria-current={
                                    page === current ? 'page' : undefined
                                }
                                onClick={() => go(page)}
                            >
                                {page}
                            </Button>
                        ),
                    )
                ) : (
                    <span className="text-sm text-muted-foreground">
                        {paginator.from}–{paginator.to} sur {paginator.total}
                    </span>
                )}
            </div>

            <Button
                variant="outline"
                size="sm"
                disabled={current === last}
                onClick={() => go(current + 1)}
            >
                <span className="hidden sm:inline">Suivant</span>
                <ArrowRight data-icon="inline-end" />
            </Button>
        </div>
    );
}

/** Nombre d'éléments affichés par page. */
export function PerPageSelect({
    paginator,
    state,
}: {
    paginator: Paginated<unknown>;
    state: ReturnType<typeof useListState>;
}) {
    return (
        <Select
            value={String(paginator.per_page)}
            onValueChange={(next) =>
                state.visit({ per_page: Number(next), page: 1 })
            }
        >
            <SelectTrigger size="sm" aria-label="Éléments par page">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    {PER_PAGE_OPTIONS.map((size) => (
                        <SelectItem key={size} value={String(size)}>
                            {size} / page
                        </SelectItem>
                    ))}
                </SelectGroup>
            </SelectContent>
        </Select>
    );
}

export function EmptyList({
    filtered,
    children,
}: {
    filtered: boolean;
    children?: ReactNode;
}) {
    return (
        <Empty>
            <EmptyHeader>
                <EmptyTitle>
                    {filtered ? 'Aucun résultat' : 'Rien pour le moment'}
                </EmptyTitle>
                <EmptyDescription>
                    {filtered
                        ? 'Aucun élément ne correspond à la recherche ou aux filtres.'
                        : 'Les éléments apparaîtront ici.'}
                </EmptyDescription>
            </EmptyHeader>
            {children}
        </Empty>
    );
}

export type Column<T> = {
    header: string;
    cell: (row: T) => ReactNode;
    className?: string;
    /**
     * Colonne à la largeur de son contenu, jamais tronquée (badge, statut, date). Les autres
     * se partagent la place restante et coupent le texte trop long par « … ».
     */
    fit?: boolean;
};

/** Coupe le texte trop long par « … » ; le texte entier reste lisible au survol. */
function TruncatedCell({ children }: { children: ReactNode }) {
    return (
        <div
            className="truncate"
            title={
                typeof children === 'string' || typeof children === 'number'
                    ? String(children)
                    : undefined
            }
        >
            {children}
        </div>
    );
}

type ResourceListProps<T extends { id: number }> = {
    paginator: Paginated<T>;
    filters: ListFilters;
    columns: Column<T>[];
    actions: (row: T) => ReactNode;
    searchPlaceholder?: string;
    /** Filtres propres à la liste, rendus à côté du champ de recherche. */
    filterControls?: (state: ReturnType<typeof useListState>) => ReactNode;
};

/**
 * Liste d'administration complète, dans une carte : recherche, filtres et total
 * en en-tête, tableau aéré (première colonne mise en avant), pagination en pied.
 */
export function ResourceList<T extends { id: number }>({
    paginator,
    filters,
    columns,
    actions,
    searchPlaceholder,
    filterControls,
}: ResourceListProps<T>) {
    const state = useListState(filters);
    const total = paginator.total;

    return (
        <div className="overflow-hidden rounded-xl border">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
                <ListToolbar state={state} placeholder={searchPlaceholder}>
                    {filterControls?.(state)}
                </ListToolbar>
                <div className="flex items-center gap-2">
                    <Badge variant="outline">
                        {total} élément{total > 1 ? 's' : ''}
                    </Badge>
                    <PerPageSelect paginator={paginator} state={state} />
                </div>
            </div>

            {paginator.data.length === 0 ? (
                <EmptyList filtered={state.activeFilterCount > 0} />
            ) : (
                <Table>
                    <TableHeader className="bg-muted/50">
                        <TableRow className="hover:bg-transparent">
                            {columns.map((column) => (
                                <TableHead
                                    key={column.header}
                                    className={cn(
                                        'h-11 px-4 text-xs font-medium text-muted-foreground first:pl-6',
                                        column.className,
                                    )}
                                >
                                    {column.header}
                                </TableHead>
                            ))}
                            <TableHead className="h-11 px-4 pr-6">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {paginator.data.map((row) => (
                            <TableRow key={row.id}>
                                {columns.map((column, index) => (
                                    <TableCell
                                        key={column.header}
                                        className={cn(
                                            'px-4 py-4 first:pl-6',
                                            column.fit ? 'w-px' : 'max-w-0',
                                            index === 0
                                                ? 'font-medium'
                                                : 'text-muted-foreground',
                                            column.className,
                                        )}
                                    >
                                        {column.fit ? (
                                            column.cell(row)
                                        ) : (
                                            <TruncatedCell>
                                                {column.cell(row)}
                                            </TruncatedCell>
                                        )}
                                    </TableCell>
                                ))}
                                <TableCell className="w-px px-4 py-4 pr-6">
                                    <div className="flex justify-end gap-0.5 text-muted-foreground">
                                        {actions(row)}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}

            {total > 0 && (
                <ListPagination paginator={paginator} state={state} />
            )}
        </div>
    );
}
