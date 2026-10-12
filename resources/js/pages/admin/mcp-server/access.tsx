import { Deferred, Form, Head, Link, router, usePage } from "@inertiajs/react";
import {
    Activity,
    Check,
    CircleAlert,
    CircleCheck,
    Copy,
    KeyRound,
    Plug,
    Plus,
    Trash2,
} from "lucide-react";
import { useMemo, useState } from "react";
import McpTokenController from "@/actions/App/Http/Controllers/Admin/McpTokenController";
import FormPageHeader from "@/components/admin/form-page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Field, FieldError, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { access, index as serverIndex } from "@/routes/admin/mcp-server";
import ActionTooltip from "@/components/admin/action-tooltip";

type McpToken = {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
};

type Connector = {
    id: string;
    name: string;
    last_connected_at: string | null;
    last_call_at: string | null;
};

type McpActivity = {
    id: number;
    client: string;
    kind: "tool" | "prompt";
    name: string;
    title: string;
    target: { label: string; url: string | null } | null;
    success: boolean;
    error: string | null;
    duration_ms: number;
    created_at: string;
};

const ALL = "__all__";

const relative = new Intl.RelativeTimeFormat("fr", { numeric: "auto" });

/** « il y a 5 minutes », « hier »… ; « jamais » sans date. */
function ago(value: string | null): string {
    if (!value) {
        return "jamais";
    }

    const seconds = (new Date(value).getTime() - Date.now()) / 1000;
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ["day", 86400],
        ["hour", 3600],
        ["minute", 60],
    ];

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return "à l'instant";
}

function CopyButton({ value }: { value: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <ActionTooltip label="Copier">
            <Button
                type="button"
                variant="ghost"
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
        </ActionTooltip>
    );
}

function CopyLine({ value }: { value: string }) {
    return (
        <div className="flex items-center gap-1 rounded-md border bg-muted/40 py-1 pr-1 pl-3">
            <code className="flex-1 truncate text-sm">{value}</code>
            <CopyButton value={value} />
        </div>
    );
}

function Empty({ children }: { children: React.ReactNode }) {
    return (
        <p className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground">
            {children}
        </p>
    );
}

function RevokeButton({
    label,
    onConfirm,
}: {
    label: string;
    onConfirm: () => void;
}) {
    return (
        <ActionTooltip label={label}>
            <Button
                variant="ghost"
                size="icon"
                aria-label={label}
                title={label}
                onClick={() => confirm(`${label} ?`) && onConfirm()}
            >
                <Trash2 />
            </Button>
        </ActionTooltip>
    );
}

