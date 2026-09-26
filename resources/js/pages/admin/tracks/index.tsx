import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import TrackController from '@/actions/App/Http/Controllers/Admin/TrackController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/tracks';
import type { ListFilters, MusicGenre, Paginated, Track } from '@/types';

export default function TracksIndex({
    tracks,
    filters,
    genres,
}: {
    tracks: Paginated<Track>;
    filters: ListFilters;
    genres: MusicGenre[];
}) {
    return (
        <>
            <Head title="Pistes de musique" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Pistes de musique"
                        description="Les morceaux du lecteur, rangés par registre"
                    />
                    <Button asChild>
                        <Link href={TrackController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle piste
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={tracks}
                    filters={filters}
                    searchPlaceholder="Titre ou artiste…"
                    columns={[
                        { header: 'Titre', cell: (row) => row.title },
                        { header: 'Artiste', cell: (row) => row.artist ?? '—' },
                        {
                            header: 'Registre',
                            cell: (row) => row.genre?.label.fr ?? '—',
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="music_genre_id"
                            value={filters.music_genre_id ?? ''}
                            label="Registre"
                            options={genres.map((genre) => ({
                                value: String(genre.id),
                                label: genre.label.fr,
                            }))}
                        />
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={TrackController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={TrackController.destroy.url(row.id)}
                                confirmMessage={`Supprimer la piste "${row.title}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

TracksIndex.layout = {
    breadcrumbs: [{ title: 'Pistes de musique', href: pageIndex() }],
};
