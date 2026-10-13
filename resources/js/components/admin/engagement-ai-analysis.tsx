import { router } from '@inertiajs/react';
import { Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';
import EngagementAnalysisController from '@/actions/App/Http/Controllers/Admin/EngagementAnalysisController';
import { formatDate } from '@/components/admin/show-page';
import { Button } from '@/components/ui/button';
import type { Engagement } from '@/types';

const amount = (value: number, currency: string | null) =>
    `${value.toLocaleString('fr-FR')} ${currency === 'XOF' ? 'FCFA' : (currency ?? '')}`.trim();

function List({ title, items }: { title: string; items: string[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div>
            <p className="text-sm font-medium">{title}</p>
            <ul className="mt-1 list-disc space-y-1 pl-5 text-sm">
                {items.map((item) => (
                    <li key={item}>{item}</li>
                ))}
            </ul>
        </div>
    );
}

/**
 * L'analyse de l'IA d'une demande de l'estimateur : faite d'office à la réception,
 * relançable ici. Elle aide à chiffrer, rien n'est envoyé au client.
 */
export default function EngagementAiAnalysis({
    engagement,
}: {
    engagement: Engagement;
}) {
    const [pending, setPending] = useState(false);
    const analysis = engagement.ai_analysis;

    const analyze = () =>
        router.post(
            EngagementAnalysisController.store.url(engagement.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setPending(true),
                onFinish: () => setPending(false),
            },
        );

    return (
        <div className="space-y-4">
            {analysis ? (
                <>
                    <p className="text-sm">{analysis.summary}</p>

                    <div className="rounded-md bg-muted p-3 text-sm">
                        <strong>
                            {analysis.hours_min} – {analysis.hours_max} h
                        </strong>{' '}
                        réalistes, marge comprise
                        {analysis.price_min !== null &&
                            analysis.price_max !== null && (
                                <>
                                    {' '}
                                    · soit{' '}
                                    {amount(
                                        analysis.price_min,
                                        analysis.currency,
                                    )}{' '}
                                    –{' '}
                                    {amount(
                                        analysis.price_max,
                                        analysis.currency,
                                    )}
                                </>
                            )}{' '}
                        ·{' '}
                        {analysis.billing === 'hourly'
                            ? "facturation à l'heure recommandée"
                            : 'forfait envisageable'}
                        {analysis.reasoning && (
                            <p className="mt-2 text-muted-foreground">
                                {analysis.reasoning}
                            </p>
                        )}
                    </div>

                    <List title="Risques" items={analysis.risks} />
                    <List
                        title="Points flous"
                        items={analysis.unclear_points}
                    />
                    <List
                        title="Questions à poser au client"
                        items={analysis.questions}
                    />

                    <p className="text-xs text-muted-foreground">
                        Analysée le {formatDate(engagement.ai_analyzed_at)}. À
                        vérifier : l'IA peut se tromper.
                    </p>
                </>
            ) : (
                <p className="text-sm text-muted-foreground">
                    Pas encore d'analyse (elle se fait à la réception de la
                    demande, si un fournisseur d'IA répond).
                </p>
            )}

            <Button
                type="button"
                variant="outline"
                onClick={analyze}
                disabled={pending}
            >
                {pending ? <Loader2 className="animate-spin" /> : <Sparkles />}
                {analysis ? "Relancer l'analyse" : "Analyser avec l'IA"}
            </Button>
        </div>
    );
}
