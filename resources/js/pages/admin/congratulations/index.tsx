import { Head, Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import CelebrationController from '@/actions/App/Http/Controllers/Admin/CelebrationController';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/congratulations';
import type { Congratulation, ListFilters, Paginated } from '@/types';

const SOURCE_LABEL = {
    about: 'Page À propos',
    surprise: 'Surprise',
} as const;

const dateTimeFormat = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export default function CongratulationsIndex({
    congratulations,
    filters,
}: {
    congratulations: Paginated<Congratulation>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Félicitations" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Félicitations"
                    description="Qui vous félicite, et pour quoi : un envoi par visiteur (ses clics sont regroupés)"
                />

                <ResourceList
                    paginator={congratulations}
                    filters={filters}
                    searchPlaceholder="Motif…"
                    columns={[
                        {
                            header: 'Date',
                            className: 'whitespace-nowrap',
                            cell: (row) =>
                                dateTimeFormat.format(new Date(row.created_at)),
                        },
                        {
                            header: 'Origine',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.source === 'surprise'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {SOURCE_LABEL[row.source]}
                                </Badge>
                            ),
                        },
                        {
                            header: 'Motif',
                            className: 'max-w-md whitespace-normal',
                            cell: (row) =>
                                row.celebration_id ? (
                                    <Link
                                        href={CelebrationController.show(
                                            row.celebration_id,
                                        )}
                                        className="hover:underline"
                                    >
                                        {row.reason}
                                    </Link>
                                ) : (
                                    row.reason
                                ),
                        },
                        {
                            header: 'Clics',
                            cell: (row) => row.count,
                        },
                        {
                            header: 'Langue',
                            className: 'uppercase',
                            cell: (row) => row.locale ?? '—',
                        },
                    ]}
                    actions={(row) =>
                        row.celebration_id ? (
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={CelebrationController.show(
                                        row.celebration_id,
                                    )}
                                    aria-label="Voir la surprise"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                        ) : null
                    }
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="source"
                            value={filters.source}
                            label="Origine"
                            options={[
                                { value: 'surprise', label: 'Surprise' },
                                { value: 'about', label: 'Page À propos' },
                            ]}
                        />
                    )}
                />
            </div>
        </>
    );
}

CongratulationsIndex.layout = {
    breadcrumbs: [{ title: 'Félicitations', href: pageIndex() }],
};
