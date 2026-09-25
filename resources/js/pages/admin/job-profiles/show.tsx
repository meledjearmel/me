import { Link } from '@inertiajs/react';
import JobProfileController from '@/actions/App/Http/Controllers/Admin/JobProfileController';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import ShowPage, {
    Bilingual,
    PublicationBadge,
    Pills,
} from '@/components/admin/show-page';
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';
import type { JobProfile } from '@/types';

export default function JobProfileShow({
    jobProfile: row,
}: {
    jobProfile: JobProfile;
}) {
    return (
        <ShowPage
            title={row.label.fr}
            description={`Profil métier · ${row.key}`}
            badge={<PublicationBadge status={row.status} />}
            backHref={jobProfilesIndex()}
            backLabel="Profils métier"
            editHref={JobProfileController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Clé', value: row.key },
                        {
                            label: 'Libellé',
                            value: <Bilingual value={row.label} />,
                        },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Description',
                            value: <Bilingual value={row.description} />,
                            wide: true,
                        },
                    ],
                },
                {
                    title: "Page d'accueil",
                    description: 'Titre et mots qui défilent dans le hero',
                    items: [
                        {
                            label: 'Titre',
                            value: <Bilingual value={row.hero_title} />,
                        },
                        {
                            label: 'Mots',
                            value: <Bilingual value={row.hero_words} />,
                        },
                    ],
                },
                {
                    title: 'CV',
                    description:
                        'Ce texte alimente uniquement le CV généré pour ce profil',
                    items: [
                        {
                            label: 'Description du CV',
                            value: <Bilingual value={row.cv_description} />,
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'Projets',
                    description:
                        'Les projets mis en avant dans le CV de ce profil',
                    content: (
                        <Pills
                            items={(row.projects ?? []).map((project) => (
                                <Link
                                    key={project.id}
                                    href={ProjectController.show(project.id)}
                                    className="hover:underline"
                                >
                                    {project.title.fr}
                                </Link>
                            ))}
                        />
                    ),
                },
            ]}
        />
    );
}

JobProfileShow.layout = {
    breadcrumbs: [
        { title: 'Profils métier', href: jobProfilesIndex() },
        { title: 'Détail', href: '' },
    ],
};
