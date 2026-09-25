import { router } from '@inertiajs/react';
import { SearchIcon, XIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
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
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
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
        <div className="flex flex-wrap items-center gap-2">
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
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                {paginator.total === 0
                    ? 'Aucun résultat'
                    : `${paginator.from}–${paginator.to} sur ${paginator.total}`}
            </p>

            {last > 1 && (
                <Pagination className="mx-0 w-auto">
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href="#"
                                aria-disabled={current === 1}
                                className={
                                    current === 1
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                }
                                onClick={(event) => {
                                    event.preventDefault();
                                    go(current - 1);
                                }}
                            />
                        </PaginationItem>
                        {pageWindow(current, last).map((page, index) =>
                            page === 'gap' ? (
                                <PaginationItem key={`gap-${index}`}>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            ) : (
                                <PaginationItem key={page}>
                                    <PaginationLink
                                        href="#"
                                        isActive={page === current}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            go(page);
                                        }}
                                    >
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ),
                        )}
                        <PaginationItem>
                            <PaginationNext
                                href="#"
                                aria-disabled={current === last}
                                className={
                                    current === last
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                }
                                onClick={(event) => {
                                    event.preventDefault();
                                    go(current + 1);
                                }}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            )}

            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                Par page
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
                                    {size}
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
        </div>
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
        <Empty className="border">
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
};

type ResourceListProps<T extends { id: number }> = {
    paginator: Paginated<T>;
    filters: ListFilters;
    columns: Column<T>[];
    actions: (row: T) => ReactNode;
    searchPlaceholder?: string;
    /** Filtres propres à la liste, rendus à côté du champ de recherche. */
    filterControls?: (state: ReturnType<typeof useListState>) => ReactNode;
};

/** Liste d'administration complète : recherche, filtres, tableau, état vide et pagination. */
export function ResourceList<T extends { id: number }>({
    paginator,
    filters,
    columns,
    actions,
    searchPlaceholder,
    filterControls,
}: ResourceListProps<T>) {
    const state = useListState(filters);

    return (
        <div className="flex flex-col gap-4">
            <ListToolbar state={state} placeholder={searchPlaceholder}>
                {filterControls?.(state)}
            </ListToolbar>

            {paginator.data.length === 0 ? (
                <EmptyList filtered={state.activeFilterCount > 0} />
            ) : (
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHead
                                    key={column.header}
                                    className={column.className}
                                >
                                    {column.header}
                                </TableHead>
                            ))}
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {paginator.data.map((row) => (
                            <TableRow key={row.id}>
                                {columns.map((column) => (
                                    <TableCell
                                        key={column.header}
                                        className={column.className}
                                    >
                                        {column.cell(row)}
                                    </TableCell>
                                ))}
                                <TableCell>
                                    <div className="flex justify-end gap-1">
                                        {actions(row)}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}

            <ListPagination paginator={paginator} state={state} />
        </div>
    );
}
