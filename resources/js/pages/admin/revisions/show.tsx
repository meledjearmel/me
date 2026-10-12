import { Head, Link, router } from "@inertiajs/react";
import { AlertTriangle, Bot, Check, ExternalLink, X } from "lucide-react";
import { useState } from "react";
import ContentRevisionController from "@/actions/App/Http/Controllers/Admin/ContentRevisionController";
import FormPageHeader from "@/components/admin/form-page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { index as revisionsIndex } from "@/routes/admin/revisions";
import type { ContentRevisionDetail } from "@/types";

function Value({ value }: { value: unknown }) {
    if (value === null || value === undefined || value === "") {
        return <span className="text-muted-foreground italic">(vide)</span>;
    }

    return (
        <pre className="font-sans text-sm break-words whitespace-pre-wrap">
            {typeof value === "string" ? value : JSON.stringify(value, null, 2)}
        </pre>
    );
}

const same = (left: unknown, right: unknown) =>
    JSON.stringify(left ?? null) === JSON.stringify(right ?? null);

/** Avant/après d'une révision proposée par l'IA, champ par champ, puis application ou rejet. */
export default function RevisionShow({
    revision,
}: {
    revision: ContentRevisionDetail;
}) {
    const [processing, setProcessing] = useState(false);
    const decide = (action: "apply" | "reject") =>
        router.post(
            ContentRevisionController[action].url(revision.id),
            {},
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );

    return (
        <>
            <Head title={`Révision — ${revision.title}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title={`${revision.content_type_label} : ${revision.title}`}
                    backHref={revisionsIndex()}
                    backLabel="Révisions"
                />

                <section className="space-y-2 rounded-md border p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        {revision.drafted_by && (
                            <Badge variant="outline">
                                <Bot /> {revision.drafted_by}
                            </Badge>
                        )}
                        <span className="text-sm text-muted-foreground">
                            Proposée le{" "}
                            {revision.created_at
                                ? new Date(revision.created_at).toLocaleString(
                                      "fr-FR",
                                  )
                                : "—"}
                        </span>
                        {revision.edit_url && (
                            <Link
                                href={revision.edit_url}
                                className="inline-flex items-center gap-1 text-sm underline"
                            >
                                Ouvrir le contenu{" "}
                                <ExternalLink className="size-3" />
                            </Link>
                        )}
                    </div>
                    <p>{revision.summary}</p>
                </section>

                <div className="space-y-4">
                    {revision.fields.map((change) => (
                        <section
                            key={`${change.locale}-${change.field}`}
                            className="space-y-3 rounded-md border p-4"
                        >
                            <div className="flex items-center gap-2">
                                <span className="font-medium">
                                    {change.field}
                                </span>
                                <Badge variant="secondary">
                                    {change.locale.toUpperCase()}
                                </Badge>
                            </div>

                            {revision.status === "pending" &&
                                !same(change.before, change.current) && (
                                    <p className="flex items-start gap-2 rounded-md bg-amber-500/10 p-2 text-sm text-amber-700 dark:text-amber-400">
                                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                        Ce champ a été modifié depuis la
                                        proposition : l'appliquer remplacera la
                                        valeur actuelle.
                                    </p>
                                )}

                            <div className="grid gap-3 md:grid-cols-2">
                                <div className="space-y-1 rounded-md bg-muted/50 p-3">
                                    <p className="text-xs font-medium text-muted-foreground uppercase">
                                        Avant
                                    </p>
                                    <Value value={change.before} />
                                </div>
                                <div className="space-y-1 rounded-md border border-primary/40 p-3">
                                    <p className="text-xs font-medium text-muted-foreground uppercase">
                                        Proposé
                                    </p>
                                    <Value value={change.after} />
                                </div>
                            </div>
                        </section>
                    ))}
                </div>

                {revision.status === "pending" ? (
                    <div className="flex flex-wrap gap-2">
                        <Button
                            disabled={processing}
                            onClick={() => decide("apply")}
                        >
                            <Check /> Appliquer sur le site
                        </Button>
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => decide("reject")}
                        >
                            <X /> Rejeter
                        </Button>
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {revision.status === "applied"
                            ? "Révision appliquée"
                            : "Révision rejetée"}
                        {revision.decided_at
                            ? ` le ${new Date(revision.decided_at).toLocaleString("fr-FR")}`
                            : ""}
                        .
                    </p>
                )}
            </div>
        </>
    );
}

RevisionShow.layout = {
    breadcrumbs: [
        { title: "Révisions proposées", href: revisionsIndex() },
        { title: "Détail", href: "" },
    ],
};
