import { router } from "@inertiajs/react";
import { Check, ChevronDown } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import ContentStatusController from "@/actions/App/Http/Controllers/Admin/ContentStatusController";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Badge } from "@/components/ui/badge";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import type { StatusContentType } from "@/components/admin/status-select";
import { cn } from "@/lib/utils";

type BadgeVariant = "default" | "secondary" | "outline" | "destructive";

export const PUBLICATION_VARIANTS: Record<string, BadgeVariant> = {
    published: "default",
    draft: "secondary",
    archived: "outline",
};

export type StatusListType =
    | StatusContentType
    | "domain"
    | "job_profile"
    | "contact"
    | "engagement"
    | "job_application"
    | "comment"
    | "testimonial"
    | "appointment"
    | "commercial_document"
    | "revision"
    | "estimator_item"
    | "appointment_type"
    | "celebration";

/**
 * Statuts atteignables depuis le statut actuel, comme le serveur les accepte : un rendez-vous
 * refusé, une révision traitée ou un document payé ne reviennent pas en arrière.
 */
function allowedStatuses(
    type: StatusListType,
    status: string,
    all: string[],
): string[] {
    switch (type) {
        case "appointment":
            return (
                { pending: ["confirmed", "declined"], confirmed: ["declined"] }[
                    status
                ] ?? []
            );
        case "commercial_document":
            if (status === "draft") {
                return ["issued"];
            }

            return ["paid", "cancelled"].includes(status)
                ? []
                : ["accepted", "declined", "paid", "cancelled"].filter(
                      (value) => value !== status,
                  );
        case "revision":
            return status === "pending" ? ["applied", "rejected"] : [];
        default:
            return all.filter((value) => value !== status);
    }
}

/** Les changements à effets lourds (emails, numérotation, contenu modifié) demandent confirmation. */
const CONFIRMATIONS: Partial<Record<StatusListType, Record<string, string>>> = {
    appointment: {
        confirmed: "Le visiteur reçoit un email de confirmation.",
        declined: "Le visiteur est prévenu par email et le créneau est libéré.",
    },
    commercial_document: {
        issued: "Le document reçoit son numéro définitif et ne se modifie plus.",
        cancelled: "Le document est annulé, sans retour possible.",
    },
    revision: {
        applied:
            "Le contenu est remplacé sur le site par la révision proposée.",
    },
};

/**
 * Statut d'une ligne de liste, modifiable sur place : le badge ouvre un menu des statuts
 * possibles, et le changement s'enregistre aussitôt (confirmation pour les plus lourds).
 */
export default function StatusMenu({
    type,
    id,
    status,
    options,
    variants,
}: {
    type: StatusListType;
    id: number;
    status: string;
    options: readonly { value: string; label: string }[];
    variants?: Partial<Record<string, BadgeVariant>>;
}) {
    const [processing, setProcessing] = useState(false);
    const [pending, setPending] = useState<string | null>(null);
    const label =
        options.find((option) => option.value === status)?.label ?? status;
    const allowed = allowedStatuses(
        type,
        status,
        options.map((option) => option.value),
    );
    const variant = variants?.[status] ?? "secondary";

    const save = (value: string) =>
        router.patch(
            ContentStatusController.url({ type, id }),
            { status: value },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) =>
                    toast.error(
                        errors.status ?? "Le statut n’a pas pu être changé.",
                    ),
            },
        );

    const choose = (value: string) => {
        if (CONFIRMATIONS[type]?.[value]) {
            setPending(value);
        } else {
            save(value);
        }
    };

    if (allowed.length === 0) {
        return <Badge variant={variant}>{label}</Badge>;
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger
                    disabled={processing}
                    aria-label={`Statut : ${label}. Changer le statut`}
                    className="rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Badge
                        variant={variant}
                        className={cn(
                            "cursor-pointer gap-1",
                            processing && "opacity-60",
                        )}
                    >
                        {label}
                        <ChevronDown className="size-3" />
                    </Badge>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start">
                    {options.map((option) => (
                        <DropdownMenuItem
                            key={option.value}
                            disabled={
                                option.value !== status &&
                                !allowed.includes(option.value)
                            }
                            onSelect={() =>
                                option.value !== status && choose(option.value)
                            }
                        >
                            <Check
                                className={cn(
                                    option.value !== status && "invisible",
                                )}
                            />
                            {option.label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            <AlertDialog
                open={pending !== null}
                onOpenChange={(open) => !open && setPending(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Passer en «{" "}
                            {
                                options.find(
                                    (option) => option.value === pending,
                                )?.label
                            }{" "}
                            » ?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {pending && CONFIRMATIONS[type]?.[pending]}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => pending && save(pending)}
                        >
                            Confirmer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
