import { Head } from '@inertiajs/react';
import { Check } from 'lucide-react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { PALETTES, usePalette } from '@/hooks/use-palette';
import { cn } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { palette, updatePalette } = usePalette();

    return (
        <>
            <Head title="Apparence" />

            <h1 className="sr-only">Paramètres d’apparence</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Mode"
                    description="Clair, sombre, ou celui de votre système"
                />
                <AppearanceTabs />
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Palette"
                    description="Les quatre palettes de l’app mobile, chacune en clair et en sombre"
                />
                <div className="grid gap-3 sm:grid-cols-2">
                    {PALETTES.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => updatePalette(option.value)}
                            aria-pressed={palette === option.value}
                            className={cn(
                                'flex items-center gap-3 rounded-2xl bg-[var(--card)] p-4 text-left ring-2 transition',
                                palette === option.value
                                    ? 'ring-[var(--brand)]'
                                    : 'ring-transparent hover:ring-[var(--soft-high)]',
                            )}
                        >
                            <span className="flex -space-x-2">
                                {option.swatch.map((color) => (
                                    <span
                                        key={color}
                                        className="size-7 rounded-full ring-2 ring-[var(--card)]"
                                        style={{ backgroundColor: color }}
                                    />
                                ))}
                            </span>
                            <span className="flex-1 font-semibold">
                                {option.label}
                            </span>
                            {palette === option.value && (
                                <Check className="size-4" />
                            )}
                        </button>
                    ))}
                </div>
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Apparence',
            href: editAppearance(),
        },
    ],
};
