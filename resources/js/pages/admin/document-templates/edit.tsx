import { Head, Link, router } from "@inertiajs/react";
import { Loader2 } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import DocumentTemplateController from "@/actions/App/Http/Controllers/Admin/DocumentTemplateController";
import Heading from "@/components/heading";
import TranslatableField from "@/components/translatable-field";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { csrfToken } from "@/hooks/use-ai-text-assist";
import { DOCUMENT_TYPES } from "@/lib/commercial-documents";
import { cn } from "@/lib/utils";
import { index as documentsIndex } from "@/routes/admin/commercial-documents";
import type { CommercialDocumentType, Translatable } from "@/types";

type Template = {
    layout: "classic" | "modern" | "minimal";
    primary_color: string;
    accent_color: string;
    font: string;
    show_logo: boolean;
    show_bank_details: boolean;
    show_signature: boolean;
    title: Partial<Translatable>;
    intro: Partial<Translatable>;
    footer: Partial<Translatable>;
    logo_url: string | null;
};

const LAYOUTS = [
    {
        value: "classic",
        label: "Classique",
        description: "En-tête sobre, tableau aux couleurs du modèle",
    },
    {
        value: "modern",
        label: "Moderne",
        description: "Bandeau de couleur pleine largeur, total souligné",
    },
    {
        value: "minimal",
        label: "Épurée",
        description: "Sans aplat, de simples filets",
    },
] as const;

const TOGGLES = [
    { name: "show_logo", label: "Afficher le logo" },
    { name: "show_bank_details", label: "Afficher les coordonnées bancaires" },
    {
        name: "show_signature",
        label: "Bloc « Bon pour accord » (date et signature du client)",
    },
] as const;

/**
 * Le design d'un type de document (devis, proforma, facture), édité à gauche et prévisualisé
 * à droite sur un document d'exemple, avant même d'enregistrer.
 */
