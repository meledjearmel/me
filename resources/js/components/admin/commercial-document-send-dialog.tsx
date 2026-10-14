import { Form } from "@inertiajs/react";
import { BellRing, Loader2, Send, Sparkles } from "lucide-react";
import { useState } from "react";
import CommercialDocumentController from "@/actions/App/Http/Controllers/Admin/CommercialDocumentController";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Field, FieldError, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { post } from "@/hooks/use-ai-text-assist";
import { DOCUMENT_TYPES } from "@/lib/commercial-documents";
import type { CommercialDocument } from "@/types";

/**
 * Envoi du document au client : objet et message à relire (proposés par l'IA sur demande),
 * PDF joint et, pour un devis, lien pour l'accepter en ligne. Un brouillon est émis à l'envoi.
 * En mode « relance », le message proposé relance un devis sans réponse ou une facture en retard.
 */
export default function CommercialDocumentSendDialog({
    document,
    reminder = false,
}: {
    document: CommercialDocument;
    reminder?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [subject, setSubject] = useState(
        `${reminder ? "Relance : " : ""}${DOCUMENT_TYPES[document.type]} ${document.number ?? ""} — ${document.title}`.replace(
            "  ",
            " ",
        ),
    );
    const [message, setMessage] = useState("");
    const [pending, setPending] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);

    async function suggest() {
        setPending(true);
        setAiError(null);

        try {
            const mail = await post<{ subject: string; message: string }>(
                CommercialDocumentController.suggestMail.url(document.id, {
                    query: reminder ? { reminder: 1 } : {},
                }),
                {},
            );
            setSubject(mail.subject);
            setMessage(mail.message);
        } catch {
            setAiError("Suggestion indisponible pour le moment, réessayez.");
        } finally {
            setPending(false);
        }
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant={
                        reminder
                            ? "default"
                            : document.sent_at
                              ? "outline"
                              : "default"
                    }
                    disabled={!document.client_email}
                >
                    {reminder ? (
                        <BellRing data-icon="inline-start" />
                    ) : (
                        <Send data-icon="inline-start" />
                    )}
                    {reminder
                        ? "Relancer"
                        : document.sent_at
                          ? "Renvoyer"
                          : "Envoyer au client"}
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {reminder ? "Relancer" : "Envoyer à"}{" "}
                        {document.client_email}
                    </DialogTitle>
                    <DialogDescription>
                        Le PDF est joint
                        {document.type === "quote" &&
                            ", avec un lien pour accepter le devis en ligne"}
                        .{" "}
                        {document.status === "draft" &&
                            "Le brouillon est émis à l’envoi : il reçoit son numéro et ne se modifie plus."}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(reminder
                        ? CommercialDocumentController.remind.form(document.id)
                        : CommercialDocumentController.send.form(document.id))}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => void suggest()}
                                disabled={pending}
                            >
                                {pending ? (
                                    <Loader2 className="animate-spin" />
                                ) : (
                                    <Sparkles />
                                )}
                                Proposer le message avec l'IA
                            </Button>
                            {aiError && (
                                <p className="text-sm text-destructive">
                                    {aiError}
                                </p>
                            )}

                            <Field data-invalid={!!errors.subject}>
                                <FieldLabel htmlFor="subject">Objet</FieldLabel>
                                <Input
                                    id="subject"
                                    name="subject"
                                    required
                                    value={subject}
                                    onChange={(event) =>
                                        setSubject(event.target.value)
                                    }
                                />
                                <FieldError>{errors.subject}</FieldError>
                            </Field>
                            <Field data-invalid={!!errors.message}>
                                <FieldLabel htmlFor="message">
                                    Message
                                </FieldLabel>
                                <Textarea
                                    id="message"
                                    name="message"
                                    required
                                    rows={9}
                                    value={message}
                                    onChange={(event) =>
                                        setMessage(event.target.value)
                                    }
                                />
                                <FieldError>
                                    {errors.message ??
                                        errors.client_email ??
                                        errors.lines}
                                </FieldError>
                            </Field>

                            <DialogFooter>
                                <Button
                                    disabled={
                                        processing || message.trim() === ""
                                    }
                                >
                                    {processing && (
                                        <Loader2 className="animate-spin" />
                                    )}
                                    Envoyer
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
