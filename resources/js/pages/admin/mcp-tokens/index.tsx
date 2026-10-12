import { Form, Head, router, usePage } from "@inertiajs/react";
import { Check, Copy, KeyRound, Trash2 } from "lucide-react";
import { useState } from "react";
import McpTokenController from "@/actions/App/Http/Controllers/Admin/McpTokenController";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import { Field, FieldError, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { destroy as destroyConnector } from "@/routes/admin/mcp-connectors";
import { index as pageIndex } from "@/routes/admin/mcp-tokens";

type McpToken = {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
};

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString("fr-FR") : "jamais";
}

function CopyButton({ value }: { value: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            aria-label="Copier"
            onClick={() => {
                void navigator.clipboard.writeText(value);
                setCopied(true);
                setTimeout(() => setCopied(false), 1500);
            }}
        >
            {copied ? <Check /> : <Copy />}
        </Button>
    );
}

/**
 * Jetons du serveur MCP du portfolio (Claude Code, Codex…). Limités au MCP : l'API de
 * l'app mobile les refuse. Le jeton en clair n'est montré qu'une fois, juste après sa création.
 */
type Connector = {
    id: string;
    name: string;
    last_connected_at: string | null;
};

export default function McpTokensIndex({
    tokens,
    connectors,
    serverUrl,
}: {
    tokens: McpToken[];
    connectors: Connector[];
    serverUrl: string;
}) {
    const newToken = usePage().flash.mcpToken as string | undefined;
    const claudeCommand = `claude mcp add --transport http portfolio ${serverUrl} --header "Authorization: Bearer ${newToken ?? "<jeton>"}"`;

    return (
        <>
            <Head title="Serveur MCP" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Serveur MCP"
                    description="Claude Code, Codex, ou les connecteurs Claude et ChatGPT rédigent des brouillons et proposent des modifications, à relire, traduire et publier ici."
                />

                <section className="space-y-2">
                    <h2 className="font-medium">Adresse du serveur</h2>
                    <div className="flex items-center gap-2">
                        <code className="flex-1 truncate rounded-md border bg-muted px-3 py-2 text-sm">
                            {serverUrl}
                        </code>
                        <CopyButton value={serverUrl} />
                    </div>
                </section>

                {newToken && (
                    <section className="space-y-3 rounded-md border border-primary p-4">
                        <h2 className="font-medium">
                            Nouveau jeton : copiez-le maintenant, il ne sera
                            plus affiché
                        </h2>
                        <div className="flex items-center gap-2">
                            <code className="flex-1 truncate rounded-md border bg-muted px-3 py-2 text-sm">
                                {newToken}
                            </code>
                            <CopyButton value={newToken} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Pour Claude Code :
                        </p>
                        <div className="flex items-center gap-2">
                            <code className="flex-1 truncate rounded-md border bg-muted px-3 py-2 text-sm">
                                {claudeCommand}
                            </code>
                            <CopyButton value={claudeCommand} />
                        </div>
                    </section>
                )}

                <section className="space-y-3">
                    <h2 className="font-medium">Créer un jeton</h2>
                    <Form
                        {...McpTokenController.store.form()}
                        resetOnSuccess
                        options={{ preserveScroll: true }}
                        className="flex flex-wrap items-end gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Field
                                    data-invalid={!!errors.name}
                                    className="w-full max-w-xs"
                                >
                                    <FieldLabel htmlFor="name">
                                        Client (ex. Claude Code, Codex)
                                    </FieldLabel>
                                    <Input id="name" name="name" required />
                                    <FieldError>{errors.name}</FieldError>
                                </Field>
                                <Button disabled={processing}>
                                    <KeyRound /> Créer
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-3">
                    <h2 className="font-medium">
                        Connecteurs Claude et ChatGPT
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Dans claude.ai (Paramètres → Connecteurs → Ajouter un
                        connecteur personnalisé) ou ChatGPT (Paramètres →
                        Applications et connecteurs, mode développeur), ajoutez
                        l'adresse du serveur ci-dessus : une fenêtre vous
                        demande de vous connecter ici puis d'autoriser l'accès.
                        Aucun jeton à copier.
                    </p>
                    {connectors.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aucun connecteur autorisé.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {connectors.map((connector) => (
                                <li
                                    key={connector.id}
                                    className="flex items-center gap-3 p-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">
                                            {connector.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Dernière connexion :{" "}
                                            {formatDate(
                                                connector.last_connected_at,
                                            )}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Déconnecter ${connector.name}`}
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Déconnecter « ${connector.name} » ? Il devra être autorisé à nouveau.`,
                                                )
                                            ) {
                                                router.delete(
                                                    destroyConnector.url(
                                                        connector.id,
                                                    ),
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-medium">
                        Jetons actifs (Claude Code, Codex)
                    </h2>
                    {tokens.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aucun jeton : le serveur refuse toute connexion.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {tokens.map((token) => (
                                <li
                                    key={token.id}
                                    className="flex items-center gap-3 p-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">
                                            {token.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Créé le{" "}
                                            {formatDate(token.created_at)} ·
                                            dernière utilisation :{" "}
                                            {formatDate(token.last_used_at)}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Révoquer ${token.name}`}
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Révoquer le jeton « ${token.name} » ? Le client perdra l'accès.`,
                                                )
                                            ) {
                                                router.delete(
                                                    McpTokenController.destroy.url(
                                                        token.id,
                                                    ),
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

McpTokensIndex.layout = {
    breadcrumbs: [{ title: "Serveur MCP", href: pageIndex() }],
};
