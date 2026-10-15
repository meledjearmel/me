import { Head, Link } from "@inertiajs/react";
import { Download, Plus, Rocket } from "lucide-react";
import AppReleaseController from "@/actions/App/Http/Controllers/Admin/AppReleaseController";
import ActionTooltip from "@/components/admin/action-tooltip";
import DeleteButton from "@/components/admin/delete-button";
import { formatDate } from "@/components/admin/show-page";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    destroy,
    download,
    index as pageIndex,
    publish,
} from "@/routes/admin/app-releases";

type AppRelease = {
    id: number;
    version_name: string;
    build_number: number;
    notes: string | null;
    size: number | null;
    sha256: string | null;
    has_file: boolean;
    published_at: string | null;
    created_at: string | null;
};

function formatSize(bytes: number | null): string {
    return bytes === null ? "—" : `${(bytes / 1_048_576).toFixed(1)} Mo`;
}

export default function AppReleasesIndex({
    releases,
}: {
    releases: AppRelease[];
}) {
    const latestPublished = releases.find((release) => release.published_at);

    return (
        <>
            <Head title="Application mobile" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Application mobile"
                        description="Builds Android privés : l'app installée propose la dernière version publiée. Claude Code les envoie aussi par le serveur MCP."
                    />
                    <Button asChild>
                        <Link href={AppReleaseController.create()}>
                            <Plus data-icon="inline-start" /> Envoyer un build
                        </Link>
                    </Button>
                </div>

                {releases.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Aucun build pour l'instant.
                    </p>
                )}

                <div className="space-y-3">
                    {releases.map((release) => (
                        <article
                            key={release.id}
                            className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div className="min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h3 className="font-semibold">
                                        {release.version_name}
                                    </h3>
                                    <span className="text-sm text-muted-foreground">
                                        build {release.build_number}
                                    </span>
                                    {release.id === latestPublished?.id ? (
                                        <Badge>Version actuelle</Badge>
                                    ) : release.published_at ? (
                                        <Badge variant="secondary">
                                            Publiée
                                        </Badge>
                                    ) : release.has_file ? (
                                        <Badge variant="outline">
                                            À publier
                                        </Badge>
                                    ) : (
                                        <Badge variant="destructive">
                                            APK non reçu
                                        </Badge>
                                    )}
                                </div>
                                {release.notes && (
                                    <p className="text-sm whitespace-pre-line">
                                        {release.notes}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    {release.created_at &&
                                        formatDate(release.created_at)}{" "}
                                    · {formatSize(release.size)}
                                    {release.sha256 && (
                                        <span className="break-all">
                                            {" "}
                                            · SHA-256 {release.sha256}
                                        </span>
                                    )}
                                </p>
                            </div>

                            <div className="flex shrink-0 items-center gap-1">
                                {release.has_file && !release.published_at && (
                                    <Button size="sm" asChild>
                                        <Link
                                            href={publish(release.id)}
                                            as="button"
                                        >
                                            <Rocket />
                                            Publier
                                        </Link>
                                    </Button>
                                )}
                                {release.has_file && (
                                    <ActionTooltip label="Télécharger l'APK">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <a
                                                href={download.url(release.id)}
                                                aria-label="Télécharger l'APK"
                                            >
                                                <Download />
                                            </a>
                                        </Button>
                                    </ActionTooltip>
                                )}
                                <DeleteButton
                                    href={destroy.url(release.id)}
                                    confirmMessage="Supprimer ce build et son APK ?"
                                />
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </>
    );
}

AppReleasesIndex.layout = {
    breadcrumbs: [{ title: "Application mobile", href: pageIndex() }],
};
