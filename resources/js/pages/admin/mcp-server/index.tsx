import { Head, Link } from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    Copy,
    KeyRound,
    Search,
    Terminal,
    Wrench,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { access, index as pageIndex } from '@/routes/admin/mcp-server';
import ActionTooltip from '@/components/admin/action-tooltip';

type Command = {
    name: string;
    title: string;
    kind: 'prompt' | 'tool';
    description: string;
    usage: string;
    tips: string[];
    example: string;
    /** Phrases à écrire telles quelles dans la conversation (claude.ai, ChatGPT, mobile). */
    requests: string[];
    arguments: { name: string; description: string; required: boolean }[];
};

/** Recherche sans tenir compte des accents ni de la casse. */
const normalize = (text: string) =>
    text
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();

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

function CommandGrid({
    commands,
    onShow,
}: {
    commands: Command[];
    onShow: (command: Command) => void;
}) {
    if (commands.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">
                Aucun résultat.
            </p>
        );
    }

    return (
        <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            {commands.map((command) => (
                <button
                    key={command.name}
                    type="button"
                    onClick={() => onShow(command)}
                    className="group flex items-center gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-muted/50"
                >
                    <div className="min-w-0 flex-1">
                        <p className="font-medium">{command.title}</p>
                        <p className="truncate font-mono text-xs text-muted-foreground">
                            {command.name}
                        </p>
                    </div>
                    <ChevronRight className="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
                </button>
            ))}
        </div>
    );
}

/**
 * Le serveur MCP du portfolio : son adresse et ses commandes, chacune avec son mode d'emploi.
 * Les jetons et connecteurs sont sur la page « Accès ».
 */
