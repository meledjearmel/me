import { Head, Link } from "@inertiajs/react";
import { Plus, SquarePen } from "lucide-react";
import PostController from "@/actions/App/Http/Controllers/Admin/PostController";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import DeleteButton from "@/components/admin/delete-button";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { index as pageIndex } from "@/routes/admin/posts";
import type { ListFilters, Paginated, Post } from "@/types";

function publicationLabel(post: Post): {
    label: string;
    variant: "default" | "secondary" | "outline";
} {
    if (post.status === "draft") {
        return { label: "Brouillon", variant: "secondary" };
    }

    if (post.published_at && new Date(post.published_at) > new Date()) {
        return { label: "Programmé", variant: "outline" };
    }

    return { label: "Publié", variant: "default" };
}

export default function PostsIndex({
    posts,
    filters,
}: {
    posts: Paginated<Post>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Blog" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading title="Blog" />
                    <Button asChild>
                        <Link href={PostController.create()}>
                            <Plus data-icon="inline-start" /> Nouvel article
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={posts}
                    filters={filters}
                    searchPlaceholder="Titre ou slug…"
                    columns={[
                        {
                            header: "Titre",
                            cell: (row) => (
                                <span className="font-medium">
                                    {row.title.fr}
                                    {!row.title.en && (
                                        <Badge
                                            variant="outline"
                                            className="ml-2"
                                        >
                                            FR seul
                                        </Badge>
                                    )}
                                </span>
                            ),
                        },
                        {
                            header: "Statut",
                            cell: (row) => {
                                const { label, variant } =
                                    publicationLabel(row);

                                return <Badge variant={variant}>{label}</Badge>;
                            },
                        },
                        {
                            header: "Publication",
                            cell: (row) =>
                                row.published_at
                                    ? new Date(
                                          row.published_at,
                                      ).toLocaleDateString("fr-FR")
                                    : "—",
                        },
                        {
                            header: "Lecture",
                            cell: (row) => `${row.reading_minutes} min`,
                        },
                        {
                            header: "Mis en avant",
                            cell: (row) => (row.is_featured ? "Oui" : "—"),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: "published", label: "Publié" },
                                    { value: "draft", label: "Brouillon" },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_featured"
                                value={filters.is_featured}
                                label="Mise en avant"
                                options={[
                                    { value: "1", label: "Mis en avant" },
                                    { value: "0", label: "Non mis en avant" },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={PostController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={PostController.destroy.url(row.id)}
                                confirmMessage={`Supprimer l’article "${row.title.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

PostsIndex.layout = {
    breadcrumbs: [{ title: "Blog", href: pageIndex() }],
};
