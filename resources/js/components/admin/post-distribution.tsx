import { Form, Link, router } from '@inertiajs/react';
import {
    Check,
    Copy,
    ExternalLink,
    Loader2,
    Send,
    Share2,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import PostSyndicationController from '@/actions/App/Http/Controllers/Admin/PostSyndicationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { useClipboard } from '@/hooks/use-clipboard';
import { edit as syndicationSettings } from '@/routes/admin/syndication';

export type Syndication = {
    platform: 'devto' | 'hashnode';
    status: 'pending' | 'published' | 'failed';
    external_url: string | null;
    locale: string;
    error: string | null;
    published_at: string | null;
};

type Network = 'linkedin' | 'x' | 'facebook' | 'whatsapp';

export type SocialKit = {
    url: string;
    image: string;
    texts: Record<Network, string | null>;
    links: Record<Network, string>;
};

const NETWORKS: { key: Network; label: string; hint: string }[] = [
    {
        key: 'linkedin',
        label: 'LinkedIn',
        hint: 'S’ouvre avec le texte et le lien.',
    },
    { key: 'x', label: 'X', hint: 'S’ouvre avec le texte et le lien.' },
    {
        key: 'facebook',
        label: 'Facebook',
        hint: 'Facebook ne prend que le lien : copiez le texte, puis collez-le.',
    },
    {
        key: 'whatsapp',
        label: 'WhatsApp',
        hint: 'Pour un statut : « Partager (téléphone) », puis WhatsApp et « Mon statut ».',
    },
];

/**
 * Faire connaître un article depuis sa fiche : le republier sur dev.to ou Hashnode (lien
 * canonique vers le site), et le partager sur LinkedIn, X, Facebook et WhatsApp avec des
 * textes rédigés par l'IA, un clic par réseau.
 */
export default function PostDistribution({
    postId,
    isLive,
    syndications,
    platforms,
    socialKits,
}: {
    postId: number;
    isLive: boolean;
    syndications: Syndication[];
    platforms: { value: 'devto' | 'hashnode'; label: string }[];
    socialKits: { fr: SocialKit; en: SocialKit | null };
}) {
    const [publishing, setPublishing] = useState<string | null>(null);
    const [generating, setGenerating] = useState(false);
    const [locale, setLocale] = useState<'fr' | 'en'>('fr');
    const [, copy] = useClipboard();
    const [copiedNetwork, setCopiedNetwork] = useState<Network | null>(null);
    const kit = socialKits[locale] ?? socialKits.fr;
    const labels = { devto: 'dev.to', hashnode: 'Hashnode' } as const;

    const publish = (platform: string) =>
        router.post(
            PostSyndicationController.store.url({ post: postId, platform }),
            {},
            {
                preserveScroll: true,
                onStart: () => setPublishing(platform),
                onFinish: () => setPublishing(null),
            },
        );

    const generate = () =>
        router.post(
            PostSyndicationController.generateSocialTexts.url({
                post: postId,
                locale,
            }),
            {},
            {
                preserveScroll: true,
                onStart: () => setGenerating(true),
                onFinish: () => setGenerating(false),
            },
        );

    /** Le partage du téléphone, avec l'image de l'article : c'est là qu'on choisit le statut WhatsApp. */
    const shareNatively = async () => {
        const text = `${kit.texts.whatsapp ?? ''} ${kit.url}`.trim();

        try {
            const image = await fetch(kit.image).then((response) =>
                response.blob(),
            );
            const file = new File([image], 'article.png', {
                type: 'image/png',
            });

            if (navigator.canShare?.({ files: [file] })) {
                await navigator.share({ files: [file], text });

                return;
            }

            await navigator.share({ text, url: kit.url });
        } catch {
            window.open(kit.links.whatsapp, '_blank', 'noopener');
        }
    };

    return (
        <div className="space-y-8">
            <div className="space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-medium">Republication</p>
                    <Link
                        href={syndicationSettings()}
                        className="text-sm text-muted-foreground underline"
                    >
                        Réglages
                    </Link>
                </div>
                {platforms.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Ajoutez une clé dev.to ou Hashnode dans les réglages
                        pour republier vos articles avec un lien canonique vers
                        le site.
                    </p>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {platforms.map((platform) => {
                            const done = syndications.find(
                                (syndication) =>
                                    syndication.platform === platform.value,
                            );

                            return (
                                <div
                                    key={platform.value}
                                    className="space-y-2 rounded-lg border p-3"
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <strong>{platform.label}</strong>
                                        {done && (
                                            <Badge
                                                variant={
                                                    done.status === 'failed'
                                                        ? 'destructive'
                                                        : done.status ===
                                                            'published'
                                                          ? 'default'
                                                          : 'secondary'
                                                }
                                            >
                                                {done.status === 'published'
                                                    ? 'Publié'
                                                    : done.status === 'failed'
                                                      ? 'Échec'
                                                      : 'En cours'}
                                            </Badge>
                                        )}
                                    </div>
                                    {done?.error && (
                                        <p className="text-sm text-destructive">
                                            {done.error}
                                        </p>
                                    )}
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                !isLive || publishing !== null
                                            }
                                            onClick={() =>
                                                publish(platform.value)
                                            }
                                            title={
                                                isLive
                                                    ? undefined
                                                    : 'Publiez d’abord l’article sur le site.'
                                            }
                                        >
                                            {publishing === platform.value ? (
                                                <Loader2 className="animate-spin" />
                                            ) : (
                                                <Send />
                                            )}
                                            {done?.status === 'published'
                                                ? 'Mettre à jour'
                                                : 'Publier'}
                                        </Button>
                                        {done?.external_url && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                asChild
                                            >
                                                <a
                                                    href={done.external_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <ExternalLink /> Voir sur{' '}
                                                    {labels[platform.value]}
                                                </a>
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            <div className="space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-medium">Kit de partage</p>
                    <div className="flex flex-wrap items-center gap-2">
                        {socialKits.en && (
                            <div className="flex rounded-md border p-0.5 text-sm">
                                {(['fr', 'en'] as const).map((candidate) => (
                                    <button
                                        key={candidate}
                                        type="button"
                                        onClick={() => setLocale(candidate)}
                                        className={`rounded px-2 py-0.5 ${locale === candidate ? 'bg-muted font-medium' : ''}`}
                                    >
                                        {candidate.toUpperCase()}
                                    </button>
                                ))}
                            </div>
                        )}
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={generate}
                            disabled={generating}
                        >
                            {generating ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Sparkles />
                            )}
                            {Object.values(kit.texts).some(Boolean)
                                ? 'Réécrire avec l’IA'
                                : 'Rédiger avec l’IA'}
                        </Button>
                    </div>
                </div>

                <Form
                    {...PostSyndicationController.updateSocialTexts.form(
                        postId,
                    )}
                    key={`${locale}-${JSON.stringify(kit.texts)}`}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing }) => (
                        <>
                            <input type="hidden" name="locale" value={locale} />
                            {NETWORKS.map((network) => (
                                <Field key={network.key}>
                                    <FieldLabel
                                        htmlFor={`social-${network.key}`}
                                    >
                                        {network.label}
                                    </FieldLabel>
                                    <Textarea
                                        id={`social-${network.key}`}
                                        name={`texts[${network.key}]`}
                                        rows={
                                            network.key === 'linkedin' ? 6 : 3
                                        }
                                        defaultValue={
                                            kit.texts[network.key] ?? ''
                                        }
                                        placeholder="Rédigez-le, ou laissez l’IA le faire."
                                    />
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <a
                                                href={kit.links[network.key]}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <ExternalLink /> Ouvrir{' '}
                                                {network.label}
                                            </a>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            type="button"
                                            onClick={() => {
                                                void copy(
                                                    `${kit.texts[network.key] ?? ''}\n\n${kit.url}`.trim(),
                                                );
                                                setCopiedNetwork(network.key);
                                            }}
                                        >
                                            {copiedNetwork === network.key ? (
                                                <Check />
                                            ) : (
                                                <Copy />
                                            )}
                                            Copier le texte
                                        </Button>
                                        {network.key === 'whatsapp' && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                type="button"
                                                onClick={() =>
                                                    void shareNatively()
                                                }
                                            >
                                                <Share2 /> Partager (téléphone)
                                            </Button>
                                        )}
                                        <span className="text-xs text-muted-foreground">
                                            {network.hint}
                                        </span>
                                    </div>
                                </Field>
                            ))}
                            <Button size="sm" disabled={processing}>
                                Enregistrer les textes
                            </Button>
                            <p className="text-xs text-muted-foreground">
                                Les boutons « Ouvrir » utilisent les textes
                                enregistrés : enregistrez vos retouches d’abord.
                            </p>
                        </>
                    )}
                </Form>
            </div>
        </div>
    );
}
