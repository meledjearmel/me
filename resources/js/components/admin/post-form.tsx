import { getHTMLFromFragment, type Editor } from "@tiptap/core";
import { Fragment } from "@tiptap/pm/model";
import { Languages, Loader2, X } from "lucide-react";
import { useRef, useState } from "react";
import AiAssistController from "@/actions/App/Http/Controllers/Admin/AiAssistController";
import FormSelect from "@/components/admin/form-select";
import PostEditor from "@/components/admin/post-editor/post-editor";
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { post as postJson, type Locale } from "@/hooks/use-ai-text-assist";
import type { Post } from "@/types";

/** Taille d'un fragment envoyé à la traduction (sous la limite du serveur). */
const TRANSLATION_CHUNK_LENGTH = 5000;

function slugify(value: string): string {
    return value
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

/** Découpe le document en fragments HTML de blocs entiers. */
function htmlChunks(editor: Editor): string[] {
    const chunks: string[] = [];
    let current: string[] = [];
    let length = 0;

    editor.state.doc.forEach((node) => {
        const html = getHTMLFromFragment(Fragment.from(node), editor.schema);

        if (
            length + html.length > TRANSLATION_CHUNK_LENGTH &&
            current.length > 0
        ) {
            chunks.push(current.join(""));
            current = [];
            length = 0;
        }

        current.push(html);
        length += html.length;
    });

    if (current.length > 0) {
        chunks.push(current.join(""));
    }

    return chunks;
}

/** Sélecteur de tags : saisie libre, Entrée ou virgule pour ajouter. */
function TagsField({
    defaultValue,
    suggestions,
    error,
}: {
    defaultValue: string[];
    suggestions: string[];
    error?: string;
}) {
    const [tags, setTags] = useState(defaultValue);
    const [draft, setDraft] = useState("");

    function add(value: string) {
        const tag = value.trim();

        if (
            tag !== "" &&
            !tags.some(
                (existing) => existing.toLowerCase() === tag.toLowerCase(),
            )
        ) {
            setTags([...tags, tag]);
        }

        setDraft("");
    }

    return (
        <Field data-invalid={!!error}>
            <FieldLabel htmlFor="tag-input">Tags</FieldLabel>
            {tags.map((tag) => (
                <input key={tag} type="hidden" name="tags[]" value={tag} />
            ))}
            <div className="flex flex-wrap gap-1.5">
                {tags.map((tag) => (
                    <span
                        key={tag}
                        className="inline-flex items-center gap-1 rounded-full bg-secondary px-2.5 py-0.5 text-sm"
                    >
                        {tag}
                        <button
                            type="button"
                            aria-label={`Retirer ${tag}`}
                            onClick={() =>
                                setTags(tags.filter((item) => item !== tag))
                            }
                        >
                            <X className="size-3" />
                        </button>
                    </span>
                ))}
            </div>
            <Input
                id="tag-input"
                list="post-tag-suggestions"
                value={draft}
                placeholder="Laravel, React…"
                onChange={(event) => setDraft(event.target.value)}
                onBlur={() => add(draft)}
                onKeyDown={(event) => {
                    if (event.key === "Enter" || event.key === ",") {
                        event.preventDefault();
                        add(draft);
                    }
                }}
            />
            <datalist id="post-tag-suggestions">
                {suggestions
                    .filter((tag) => !tags.includes(tag))
                    .map((tag) => (
                        <option key={tag} value={tag} />
                    ))}
            </datalist>
            <FieldError>{error}</FieldError>
        </Field>
    );
}

/** Champs communs à la création et à la modification d'un article. */
export default function PostForm({
    post,
    tags,
    errors,
    processing,
    submitLabel,
}: {
    post?: Post;
    tags: string[];
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
}) {
    const formRef = useRef<HTMLDivElement>(null);
    const editors = useRef<Partial<Record<Locale, Editor>>>({});
    const [locale, setLocale] = useState<Locale>("fr");
    const [slugTouched, setSlugTouched] = useState(!!post);
    const [slug, setSlug] = useState(post?.slug ?? "");
    const [translating, setTranslating] = useState<string | null>(null);
    const [translationError, setTranslationError] = useState<string | null>(
        null,
    );

    function articleMeta(target: Locale) {
        return () => {
            const read = (field: string) =>
                (
                    formRef.current?.querySelector<
                        HTMLInputElement | HTMLTextAreaElement
                    >(`[name="${field}[${target}]"]`)?.value ?? ""
                ).trim();

            return { title: read("title"), excerpt: read("excerpt") };
        };
    }

    async function translateBody(source: Locale, target: Locale) {
        const from = editors.current[source];
        const to = editors.current[target];

        if (!from || !to || from.isEmpty) {
            setTranslationError(
                `Rédigez d’abord la version ${source.toUpperCase()}.`,
            );

            return;
        }

        if (
            !to.isEmpty &&
            !window.confirm(
                `Remplacer le contenu ${target.toUpperCase()} par la traduction ?`,
            )
        ) {
            return;
        }

        setTranslationError(null);
        const chunks = htmlChunks(from);
        const translated: string[] = [];

        try {
            for (const [index, chunk] of chunks.entries()) {
                setTranslating(`${index + 1}/${chunks.length}`);
                const { text } = await postJson(
                    AiAssistController.translateHtml.url(),
                    {
                        html: chunk,
                        source_locale: source,
                        target_locale: target,
                    },
                );
                translated.push(text);
            }

            to.commands.setContent(translated.join(""), { emitUpdate: true });
        } catch {
            setTranslationError(
                "Traduction indisponible pour le moment, réessayez.",
            );
        } finally {
            setTranslating(null);
        }
    }

    return (
        <div
            ref={formRef}
            className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]"
        >
            <FieldGroup className="min-w-0">
                <TranslatableField
                    name="title"
                    label="Titre"
                    required
                    defaultValue={post?.title}
                    errors={{
                        fr: errors["title.fr"],
                        en: errors["title.en"],
                    }}
                />

                <Tabs
                    value={locale}
                    onValueChange={(value) => setLocale(value as Locale)}
                >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <TabsList>
                            <TabsTrigger value="fr">Contenu FR *</TabsTrigger>
                            <TabsTrigger value="en">Contenu EN</TabsTrigger>
                        </TabsList>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={translating !== null}
                            onClick={() =>
                                void translateBody(
                                    locale === "fr" ? "en" : "fr",
                                    locale,
                                )
                            }
                        >
                            {translating ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Languages />
                            )}
                            {translating
                                ? `Traduction ${translating}…`
                                : `Traduire depuis ${locale === "fr" ? "l’anglais" : "le français"}`}
                        </Button>
                    </div>
                    {translationError && (
                        <p className="text-sm text-destructive">
                            {translationError}
                        </p>
                    )}
                    {(["fr", "en"] as const).map((item) => (
                        <TabsContent
                            key={item}
                            value={item}
                            forceMount
                            className="data-[state=inactive]:hidden"
                        >
                            <PostEditor
                                name={`body[${item}]`}
                                defaultValue={post?.body[item]}
                                locale={item}
                                articleMeta={articleMeta(item)}
                                onReady={(editor) => {
                                    editors.current[item] = editor;
                                }}
                            />
                            <FieldError className="mt-2">
                                {errors[`body.${item}`]}
                            </FieldError>
                        </TabsContent>
                    ))}
                </Tabs>
            </FieldGroup>

            <FieldGroup className="h-fit rounded-xl border p-4 xl:sticky xl:top-4">
                <Field data-invalid={!!errors.status}>
                    <FieldLabel htmlFor="status">Statut *</FieldLabel>
                    <FormSelect
                        id="status"
                        name="status"
                        defaultValue={post?.status ?? "draft"}
                    >
                        <option value="draft">Brouillon</option>
                        <option value="published">Publié</option>
                    </FormSelect>
                    <FieldError>{errors.status}</FieldError>
                </Field>

                <Field data-invalid={!!errors.published_at}>
                    <FieldLabel htmlFor="published_at">
                        Date de publication
                    </FieldLabel>
                    <Input
                        id="published_at"
                        name="published_at"
                        type="datetime-local"
                        defaultValue={post?.published_at?.slice(0, 16) ?? ""}
                    />
                    <FieldDescription>
                        Vide : maintenant. Une date future programme la
                        publication.
                    </FieldDescription>
                    <FieldError>{errors.published_at}</FieldError>
                </Field>

                <Field orientation="horizontal">
                    <input type="hidden" name="is_featured" value="0" />
                    <Checkbox
                        id="is_featured"
                        name="is_featured"
                        value="1"
                        defaultChecked={post?.is_featured ?? false}
                    />
                    <FieldLabel htmlFor="is_featured">Mis en avant</FieldLabel>
                </Field>

                <Field data-invalid={!!errors.slug}>
                    <FieldLabel htmlFor="slug">Slug *</FieldLabel>
                    <Input
                        id="slug"
                        name="slug"
                        required
                        value={slug}
                        onChange={(event) => {
                            setSlugTouched(true);
                            setSlug(event.target.value);
                        }}
                        onFocus={() => {
                            if (!slugTouched && slug === "") {
                                setSlug(slugify(articleMeta("fr")().title));
                            }
                        }}
                    />
                    <FieldDescription>
                        L’adresse de l’article : /blog/{slug || "…"}
                    </FieldDescription>
                    <FieldError>{errors.slug}</FieldError>
                </Field>

                <TranslatableField
                    name="excerpt"
                    label="Résumé"
                    textarea
                    maxLength={300}
                    defaultValue={post?.excerpt ?? undefined}
                    errors={{
                        fr: errors["excerpt.fr"],
                        en: errors["excerpt.en"],
                    }}
                />

                <TagsField
                    defaultValue={(post?.tags as string[] | undefined) ?? []}
                    suggestions={tags}
                    error={errors.tags}
                />

                <Field data-invalid={!!errors.cover}>
                    <FieldLabel htmlFor="cover">Image de couverture</FieldLabel>
                    {post?.cover_url && (
                        <img
                            src={post.cover_url}
                            alt=""
                            className="aspect-video w-full rounded-md object-cover"
                        />
                    )}
                    <Input
                        id="cover"
                        name="cover"
                        type="file"
                        accept="image/*"
                    />
                    <FieldError>{errors.cover}</FieldError>
                </Field>

                <Button disabled={processing}>{submitLabel}</Button>
            </FieldGroup>
        </div>
    );
}
