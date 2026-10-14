import { Form, Head } from '@inertiajs/react';
import SyndicationSettingsController from '@/actions/App/Http/Controllers/Admin/SyndicationSettingsController';
import FormSelect from '@/components/admin/form-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { edit as pageEdit } from '@/routes/admin/syndication';

type PlatformSettings = {
    mode: 'off' | 'manual' | 'auto';
    has_key: boolean;
    locale: 'fr' | 'en';
    auto_since: string | null;
    publication_id?: string | null;
};

const PLATFORMS = [
    {
        key: 'devto',
        label: 'dev.to',
        keyLabel: 'Clé d’API',
        keyHelp: 'dev.to → Settings → Extensions → DEV Community API Keys.',
    },
    {
        key: 'hashnode',
        label: 'Hashnode',
        keyLabel: 'Jeton personnel',
        keyHelp: 'hashnode.com → Settings → Developer → Personal Access Token.',
    },
] as const;

const MODES = [
    { value: 'off', label: 'Coupé' },
    { value: 'manual', label: 'Manuel : bouton sur la fiche de l’article' },
    { value: 'auto', label: 'Auto : chaque article publié part tout seul' },
] as const;

/**
 * Réglages de republication des articles sur dev.to et Hashnode : clé (jamais réaffichée),
 * mode (coupé, manuel, auto), langue republiée, et identifiant de publication Hashnode.
 */
