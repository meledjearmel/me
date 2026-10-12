import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import MusicGenreController from '@/actions/App/Http/Controllers/Admin/MusicGenreController';
import DeleteButton from '@/components/admin/delete-button';
import { ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/music-genres';
import type { ListFilters, MusicGenre, Paginated } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

export default function MusicGenresIndex({
    genres,
    filters,
}: {
    genres: Paginated<MusicGenre>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Registres de musique" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Registres de musique"
                        description="Les onglets du lecteur : lo-fi, jazz, afrobeat…"
                    />
                    <Button asChild>
                        <Link href={MusicGenreController.create()}>
                            <Plus data-icon="inline-start" /> Nouveau registre
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={genres}
                    filters={filters}
                    searchPlaceholder="Clé ou libellé…"
                    columns={[
                        {
                            header: 'Clé',
                            className: 'font-mono text-xs',
                            cell: (row) => row.key,
                        },
                        { header: 'Libellé (FR)', cell: (row) => row.label.fr },
                        { header: 'Libellé (EN)', cell: (row) => row.label.en },
                        {
                            header: 'Pistes',
                            cell: (row) => row.tracks_count ?? 0,
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={MusicGenreController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={MusicGenreController.destroy.url(row.id)}
                                confirmMessage={`Supprimer le registre "${row.label.fr}" et ses pistes ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

MusicGenresIndex.layout = {
    breadcrumbs: [{ title: 'Registres de musique', href: pageIndex() }],
};