export default function DocumentTemplateEdit({
    type,
    template,
    fonts,
}: {
    type: CommercialDocumentType;
    template: Template;
    fonts: Record<string, string>;
}) {
    const formRef = useRef<HTMLFormElement>(null);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [previewLocale, setPreviewLocale] = useState<"fr" | "en">("fr");
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [primary, setPrimary] = useState(template.primary_color);
    const [accent, setAccent] = useState(template.accent_color);

    /** Les champs du formulaire, cases décochées comprises (une case absente vaut 0). */
    const formData = useCallback((): FormData => {
        const data = new FormData(formRef.current ?? undefined);

        TOGGLES.forEach(({ name }) =>
            data.set(name, data.has(name) ? "1" : "0"),
        );
        data.set("remove_logo", data.has("remove_logo") ? "1" : "0");

        return data;
    }, []);

    const refreshPreview = useCallback(async () => {
        setLoading(true);
        const data = formData();
        data.set("preview_locale", previewLocale);

        try {
            const response = await fetch(
                DocumentTemplateController.preview.url(type),
                {
                    method: "POST",
                    headers: {
                        "X-XSRF-TOKEN": csrfToken(),
                        // JSON : une erreur de validation revient en 422, pas en page HTML.
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: data,
                },
            );

            if (response.status === 422) {
                const body = (await response.json()) as {
                    errors?: Record<string, string[]>;
                };
                setErrors(
                    Object.fromEntries(
                        Object.entries(body.errors ?? {}).map(
                            ([field, messages]) => [field, messages[0]],
                        ),
                    ),
                );

                return;
            }

            // Seul un PDF s'affiche dans l'aperçu (jamais une page de l'application).
            if (
                response.ok &&
                response.headers
                    .get("Content-Type")
                    ?.startsWith("application/pdf")
            ) {
                setErrors({});
                const url = URL.createObjectURL(await response.blob());
                setPreviewUrl((previous) => {
                    if (previous) {
                        URL.revokeObjectURL(previous);
                    }

                    return url;
                });
            }
        } finally {
            setLoading(false);
        }
    }, [formData, previewLocale, type]);

    const schedulePreview = () => {
        if (timer.current) {
            clearTimeout(timer.current);
        }

        timer.current = setTimeout(() => void refreshPreview(), 600);
    };

    useEffect(() => {
        void refreshPreview();
    }, [refreshPreview]);

    const save = () => {
        router.post(DocumentTemplateController.update.url(type), formData(), {
            preserveScroll: true,
            forceFormData: true,
            onStart: () => setSaving(true),
            onFinish: () => setSaving(false),
            onSuccess: () => setErrors({}),
            onError: (bag) => setErrors(bag),
        });
    };

    return (
        <>
            <Head title={`Modèle · ${DOCUMENT_TYPES[type]}`} />

            <div className="w-full space-y-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <Heading
                        title="Modèles de documents"
                        description="Le design de chaque type de document, appliqué à tous ses PDF"
                    />
                    <nav
                        className="flex flex-wrap gap-1 rounded-lg border p-1"
                        aria-label="Type de document"
                    >
                        {(
                            Object.keys(
                                DOCUMENT_TYPES,
                            ) as CommercialDocumentType[]
                        ).map((item) => (
                            <Link
                                key={item}
                                href={DocumentTemplateController.edit(item)}
                                className={cn(
                                    "rounded-md px-3 py-1.5 text-sm",
                                    item === type
                                        ? "bg-primary text-primary-foreground"
                                        : "hover:bg-muted",
                                )}
                                aria-current={
                                    item === type ? "page" : undefined
                                }
                            >
                                {DOCUMENT_TYPES[item]}
                            </Link>
                        ))}
                    </nav>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
                    <form
                        ref={formRef}
                        key={type}
                        onChange={schedulePreview}
                        onSubmit={(event) => {
                            event.preventDefault();
                            save();
                        }}
                        className="space-y-6"
                    >
                        <FieldGroup>
                            <Field>
                                <FieldLabel>Mise en page</FieldLabel>
                                <div className="grid gap-2">
                                    {LAYOUTS.map((layout) => (
                                        <label
                                            key={layout.value}
                                            className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-[:checked]:border-primary has-[:checked]:bg-muted/50"
                                        >
                                            <input
                                                type="radio"
                                                name="layout"
                                                value={layout.value}
                                                defaultChecked={
                                                    template.layout ===
                                                    layout.value
                                                }
                                                className="mt-1"
                                            />
                                            <span>
                                                <span className="block text-sm font-medium">
                                                    {layout.label}
                                                </span>
                                                <span className="block text-sm text-muted-foreground">
                                                    {layout.description}
                                                </span>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <FieldError>{errors.layout}</FieldError>
                            </Field>

                            <div className="grid grid-cols-2 gap-4">
                                <Field data-invalid={!!errors.primary_color}>
                                    <FieldLabel htmlFor="primary_color">
                                        Couleur principale
                                    </FieldLabel>
                                    <div className="flex gap-2">
                                        <input
                                            type="color"
                                            aria-label="Choisir la couleur principale"
                                            value={primary}
                                            onChange={(event) =>
                                                setPrimary(event.target.value)
                                            }
                                            className="h-9 w-12 cursor-pointer rounded border"
                                        />
                                        <Input
                                            id="primary_color"
                                            name="primary_color"
                                            value={primary}
                                            onChange={(event) =>
                                                setPrimary(event.target.value)
                                            }
                                        />
                                    </div>
                                    <FieldError>
                                        {errors.primary_color}
                                    </FieldError>
                                </Field>
                                <Field data-invalid={!!errors.accent_color}>
                                    <FieldLabel htmlFor="accent_color">
                                        Couleur d'accent
                                    </FieldLabel>
                                    <div className="flex gap-2">
                                        <input
                                            type="color"
                                            aria-label="Choisir la couleur d'accent"
                                            value={accent}
                                            onChange={(event) =>
                                                setAccent(event.target.value)
                                            }
                                            className="h-9 w-12 cursor-pointer rounded border"
                                        />
                                        <Input
                                            id="accent_color"
                                            name="accent_color"
                                            value={accent}
                                            onChange={(event) =>
                                                setAccent(event.target.value)
                                            }
                                        />
                                    </div>
                                    <FieldError>
                                        {errors.accent_color}
                                    </FieldError>
                                </Field>
                            </div>

                            <Field data-invalid={!!errors.font}>
                                <FieldLabel htmlFor="font">Police</FieldLabel>
                                <NativeSelect
                                    id="font"
                                    name="font"
                                    defaultValue={template.font}
                                >
                                    {Object.entries(fonts).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </NativeSelect>
                                <FieldError>{errors.font}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.logo}>
                                <FieldLabel htmlFor="logo">Logo</FieldLabel>
                                {template.logo_url && (
                                    <img
                                        src={template.logo_url}
                                        alt="Logo actuel"
                                        className="max-h-14 w-fit rounded border bg-white p-1"
                                    />
                                )}
                                <Input
                                    id="logo"
                                    name="logo"
                                    type="file"
                                    accept="image/png,image/jpeg"
                                />
                                <FieldDescription>
                                    PNG ou JPEG, 2 Mo au plus. Un PNG
                                    transparent rend mieux sur le bandeau «
                                    Moderne ».
                                </FieldDescription>
                                {template.logo_url && (
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="remove_logo"
                                            value="1"
                                        />{" "}
                                        Retirer le logo
                                    </label>
                                )}
                                <FieldError>{errors.logo}</FieldError>
                            </Field>

                            <div className="space-y-3">
                                {TOGGLES.map((toggle) => (
                                    <Field
                                        key={toggle.name}
                                        orientation="horizontal"
                                    >
                                        <Checkbox
                                            id={toggle.name}
                                            name={toggle.name}
                                            value="1"
                                            defaultChecked={
                                                template[toggle.name]
                                            }
                                            onCheckedChange={schedulePreview}
                                        />
                                        <FieldLabel htmlFor={toggle.name}>
                                            {toggle.label}
                                        </FieldLabel>
                                    </Field>
                                ))}
                            </div>

                            <TranslatableField
                                name="title"
                                label="Titre du document"
                                defaultValue={template.title}
                                errors={{
                                    fr: errors["title.fr"],
                                    en: errors["title.en"],
                                }}
                            />
                            <FieldDescription className="-mt-4">
                                Vide : « {DOCUMENT_TYPES[type]} ».
                            </FieldDescription>
                            <TranslatableField
                                name="intro"
                                label="Texte avant les lignes"
                                textarea
                                defaultValue={template.intro}
                                errors={{
                                    fr: errors["intro.fr"],
                                    en: errors["intro.en"],
                                }}
                            />
                            <TranslatableField
                                name="footer"
                                label="Texte du pied de page"
                                defaultValue={template.footer}
                                errors={{
                                    fr: errors["footer.fr"],
                                    en: errors["footer.en"],
                                }}
                            />

                            <Button disabled={saving}>
                                {saving && <Loader2 className="animate-spin" />}
                                Enregistrer le modèle
                            </Button>
                        </FieldGroup>
                    </form>

                    <div className="space-y-3 xl:sticky xl:top-4 xl:self-start">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-sm text-muted-foreground">
                                Aperçu sur un document d'exemple
                                {loading && (
                                    <Loader2 className="ml-2 inline size-4 animate-spin" />
                                )}
                            </p>
                            <NativeSelect
                                className="w-auto"
                                aria-label="Langue de l'aperçu"
                                value={previewLocale}
                                onChange={(event) =>
                                    setPreviewLocale(
                                        event.target.value as "fr" | "en",
                                    )
                                }
                            >
                                <option value="fr">Français</option>
                                <option value="en">Anglais</option>
                            </NativeSelect>
                        </div>
                        {previewUrl ? (
                            <iframe
                                src={previewUrl}
                                title="Aperçu du modèle"
                                className="h-[85vh] w-full rounded-lg border bg-white"
                            />
                        ) : (
                            <div className="flex h-[85vh] items-center justify-center rounded-lg border">
                                <Loader2 className="animate-spin text-muted-foreground" />
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

DocumentTemplateEdit.layout = {
    breadcrumbs: [
        { title: "Devis et factures", href: documentsIndex() },
        { title: "Modèles", href: "" },
    ],
};
