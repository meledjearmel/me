import { Link } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import { Badge } from '@/components/ui/badge';
import ShowPage, { Bilingual, Pills } from '@/components/admin/show-page';
import { index as projectsIndex } from '@/routes/admin/projects';
import type { Project } from '@/types';

export default function ProjectShow({ project: row }: { project: Project }) {
    return (
        <ShowPage
            title={row.title.fr}
            description={row.slug}
            badge={
                <Badge
                    variant={
                        row.status === 'published' ? 'default' : 'secondary'
                    }
                >
                    {row.status === 'published' ? 'Publié' : 'Archivé'}
                </Badge>
            }
            backHref={projectsIndex()}
            backLabel="Projets"
            editHref={ProjectController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        {
                            label: 'Titre',
                            value: <Bilingual value={row.title} />,
                        },
                        { label: 'Adresse', value: `/projects/${row.slug}` },
                        {
                            label: 'Mis en avant',
                            value: row.is_featured ? 'Oui' : 'Non',
                        },
                        {
                            label: 'Open source',
                            value: row.is_open_source ? 'Oui' : 'Non',
                        },
                        {
                            label: "Couleur d'accent",
                            value: row.accent_color && (
                                <span className="inline-flex items-center gap-2">
                                    <span
                                        className="size-3 rounded-full border"
                                        style={{
                                            backgroundColor: row.accent_color,
                                        }}
                                    />
                                    {row.accent_color}
                                </span>
                            ),
                        },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Dépôt',
                            value: row.repo_url && (
                                <a
                                    href={row.repo_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="underline"
                                >
                                    {row.repo_url}
                                </a>
                            ),
                        },
                        {
                            label: 'Démo',
                            value: row.demo_url && (
                                <a
                                    href={row.demo_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="underline"
                                >
                                    {row.demo_url}
                                </a>
                            ),
                        },
                    ],
                },
                {
                    title: 'Récit du projet',
                    items: [
                        {
                            label: 'Contexte',
                            value: <Bilingual value={row.context} />,
                            wide: true,
                        },
                        {
                            label: 'Réalisation',
                            value: <Bilingual value={row.realization} />,
                            wide: true,
                        },
                        {
                            label: 'Résultat',
                            value: <Bilingual value={row.result} />,
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'Classement',
                    items: [
                        {
                            label: 'Domaines',
                            value: (
                                <Pills
                                    items={(row.domains ?? []).map(
                                        (domain) => domain.label.fr,
                                    )}
                                />
                            ),
                        },
                        {
                            label: 'Profils métier',
                            value: (
                                <Pills
                                    items={(row.job_profiles ?? []).map(
                                        (jobProfile) => jobProfile.label.fr,
                                    )}
                                />
                            ),
                        },
                        {
                            label: 'Technologies',
                            value: (
                                <Pills
                                    items={(row.technologies ?? []).map(
                                        (technology) => technology.name,
                                    )}
                                />
                            ),
                            wide: true,
                        },
                        {
                            label: 'Projets liés',
                            value: (
                                <Pills
                                    items={(row.related_projects ?? []).map(
                                        (project) => (
                                            <Link
                                                key={project.id}
                                                href={ProjectController.show(
                                                    project.id,
                                                )}
                                                className="hover:underline"
                                            >
                                                {project.title.fr}
                                            </Link>
                                        ),
                                    )}
                                />
                            ),
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'Images',
                    content: (
                        <div className="flex flex-wrap gap-3">
                            {row.cover_url && (
                                <img
                                    src={row.cover_url}
                                    alt="Couverture"
                                    className="h-32 rounded-md border object-cover"
                                />
                            )}
                            {(row.gallery_urls ?? []).map((url) => (
                                <img
                                    key={url}
                                    src={url}
                                    alt="Galerie"
                                    className="h-32 rounded-md border object-cover"
                                />
                            ))}
                            {!row.cover_url &&
                                (row.gallery_urls ?? []).length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Aucune image.
                                    </p>
                                )}
                        </div>
                    ),
                },
            ]}
        />
    );
}

ProjectShow.layout = {
    breadcrumbs: [
        { title: 'Projets', href: projectsIndex() },
        { title: 'Détail', href: '' },
    ],
};
