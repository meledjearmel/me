import { Head, router } from '@inertiajs/react';
import { Loader2, Send } from 'lucide-react';
import { useState } from 'react';
import WeeklyReportController from '@/actions/App/Http/Controllers/Admin/WeeklyReportController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { show as pageShow } from '@/routes/admin/weekly-report';

/**
 * Le rapport de la semaine : envoyé chaque lundi matin à l'email du profil. On l'active ou
 * le coupe, on voit le mail tel qu'il partira, et on peut se l'envoyer tout de suite.
 */
export default function WeeklyReportShow({
    enabled,
    sentAt,
    recipient,
}: {
    enabled: boolean;
    sentAt: string | null;
    recipient: string | null;
}) {
    const [sending, setSending] = useState(false);

    const toggle = (value: boolean) =>
        router.put(
            WeeklyReportController.update.url(),
            { weekly_report_enabled: value },
            { preserveScroll: true },
        );

    const send = () =>
        router.post(
            WeeklyReportController.send.url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
            },
        );

    return (
        <>
            <Head title="Rapport de la semaine" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title="Rapport de la semaine"
                        description={`Chaque lundi à 7 h, le bilan des 7 derniers jours ${recipient ? `à ${recipient}` : '(ajoutez un email au profil)'}`}
                    />
                    <Button
                        variant="outline"
                        onClick={send}
                        disabled={sending || !recipient}
                    >
                        {sending ? (
                            <Loader2 className="animate-spin" />
                        ) : (
                            <Send />
                        )}
                        M'envoyer le rapport maintenant
                    </Button>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4">
                    <div className="flex items-center gap-3">
                        <Switch
                            id="weekly_report_enabled"
                            checked={enabled}
                            onCheckedChange={toggle}
                        />
                        <Label htmlFor="weekly_report_enabled">
                            {enabled
                                ? 'Envoi automatique activé'
                                : 'Envoi automatique coupé'}
                        </Label>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {sentAt
                            ? `Dernier envoi le ${new Date(sentAt).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' })}`
                            : 'Pas encore envoyé'}
                        . Une semaine sans activité n'envoie rien.
                    </p>
                </div>

                <div className="space-y-2">
                    <p className="text-sm font-medium">Aperçu</p>
                    <p className="text-sm text-muted-foreground">
                        Les chiffres d'aujourd'hui, tels qu'ils partiraient. « À
                        retenir », rédigé par l'IA, s'ajoute en tête au moment
                        de l'envoi.
                    </p>
                    <iframe
                        title="Aperçu du rapport"
                        src={WeeklyReportController.preview.url()}
                        className="h-[900px] w-full rounded-lg border bg-white"
                    />
                </div>
            </div>
        </>
    );
}

WeeklyReportShow.layout = {
    breadcrumbs: [{ title: 'Rapport de la semaine', href: pageShow() }],
};
