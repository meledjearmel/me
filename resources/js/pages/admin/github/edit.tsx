import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Lock, Plus, RefreshCw, X } from 'lucide-react';
import { useState } from 'react';
import GitHubController from '@/actions/App/Http/Controllers/Admin/GitHubController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit as pageEdit } from '@/routes/admin/github';

type Repository = {
    full_name: string;
    name: string;
    description: string | null;
    language: string | null;
    stars: number;
    pushed_at: string | null;
    private: boolean;
    archived: boolean;
    contribution: boolean;
};

function RepositoryLabel({ repository }: { repository: Repository }) {
    return (
        <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-2">
                <span className="font-medium">{repository.name}</span>
                {repository.private && (
                    <Badge variant="secondary">
                        <Lock /> Privé
                    </Badge>
                )}
                {repository.contribution && (
                    <Badge variant="outline">Contribution</Badge>
                )}
                {repository.archived && (
                    <Badge variant="outline">Archivé</Badge>
                )}
            </div>
            <p className="truncate text-sm text-muted-foreground">
                {[
                    repository.language,
                    repository.stars > 0 ? `★ ${repository.stars}` : null,
                    repository.description,
                ]
                    .filter(Boolean)
                    .join(' · ')}
            </p>
        </div>
    );
}

/**
 * Choix des dépôts GitHub de la page À propos, dans l'ordre d'affichage. Sans choix,
 * le site montre automatiquement mes dépôts publics les plus étoilés et récents.
 */
export default function GitHubEdit({
    available,
    selected: initialSelection,
    syncedAt,
    hasToken,
    maxSelected,
}: {
    available: Repository[];
    selected: string[];
    syncedAt: string | null;
    hasToken: boolean;
    maxSelected: number;
}) {
    const byName = new Map(available.map((repo) => [repo.full_name, repo]));
    const [selected, setSelected] = useState(
        initialSelection.filter((name) => byName.has(name)),
    );
    const [processing, setProcessing] = useState(false);
    const others = available.filter(
        (repo) => !selected.includes(repo.full_name),
    );

    const move = (index: number, offset: number) =>
        setSelected((current) => {
            const next = [...current];
            [next[index], next[index + offset]] = [
                next[index + offset],
                next[index],
            ];

            return next;
        });

    const save = () =>
        router.put(
            GitHubController.update.url(),
            { repositories: selected },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );

    return (
        <>
            <Head title="Dépôts GitHub" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title="Dépôts GitHub"
                        description={`Section « Sur GitHub » de la page À propos. ${
                            syncedAt
                                ? `Synchronisé le ${new Date(syncedAt).toLocaleString('fr-FR')}.`
                                : 'Jamais synchronisé.'
                        }`}
                    />
                    <Button
                        variant="outline"
                        onClick={() =>
                            router.post(
                                GitHubController.sync.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <RefreshCw /> Synchroniser maintenant
                    </Button>
                </div>

                {!hasToken && (
                    <p className="rounded-md border p-3 text-sm text-muted-foreground">
                        Sans jeton GitHub (<code>GITHUB_TOKEN</code>), seuls tes
                        dépôts publics sont proposés. Avec un jeton (permission
                        « Metadata : read-only »), s’ajoutent tes dépôts privés
                        et ceux auxquels tu as contribué.
                    </p>
                )}

                <section className="space-y-3">
                    <h2 className="font-medium">
                        Dépôts affichés ({selected.length}/{maxSelected})
                    </h2>
                    {selected.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aucun choix : le site affiche automatiquement tes
                            6 dépôts publics les plus étoilés et récents.
                        </p>
                    ) : (
                        <ol className="divide-y rounded-md border">
                            {selected.map((name, index) => {
                                const repository = byName.get(name)!;

                                return (
                                    <li
                                        key={name}
                                        className="flex items-center gap-2 p-3"
                                    >
                                        <span className="w-6 text-sm text-muted-foreground tabular-nums">
                                            {index + 1}
                                        </span>
                                        <div className="flex-1 overflow-hidden">
                                            <RepositoryLabel
                                                repository={repository}
                                            />
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Monter"
                                            disabled={index === 0}
                                            onClick={() => move(index, -1)}
                                        >
                                            <ArrowUp />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Descendre"
                                            disabled={
                                                index === selected.length - 1
                                            }
                                            onClick={() => move(index, 1)}
                                        >
                                            <ArrowDown />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Retirer"
                                            onClick={() =>
                                                setSelected((current) =>
                                                    current.filter(
                                                        (item) => item !== name,
                                                    ),
                                                )
                                            }
                                        >
                                            <X />
                                        </Button>
                                    </li>
                                );
                            })}
                        </ol>
                    )}
                    {selected.some((name) => byName.get(name)?.private) && (
                        <p className="text-sm text-muted-foreground">
                            Les dépôts privés s’affichent sans lien, mais leur
                            nom et leur description deviennent publics.
                        </p>
                    )}
                    <Button disabled={processing} onClick={save}>
                        Enregistrer
                    </Button>
                </section>

                <section className="space-y-3">
                    <h2 className="font-medium">
                        Autres dépôts ({others.length})
                    </h2>
                    <ul className="divide-y rounded-md border">
                        {others.map((repository) => (
                            <li
                                key={repository.full_name}
                                className="flex items-center gap-2 p-3"
                            >
                                <div className="flex-1 overflow-hidden">
                                    <RepositoryLabel repository={repository} />
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={selected.length >= maxSelected}
                                    onClick={() =>
                                        setSelected((current) => [
                                            ...current,
                                            repository.full_name,
                                        ])
                                    }
                                >
                                    <Plus /> Afficher
                                </Button>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </>
    );
}

GitHubEdit.layout = {
    breadcrumbs: [{ title: 'Dépôts GitHub', href: pageEdit() }],
};