function ActivityList({ activities }: { activities: McpActivity[] }) {
    const [client, setClient] = useState(ALL);
    const [errorsOnly, setErrorsOnly] = useState(false);
    const clients = useMemo(
        () =>
            [...new Set(activities.map((activity) => activity.client))].sort(),
        [activities],
    );
    const shown = activities.filter(
        (activity) =>
            (client === ALL || activity.client === client) &&
            (!errorsOnly || !activity.success),
    );

    if (activities.length === 0) {
        return <Empty>Aucun appel pour le moment.</Empty>;
    }

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2">
                <Select value={client} onValueChange={setClient}>
                    <SelectTrigger className="w-44" aria-label="Client">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Tous les clients</SelectItem>
                        {clients.map((name) => (
                            <SelectItem key={name} value={name}>
                                {name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button
                    variant={errorsOnly ? "default" : "outline"}
                    size="sm"
                    onClick={() => setErrorsOnly((value) => !value)}
                >
                    <CircleAlert /> Erreurs seulement
                </Button>
                <span className="ml-auto text-xs text-muted-foreground">
                    {shown.length} appel{shown.length > 1 ? "s" : ""} · 90 jours
                    conservés
                </span>
            </div>

            {shown.length === 0 ? (
                <Empty>Aucun appel ne correspond.</Empty>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {shown.map((activity) => (
                        <li
                            key={activity.id}
                            className="flex gap-3 px-3 py-2.5"
                        >
                            {activity.success ? (
                                <CircleCheck className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                            ) : (
                                <CircleAlert className="mt-0.5 size-4 shrink-0 text-destructive" />
                            )}
                            <div className="min-w-0 flex-1 space-y-0.5">
                                <div className="flex flex-wrap items-center gap-x-2 text-sm">
                                    <span className="font-medium">
                                        {activity.title}
                                    </span>
                                    {activity.target &&
                                        (activity.target.url ? (
                                            <Link
                                                href={activity.target.url}
                                                className="truncate text-muted-foreground underline-offset-2 hover:underline"
                                            >
                                                {activity.target.label}
                                            </Link>
                                        ) : (
                                            <span className="truncate text-muted-foreground">
                                                {activity.target.label}
                                            </span>
                                        ))}
                                </div>
                                {activity.error && (
                                    <p className="text-xs text-destructive">
                                        {activity.error}
                                    </p>
                                )}
                            </div>
                            <div className="shrink-0 text-right text-xs text-muted-foreground">
                                <p>{activity.client}</p>
                                <p
                                    title={new Date(
                                        activity.created_at,
                                    ).toLocaleString("fr-FR")}
                                >
                                    {ago(activity.created_at)}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

/**
 * Accès au serveur MCP : connecteurs Claude et ChatGPT autorisés, jetons de Claude Code et
 * Codex (le jeton en clair n'est montré qu'une fois), et journal des appels.
 */
export default function McpServerAccess({
    tokens,
    connectors,
    activities,
    serverUrl,
}: {
    tokens: McpToken[];
    connectors: Connector[];
    activities?: McpActivity[];
    serverUrl: string;
}) {
    const newToken = usePage().flash.mcpToken as string | undefined;
    const [creating, setCreating] = useState(false);
    const [revealed, setRevealed] = useState(true);
    const claudeCommand = `claude mcp add --transport http portfolio ${serverUrl} --header "Authorization: Bearer ${newToken ?? "<jeton>"}"`;

    return (
        <>
            <Head title="Accès au serveur MCP" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4">
                <FormPageHeader
                    title="Accès"
                    description="Qui peut utiliser le serveur MCP, et ce qu'il y a fait."
                    backHref={serverIndex()}
                    backLabel="Serveur MCP"
                />

                <Tabs
                    defaultValue={newToken ? "tokens" : "connectors"}
                    className="space-y-4"
                >
                    <TabsList>
                        <TabsTrigger value="connectors">
                            <Plug /> Connecteurs
                            <Badge variant="secondary">
                                {connectors.length}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="tokens">
                            <KeyRound /> Jetons
                            <Badge variant="secondary">{tokens.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="activity">
                            <Activity /> Activité
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="connectors" className="space-y-3">
                        {connectors.length === 0 ? (
                            <Empty>Aucun connecteur autorisé.</Empty>
                        ) : (
                            <ul className="divide-y rounded-lg border">
                                {connectors.map((connector) => (
                                    <li
                                        key={connector.id}
                                        className="flex items-center gap-3 px-3 py-2.5"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">
                                                {connector.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Dernier appel{" "}
                                                {ago(connector.last_call_at)} ·
                                                accès renouvelé{" "}
                                                {ago(
                                                    connector.last_connected_at,
                                                )}
                                            </p>
                                        </div>
                                        <RevokeButton
                                            label={`Déconnecter ${connector.name}`}
                                            onConfirm={() =>
                                                router.delete(
                                                    McpTokenController.destroyConnector.url(
                                                        connector.id,
                                                    ),
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                        <details className="text-sm text-muted-foreground">
                            <summary className="cursor-pointer">
                                Ajouter Claude ou ChatGPT
                            </summary>
                            <div className="mt-2 space-y-2">
                                <p>
                                    Dans claude.ai (Paramètres → Connecteurs →
                                    Ajouter un connecteur personnalisé) ou
                                    ChatGPT (Paramètres → Applications et
                                    connecteurs, mode développeur), collez cette
                                    adresse ; une fenêtre vous demande de vous
                                    connecter ici puis d'autoriser l'accès.
                                </p>
                                <CopyLine value={serverUrl} />
                            </div>
                        </details>
                    </TabsContent>

                    <TabsContent value="tokens" className="space-y-3">
                        <div className="flex items-center justify-between gap-2">
                            <p className="text-sm text-muted-foreground">
                                Pour Claude Code et Codex. Limités au MCP,
                                refusés par l'API mobile.
                            </p>
                            <Button size="sm" onClick={() => setCreating(true)}>
                                <Plus /> Nouveau jeton
                            </Button>
                        </div>
                        {tokens.length === 0 ? (
                            <Empty>Aucun jeton.</Empty>
                        ) : (
                            <ul className="divide-y rounded-lg border">
                                {tokens.map((token) => (
                                    <li
                                        key={token.id}
                                        className="flex items-center gap-3 px-3 py-2.5"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">
                                                {token.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Utilisé{" "}
                                                {ago(token.last_used_at)} · créé{" "}
                                                {ago(token.created_at)}
                                            </p>
                                        </div>
                                        <RevokeButton
                                            label={`Révoquer ${token.name}`}
                                            onConfirm={() =>
                                                router.delete(
                                                    McpTokenController.destroy.url(
                                                        token.id,
                                                    ),
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </TabsContent>

                    <TabsContent value="activity">
                        <Deferred
                            data="activities"
                            fallback={
                                <div className="space-y-2">
                                    {[0, 1, 2, 3].map((row) => (
                                        <Skeleton
                                            key={row}
                                            className="h-12 w-full"
                                        />
                                    ))}
                                </div>
                            }
                        >
                            <ActivityList activities={activities ?? []} />
                        </Deferred>
                    </TabsContent>
                </Tabs>
            </div>

            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nouveau jeton</DialogTitle>
                        <DialogDescription>
                            Donnez le nom du client qui l'utilisera, pour le
                            reconnaître dans l'activité et le révoquer.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        {...McpTokenController.store.form()}
                        resetOnSuccess
                        options={{ preserveScroll: true }}
                        onSuccess={() => {
                            setCreating(false);
                            setRevealed(true);
                        }}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Field data-invalid={!!errors.name}>
                                    <FieldLabel htmlFor="name">
                                        Client
                                    </FieldLabel>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Claude Code, Codex…"
                                        required
                                        autoFocus
                                    />
                                    <FieldError>{errors.name}</FieldError>
                                </Field>
                                <Button
                                    disabled={processing}
                                    className="w-full"
                                >
                                    <KeyRound /> Créer le jeton
                                </Button>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!newToken && revealed} onOpenChange={setRevealed}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Jeton créé</DialogTitle>
                        <DialogDescription>
                            Copiez-le maintenant : il ne sera plus affiché.
                        </DialogDescription>
                    </DialogHeader>
                    {newToken && (
                        <div className="space-y-3 text-sm">
                            <CopyLine value={newToken} />
                            <p className="text-muted-foreground">
                                Pour Claude Code :
                            </p>
                            <CopyLine value={claudeCommand} />
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

McpServerAccess.layout = {
    breadcrumbs: [
        { title: "Serveur MCP", href: serverIndex() },
        { title: "Accès", href: access() },
    ],
};