export default function SyndicationEdit({
    settings,
    hashnodePublications,
}: {
    settings: Record<'devto' | 'hashnode', PlatformSettings>;
    /** Chargées après la page (appel à Hashnode) : undefined tant qu'elles arrivent. */
    hashnodePublications?: { id: string; title: string; url: string }[];
}) {
    return (
        <>
            <Head title="Republication" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <Heading
                    title="Republication"
                    description="Vos articles sur dev.to et Hashnode, avec un lien canonique vers le site : l’original reste chez vous pour Google"
                />

                <Form
                    {...SyndicationSettingsController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {PLATFORMS.map((platform) => {
                                const current = settings[platform.key];

                                return (
                                    <fieldset
                                        key={platform.key}
                                        className="space-y-4 rounded-lg border p-4"
                                    >
                                        <legend className="px-1 font-medium">
                                            {platform.label}
                                        </legend>

                                        <Field
                                            data-invalid={
                                                !!errors[`${platform.key}_mode`]
                                            }
                                        >
                                            <FieldLabel
                                                htmlFor={`${platform.key}_mode`}
                                            >
                                                Mode
                                            </FieldLabel>
                                            <FormSelect
                                                id={`${platform.key}_mode`}
                                                name={`${platform.key}_mode`}
                                                defaultValue={current.mode}
                                            >
                                                {MODES.map((mode) => (
                                                    <option
                                                        key={mode.value}
                                                        value={mode.value}
                                                    >
                                                        {mode.label}
                                                    </option>
                                                ))}
                                            </FormSelect>
                                            {current.mode === 'auto' &&
                                                current.auto_since && (
                                                    <FieldDescription>
                                                        Auto depuis le{' '}
                                                        {new Date(
                                                            current.auto_since,
                                                        ).toLocaleDateString(
                                                            'fr-FR',
                                                            {
                                                                dateStyle:
                                                                    'long',
                                                            },
                                                        )}{' '}
                                                        : les articles plus
                                                        anciens se republient
                                                        depuis leur fiche.
                                                    </FieldDescription>
                                                )}
                                        </Field>

                                        <Field
                                            data-invalid={
                                                !!errors[
                                                    `${platform.key}_api_key`
                                                ]
                                            }
                                        >
                                            <FieldLabel
                                                htmlFor={`${platform.key}_api_key`}
                                            >
                                                {platform.keyLabel}
                                            </FieldLabel>
                                            <Input
                                                id={`${platform.key}_api_key`}
                                                name={`${platform.key}_api_key`}
                                                type="password"
                                                autoComplete="off"
                                                placeholder={
                                                    current.has_key
                                                        ? 'Enregistrée (laisser vide pour la garder)'
                                                        : 'Collez la clé'
                                                }
                                            />
                                            <FieldDescription>
                                                {platform.keyHelp} Elle est
                                                chiffrée et n’est jamais
                                                réaffichée.
                                            </FieldDescription>
                                            <FieldError>
                                                {
                                                    errors[
                                                        `${platform.key}_api_key`
                                                    ]
                                                }
                                            </FieldError>
                                        </Field>

                                        {current.has_key && (
                                            <Field orientation="horizontal">
                                                <input
                                                    type="hidden"
                                                    name={`${platform.key}_clear_key`}
                                                    value="0"
                                                />
                                                <Checkbox
                                                    id={`${platform.key}_clear_key`}
                                                    name={`${platform.key}_clear_key`}
                                                    value="1"
                                                />
                                                <FieldLabel
                                                    htmlFor={`${platform.key}_clear_key`}
                                                >
                                                    Retirer la clé enregistrée
                                                </FieldLabel>
                                            </Field>
                                        )}

                                        {platform.key === 'hashnode' && (
                                            <Field
                                                data-invalid={
                                                    !!errors.hashnode_publication_id
                                                }
                                            >
                                                <FieldLabel htmlFor="hashnode_publication_id">
                                                    Identifiant de la
                                                    publication
                                                </FieldLabel>
                                                {hashnodePublications ===
                                                    undefined &&
                                                current.has_key ? (
                                                    <div className="h-9 animate-pulse rounded-md bg-muted" />
                                                ) : hashnodePublications &&
                                                  hashnodePublications.length >
                                                      0 ? (
                                                    <FormSelect
                                                        id="hashnode_publication_id"
                                                        name="hashnode_publication_id"
                                                        defaultValue={
                                                            current.publication_id ??
                                                            hashnodePublications[0]
                                                                .id
                                                        }
                                                    >
                                                        {hashnodePublications.map(
                                                            (publication) => (
                                                                <option
                                                                    key={
                                                                        publication.id
                                                                    }
                                                                    value={
                                                                        publication.id
                                                                    }
                                                                >
                                                                    {
                                                                        publication.title
                                                                    }{' '}
                                                                    —{' '}
                                                                    {
                                                                        publication.url
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </FormSelect>
                                                ) : (
                                                    <Input
                                                        id="hashnode_publication_id"
                                                        name="hashnode_publication_id"
                                                        defaultValue={
                                                            current.publication_id ??
                                                            ''
                                                        }
                                                        placeholder="Laisser vide : retrouvée avec le jeton"
                                                    />
                                                )}
                                                <FieldDescription>
                                                    Collez d’abord le jeton et
                                                    enregistrez : le site
                                                    retrouve vos publications
                                                    tout seul et vous laisse
                                                    choisir. À la main, c’est
                                                    l’identifiant de l’adresse
                                                    du tableau de bord du blog
                                                    (hashnode.com/dashboards/identifiant/general).
                                                </FieldDescription>
                                                <FieldError>
                                                    {
                                                        errors.hashnode_publication_id
                                                    }
                                                </FieldError>
                                            </Field>
                                        )}

                                        <Field>
                                            <FieldLabel
                                                htmlFor={`${platform.key}_locale`}
                                            >
                                                Langue republiée
                                            </FieldLabel>
                                            <FormSelect
                                                id={`${platform.key}_locale`}
                                                name={`${platform.key}_locale`}
                                                defaultValue={current.locale}
                                            >
                                                <option value="en">
                                                    Anglais
                                                </option>
                                                <option value="fr">
                                                    Français
                                                </option>
                                            </FormSelect>
                                            <FieldDescription>
                                                Un article sans version anglaise
                                                part en français.
                                            </FieldDescription>
                                        </Field>
                                    </fieldset>
                                );
                            })}

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

SyndicationEdit.layout = {
    breadcrumbs: [{ title: 'Republication', href: pageEdit() }],
};
