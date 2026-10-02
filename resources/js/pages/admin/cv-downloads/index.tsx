import { Head, Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy, show, index as pageIndex } from '@/routes/admin/cv-downloads';
import { originOf, placeOf } from '@/lib/cv-downloads';
import type { CvDownload, ListFilters, Paginated } from '@/types';

export default function CvDownloadsIndex({
    downloads,
    filters,
    summary,
    countries,
}: {
    downloads: Paginated<CvDownload>;
    filters: ListFilters;
    summary: { total: number; last_30_days: number; with_email: number };
    countries: { country_code: string; country: string | null }[];
}) {
    return (
        <>
            <Head title="Téléchargements du CV" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Téléchargements du CV"
                    description="Chaque téléchargement depuis la page contact, avec sa provenance"
                />

                <dl className="grid gap-3 sm:grid-cols-3">
                    {[
                        ['Au total', summary.total],
                        ['Sur 30 jours', summary.last_30_days],
                        ['Avec un email', summary.with_email],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-lg border p-4">
                            <dt className="text-sm text-muted-foreground">
                                {label}
                            </dt>
                            <dd className="text-2xl font-semibold">{value}</dd>
                        </div>
                    ))}
                </dl>

                <ResourceList
                    paginator={downloads}
                    filters={filters}
                    searchPlaceholder="Email, ville, provenance, campagne…"
                    columns={[
                        {
                            header: 'Date',
                            cell: (row) => formatDate(row.created_at),
                        },
                        { header: 'Lieu', cell: (row) => placeOf(row) },
                        {
                            header: 'Provenance',
                            cell: (row) => (
                                <Badge variant="outline">{originOf(row)}</Badge>
                            ),
                        },
                        {
                            header: 'Email',
                            cell: (row) => row.email ?? '—',
                        },
                        {
                            header: 'CV',
                            cell: (row) =>
                                `${row.locale.toUpperCase()} · ${row.source === 'uploaded' ? 'importé' : 'généré'}`,
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="country_code"
                                value={filters.country_code}
                                label="Pays"
                                options={countries.map((country) => ({
                                    value: country.country_code,
                                    label:
                                        country.country ?? country.country_code,
                                }))}
                            />
                            <FilterSelect
                                state={state}
                                name="locale"
                                value={filters.locale}
                                label="Langue"
                                options={[
                                    { value: 'fr', label: 'Français' },
                                    { value: 'en', label: 'Anglais' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={show(row.id)} aria-label="Voir">
                                    <Eye />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={destroy.url(row.id)}
                                confirmMessage="Supprimer ce téléchargement ?"
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

CvDownloadsIndex.layout = {
    breadcrumbs: [{ title: 'Téléchargements du CV', href: pageIndex() }],
};
