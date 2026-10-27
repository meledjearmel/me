import { usePage } from '@inertiajs/react';
import { Check, Menu, Moon, Palette as PaletteIcon, Sun } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useAppearance } from '@/hooks/use-appearance';
import { useInitials } from '@/hooks/use-initials';
import { PALETTES, usePalette } from '@/hooks/use-palette';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/** En-tête de la console : une carte avec le fil d'Ariane, le thème et le compte. */
export function AppSidebarHeader({
    breadcrumbs = [],
    onMenu,
}: {
    breadcrumbs?: BreadcrumbItemType[];
    onMenu: () => void;
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const { palette, updatePalette } = usePalette();
    const isDark = resolvedAppearance === 'dark';

    return (
        <header className="flex shrink-0 items-center gap-3 rounded-[1.6rem] bg-[var(--card)] px-3 py-3 sm:px-5">
            <button
                type="button"
                onClick={onMenu}
                className="grid size-11 shrink-0 place-items-center rounded-full bg-[var(--soft)] lg:hidden"
                aria-label="Menu"
            >
                <Menu className="size-5" />
            </button>

            <div className="min-w-0 flex-1 overflow-hidden">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="ml-auto flex items-center gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            className="grid size-11 place-items-center rounded-full bg-[var(--soft)] transition hover:bg-[var(--soft-high)]"
                            title="Palette"
                            aria-label="Choisir la palette"
                        >
                            <PaletteIcon className="size-5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        className="w-56 rounded-2xl p-2"
                    >
                        <DropdownMenuLabel>Palette</DropdownMenuLabel>
                        {PALETTES.map((option) => (
                            <DropdownMenuItem
                                key={option.value}
                                onSelect={() => updatePalette(option.value)}
                                className="gap-3 rounded-xl"
                            >
                                <Swatch colors={option.swatch} />
                                <span className="flex-1">{option.label}</span>
                                {palette === option.value && (
                                    <Check className="size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>

                <button
                    type="button"
                    onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
                    className="grid size-11 place-items-center rounded-full bg-[var(--soft)] transition hover:bg-[var(--soft-high)]"
                    title={isDark ? 'Mode clair' : 'Mode sombre'}
                    aria-label={
                        isDark
                            ? 'Passer en mode clair'
                            : 'Passer en mode sombre'
                    }
                >
                    {isDark ? (
                        <Sun className="size-5" />
                    ) : (
                        <Moon className="size-5" />
                    )}
                </button>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            className="flex items-center gap-3 rounded-full py-1 pr-1 text-left sm:pr-3"
                            data-test="sidebar-menu-button"
                        >
                            <span className="grid size-11 place-items-center rounded-full bg-[var(--brand)] text-sm font-bold text-[var(--on-brand)]">
                                {getInitials(auth.user.name)}
                            </span>
                            <span className="hidden leading-tight md:block">
                                <span className="block font-semibold">
                                    {auth.user.name}
                                </span>
                                <span className="block text-xs text-[var(--muted-fg)]">
                                    {auth.user.email}
                                </span>
                            </span>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        className="min-w-60 rounded-2xl p-2"
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}

function Swatch({ colors }: { colors: readonly string[] }) {
    return (
        <span className="flex -space-x-1.5">
            {colors.map((color) => (
                <span
                    key={color}
                    className="size-4 rounded-full ring-2 ring-[var(--card)]"
                    style={{ backgroundColor: color }}
                />
            ))}
        </span>
    );
}
