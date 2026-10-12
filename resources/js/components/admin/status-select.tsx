import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ContentStatusController from "@/actions/App/Http/Controllers/Admin/ContentStatusController";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { PROJECT_STATUSES, PUBLICATION_STATUSES } from "@/lib/admin-options";

export type StatusContentType =
    | "project"
    | "post"
    | "experience"
    | "education"
    | "certification"
    | "uses_item"
    | "skill";

/**
 * Change le statut d'un contenu depuis sa fiche, sans passer par le formulaire. Publier est
 * refusé tant que l'anglais obligatoire manque : le message s'affiche sous le sélecteur.
 */
export default function StatusSelect({
    type,
    id,
    status,
}: {
    type: StatusContentType;
    id: number;
    status: string;
}) {
    const options =
        type === "project" ? PROJECT_STATUSES : PUBLICATION_STATUSES;
    const error = usePage().props.errors?.status as string | undefined;
    const [processing, setProcessing] = useState(false);

    return (
        <div className="space-y-1">
            <Select
                value={status}
                disabled={processing}
                onValueChange={(value) =>
                    router.patch(
                        ContentStatusController.url({ type, id }),
                        { status: value },
                        {
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                        },
                    )
                }
            >
                <SelectTrigger className="h-8 w-36" aria-label="Statut">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {error && (
                <p className="max-w-xs text-xs text-destructive">{error}</p>
            )}
        </div>
    );
}
