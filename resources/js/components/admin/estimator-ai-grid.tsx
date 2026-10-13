import { router } from '@inertiajs/react';
import { Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';
import AiAssistController from '@/actions/App/Http/Controllers/Admin/AiAssistController';
import EstimatorConfigurationController from '@/actions/App/Http/Controllers/Admin/EstimatorConfigurationController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { post } from '@/hooks/use-ai-text-assist';
import type { EstimatorProposal, EstimatorProposalItem } from '@/types';

const describe = (item: EstimatorProposalItem): string =>
    item.pricing === 'percent'
        ? `+${item.percent} % des heures du projet`
        : `${item.hours_min ?? 0} – ${item.hours_max ?? 0} h`;

/**
 * « Configurer avec l'IA » : le développeur décrit son activité, l'IA propose toute la
 * grille (types, options en heures ou en %, options réservées à certains types, tarifs si
 * demandés). Rien n'est enregistré avant « Appliquer », qui crée ou met à jour d'un bloc.
 */
export default function EstimatorAiGrid() {
    const [open, setOpen] = useState(false);
    const [brief, setBrief] = useState('');
    const [pending, setPending] = useState(false);
    const [applying, setApplying] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [proposal, setProposal] = useState<EstimatorProposal | null>(null);

    const projectTypes =
        proposal?.items.filter((item) => item.kind === 'project_type') ?? [];
    const features =
        proposal?.items.filter((item) => item.kind === 'feature') ?? [];
    const nameOf = (target: string | number) =>
        projectTypes.find((item) => item.ref === target || item.id === target)
            ?.name_fr ?? `#${target}`;

    async function propose() {
        setPending(true);
        setError(null);

        try {
            const response = await post<{ proposal: EstimatorProposal }>(
                AiAssistController.proposeEstimatorGrid.url(),
                { brief },
            );

            setProposal(response.proposal);
        } catch {
            setError('Proposition indisponible pour le moment, réessayez.');
        } finally {
            setPending(false);
        }
    }

    function apply() {
        if (!proposal) {
            return;
        }

        router.post(
            EstimatorConfigurationController.store.url(),
            { settings: proposal.settings, items: proposal.items },
            {
                preserveScroll: true,
                onStart: () => setApplying(true),
                onFinish: () => setApplying(false),
                onSuccess: () => {
                    setOpen(false);
                    setProposal(null);
                },
                onError: () =>
                    setError(
                        "La proposition n'a pas pu être appliquée : rien n'a été enregistré.",
                    ),
            },
        );
    }

    return (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(true)}
            >
                <Sparkles /> Configurer avec l'IA
            </Button>

            <Dialog
                open={open}
                onOpenChange={(value) =>
                    !pending && !applying && setOpen(value)
                }
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            Configurer l'estimateur avec l'IA
                        </DialogTitle>
                        <DialogDescription>
                            Décrivez ce que vous faites, pour qui, et vos tarifs
                            si vous voulez les changer. L'IA propose toute la
                            grille ; vous relisez avant d'appliquer.
                        </DialogDescription>
                    </DialogHeader>

                    <Textarea
                        value={brief}
                        onChange={(event) => setBrief(event.target.value)}
                        rows={5}
                        placeholder="Ex. : je fais des sites vitrines, des boutiques en ligne et des applications mobiles Flutter pour des PME en Suisse et en Côte d'Ivoire. 100 CHF de l'heure, 30 000 FCFA, 20 % de marge."
                        aria-label="Votre activité et vos consignes"
                    />

                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => void propose()}
                            disabled={pending || brief.trim().length < 20}
                        >
                            {pending ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Sparkles />
                            )}
                            {proposal
                                ? 'Proposer à nouveau'
                                : 'Proposer une grille'}
                        </Button>
                        {error && (
                            <span className="text-sm text-destructive">
                                {error}
                            </span>
                        )}
                    </div>

                    {proposal && (
                        <div className="space-y-4 text-sm">
                            {proposal.reasoning && (
                                <p className="text-muted-foreground">
                                    {proposal.reasoning}
                                </p>
                            )}

                            {proposal.settings && (
                                <div className="rounded-md bg-muted p-3">
                                    <p className="font-medium">Tarifs</p>
                                    <p>
                                        {Object.entries(
                                            proposal.settings.hourly_rates ??
                                                {},
                                        )
                                            .map(
                                                ([currency, rate]) =>
                                                    `${rate} ${currency === 'XOF' ? 'FCFA' : currency}/h`,
                                            )
                                            .join(' · ')}
                                        {proposal.settings.risk_percent !==
                                            undefined &&
                                            ` · marge ${proposal.settings.risk_percent} %`}
                                        {proposal.settings.hours_per_week !==
                                            undefined &&
                                            ` · ${proposal.settings.hours_per_week} h/semaine`}
                                    </p>
                                </div>
                            )}

                            <div>
                                <p className="font-medium">
                                    Types de projet ({projectTypes.length})
                                </p>
                                <ul className="mt-1 space-y-1">
                                    {projectTypes.map((item) => (
                                        <li key={item.ref ?? item.id}>
                                            <strong>{item.name_fr}</strong> ·{' '}
                                            {describe(item)} ·{' '}
                                            {item.billing === 'hourly'
                                                ? "à l'heure"
                                                : 'forfait'}
                                            {item.id ? ' (mise à jour)' : ''}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div>
                                <p className="font-medium">
                                    Options ({features.length})
                                </p>
                                <ul className="mt-1 space-y-1">
                                    {features.map((item) => (
                                        <li key={item.ref ?? item.id}>
                                            <strong>{item.name_fr}</strong> ·{' '}
                                            {describe(item)} ·{' '}
                                            {item.project_types?.length
                                                ? `pour ${item.project_types.map(nameOf).join(', ')}`
                                                : 'pour tous les types'}
                                            {item.id ? ' (mise à jour)' : ''}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={apply}
                            disabled={!proposal || applying}
                        >
                            {applying && <Loader2 className="animate-spin" />}
                            Appliquer la proposition
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