export default function McpServerIndex({
    serverUrl,
    prompts,
    tools,
}: {
    serverUrl: string;
    prompts: Command[];
    tools: Command[];
}) {
    const [shown, setShown] = useState<Command | null>(null);
    const [query, setQuery] = useState('');

    const filter = useMemo(() => {
        const terms = normalize(query.trim());

        return (commands: Command[]) =>
            terms === ''
                ? commands
                : commands.filter((command) =>
                      normalize(
                          `${command.title} ${command.name} ${command.usage} ${command.requests.join(' ')}`,
                      ).includes(terms),
                  );
    }, [query]);

    const shownPrompts = filter(prompts);
    const shownTools = filter(tools);

    return (
        <>
            <Head title="Serveur MCP" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Serveur MCP"
                        description="Brouillons et propositions de modification rédigés par Claude ou ChatGPT, à relire ici."
                    />
                    <Button asChild>
                        <Link href={access()}>
                            <KeyRound /> Gérer les accès
                        </Link>
                    </Button>
                </div>

                <div className="flex items-center gap-2 rounded-lg border bg-muted/40 py-1 pr-1 pl-3">
                    <span className="text-xs font-medium text-muted-foreground">
                        Adresse
                    </span>
                    <code className="flex-1 truncate text-sm">{serverUrl}</code>
                    <CopyButton value={serverUrl} />
                </div>

                <Tabs defaultValue="prompts" className="space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <TabsList>
                            <TabsTrigger value="prompts">
                                <Terminal /> Commandes
                                <Badge variant="secondary">
                                    {shownPrompts.length}
                                </Badge>
                            </TabsTrigger>
                            <TabsTrigger value="tools">
                                <Wrench /> Outils
                                <Badge variant="secondary">
                                    {shownTools.length}
                                </Badge>
                            </TabsTrigger>
                        </TabsList>

                        <div className="relative w-full sm:w-64">
                            <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder="Rechercher…"
                                aria-label="Rechercher une commande"
                                className="pl-8"
                            />
                        </div>
                    </div>

                    <TabsContent value="prompts" className="space-y-3">
                        <details className="text-sm text-muted-foreground">
                            <summary className="cursor-pointer">
                                Comment lancer une commande ?
                            </summary>
                            <p className="mt-2">
                                Dans Claude Code ou Codex, tapez « / » puis son
                                nom (ex. /mcp__portfolio__draft-project). Dans
                                claude.ai ou ChatGPT, y compris sur mobile,
                                écrivez simplement la demande : chaque fiche
                                donne des phrases prêtes à copier.
                            </p>
                        </details>
                        <CommandGrid
                            commands={shownPrompts}
                            onShow={setShown}
                        />
                    </TabsContent>

                    <TabsContent value="tools" className="space-y-3">
                        <details className="text-sm text-muted-foreground">
                            <summary className="cursor-pointer">
                                Comment utiliser un outil ?
                            </summary>
                            <p className="mt-2">
                                Pas besoin de l'appeler : demandez ce que vous
                                voulez en langage naturel, l'agent choisit
                                l'outil. Chaque fiche donne un exemple de
                                demande.
                            </p>
                        </details>
                        <CommandGrid commands={shownTools} onShow={setShown} />
                    </TabsContent>
                </Tabs>
            </div>

            <Dialog
                open={shown !== null}
                onOpenChange={(open) => !open && setShown(null)}
            >
                {shown && (
                    <DialogContent className="max-h-[90dvh] w-[calc(100%-1rem)] overflow-y-auto p-4 sm:max-w-lg sm:p-6">
                        <DialogHeader>
                            <DialogTitle>{shown.title}</DialogTitle>
                            <div className="flex items-center gap-2">
                                <Badge variant="outline">
                                    {shown.kind === 'prompt'
                                        ? 'Commande'
                                        : 'Outil'}
                                </Badge>
                                <code className="text-xs text-muted-foreground">
                                    {shown.name}
                                </code>
                            </div>
                            <DialogDescription>{shown.usage}</DialogDescription>
                        </DialogHeader>

                        <div className="space-y-5 text-sm">
                            {shown.requests.length > 0 && (
                                <section className="space-y-1.5">
                                    <h3 className="font-medium">
                                        À écrire dans la conversation
                                    </h3>
                                    <p className="text-muted-foreground">
                                        Sans commande : dans claude.ai ou
                                        ChatGPT, sur ordinateur comme sur
                                        mobile, avec le connecteur activé.
                                    </p>
                                    <ul className="space-y-1.5">
                                        {shown.requests.map((request) => (
                                            <li
                                                key={request}
                                                className="flex items-start gap-1 rounded-md border bg-muted/40 py-1 pr-1 pl-3"
                                            >
                                                <p className="flex-1 py-1.5 break-words">
                                                    {request}
                                                </p>
                                                <CopyButton value={request} />
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {shown.kind === 'prompt' && shown.example && (
                                <section className="space-y-1.5">
                                    <h3 className="font-medium">
                                        Commande (Claude Code, Codex)
                                    </h3>
                                    <div className="flex items-start gap-1 rounded-md border bg-muted/40 py-1 pr-1 pl-3">
                                        <code className="min-w-0 flex-1 py-1.5 text-xs break-all whitespace-pre-wrap">
                                            {shown.example}
                                        </code>
                                        <CopyButton value={shown.example} />
                                    </div>
                                </section>
                            )}

                            {shown.tips.length > 0 && (
                                <section className="space-y-1.5">
                                    <h3 className="font-medium">
                                        Pour bien l'utiliser
                                    </h3>
                                    <ul className="list-disc space-y-1 pl-5 text-muted-foreground">
                                        {shown.tips.map((tip) => (
                                            <li key={tip}>{tip}</li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {shown.arguments.length > 0 && (
                                <section className="space-y-1.5">
                                    <h3 className="font-medium">Arguments</h3>
                                    <dl className="space-y-1.5">
                                        {shown.arguments.map((argument) => (
                                            <div key={argument.name}>
                                                <dt>
                                                    <code>{argument.name}</code>
                                                    {argument.required && (
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            · obligatoire
                                                        </span>
                                                    )}
                                                </dt>
                                                <dd className="text-muted-foreground">
                                                    {argument.description}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                </section>
                            )}

                            <details className="text-muted-foreground">
                                <summary className="cursor-pointer">
                                    Description transmise à l'agent
                                </summary>
                                <p className="mt-2">{shown.description}</p>
                            </details>
                        </div>
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

McpServerIndex.layout = {
    breadcrumbs: [{ title: 'Serveur MCP', href: pageIndex() }],
};
