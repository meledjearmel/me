import { Head, router } from "@inertiajs/react";
import { useState } from "react";
import CommercialDocumentController from "@/actions/App/Http/Controllers/CommercialDocumentController";
import PublicShell from "@/components/public/public-shell";
import { useLocale } from "@/lib/i18n";

type PublicDocument = {
    type: "quote" | "proforma" | "invoice";
    type_label: string;
    number: string;
    status: "issued" | "accepted" | "declined" | "paid";
    title: string;
    client_name: string;
    currency: string;
    total: number;
    deposit_percent: number;
    deposit_amount: number;
    lines: {
        label: string;
        description: string | null;
        quantity: number;
        unit: string;
        unit_price: number;
    }[];
    issued_at: string | null;
    valid_until: string | null;
    due_at: string | null;
    can_answer: boolean;
};

const COPY = {
    fr: {
        hello: (name: string) => `Bonjour ${name},`,
        intro: "Voici le détail du document. Vous pouvez télécharger le PDF et, pour un devis, me donner votre réponse ici.",
        total: "Total",
        deposit: (percent: number) => `Acompte (${percent} %)`,
        validUntil: "Valable jusqu’au",
        dueAt: "À régler avant le",
        download: "Télécharger le PDF",
        accept: "J’accepte le devis",
        decline: "Je décline",
        confirmAccept: "Confirmer l’acceptation de ce devis ?",
        accepted:
            "Devis accepté, merci ! Je reviens vers vous très vite pour la suite.",
        declined: "Vous avez décliné ce devis. Merci pour votre réponse.",
        expired:
            "Ce devis n’est plus valable : contactez-moi pour en recevoir un à jour.",
        paid: "Ce document est réglé, merci.",
        units: { hour: "h", day: "j", flat: "forfait", month: "mois", quarter: "trimestre", semester: "semestre", year: "an" } as Record<
            string,
            string
        >,
    },
    en: {
        hello: (name: string) => `Hello ${name},`,
        intro: "Here are the details of the document. You can download the PDF and, for a quote, give me your answer here.",
        total: "Total",
        deposit: (percent: number) => `Deposit (${percent}%)`,
        validUntil: "Valid until",
        dueAt: "Due by",
        download: "Download the PDF",
        accept: "I accept the quote",
        decline: "I decline",
        confirmAccept: "Confirm that you accept this quote?",
        accepted:
            "Quote accepted, thank you! I will get back to you very soon about next steps.",
        declined: "You declined this quote. Thank you for your answer.",
        expired:
            "This quote has expired: contact me to receive an updated one.",
        paid: "This document has been paid, thank you.",
        units: { hour: "h", day: "d", flat: "flat", month: "month", quarter: "quarter", semester: "half-year", year: "year" } as Record<string, string>,
    },
};

/** Le lien privé d'un devis ou d'une facture : détail, PDF et, pour un devis, la réponse du client. */
export default function PublicCommercialDocument({
    document,
    token,
}: {
    document: PublicDocument;
    token: string;
}) {
    const locale = useLocale();
    const copy = COPY[locale];
    const [processing, setProcessing] = useState(false);
    const money = (value: number) =>
        `${new Intl.NumberFormat(locale === "en" ? "en-GB" : "fr-FR", { maximumFractionDigits: 0 }).format(value)} ${document.currency}`;
    const date = (value: string) =>
        new Intl.DateTimeFormat(locale === "en" ? "en-GB" : "fr-FR", {
            dateStyle: "long",
        }).format(new Date(value));

    const answer = (action: "accept" | "decline") => {
        if (action === "accept" && !window.confirm(copy.confirmAccept)) {
            return;
        }

        router.post(
            CommercialDocumentController[action].url({ locale, token }),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const isQuote = document.type === "quote";
    const status =
        document.status === "accepted"
            ? copy.accepted
            : document.status === "declined"
              ? copy.declined
              : document.status === "paid"
                ? copy.paid
                : isQuote && !document.can_answer
                  ? copy.expired
                  : null;

    return (
        <>
            <Head title={`${document.type_label} ${document.number}`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <PublicShell>
                <div className="pub-application site-wrap">
                    <header className="pub-application__head">
                        <p className="pub-application__for">
                            {document.type_label} · {document.number}
                        </p>
                        <h1 className="pub-application__position">
                            {document.title}
                        </h1>
                    </header>

                    <div className="pub-application__layout">
                        <article className="pub-application__body">
                            <p className="pub-application__greeting">
                                {copy.hello(document.client_name)}
                            </p>
                            <p className="pub-application__paragraph">
                                {copy.intro}
                            </p>

                            <table className="w-full border-collapse text-left">
                                <tbody>
                                    {document.lines.map((line, index) => (
                                        <tr
                                            key={index}
                                            className="border-b border-current/15 align-top"
                                        >
                                            <td className="py-3 pr-4">
                                                {line.label}
                                                {line.description && (
                                                    <span className="block text-sm opacity-70">
                                                        {line.description}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 pr-4 text-right whitespace-nowrap opacity-70">
                                                {line.quantity}{" "}
                                                {copy.units[line.unit] ?? ""}
                                            </td>
                                            <td className="py-3 text-right whitespace-nowrap tabular-nums">
                                                {money(
                                                    Math.round(
                                                        line.quantity *
                                                            line.unit_price,
                                                    ),
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </article>

                        <aside
                            className="pub-application__card"
                            aria-label={document.type_label}
                        >
                            <p className="pub-application__label">
                                {copy.total}
                            </p>
                            <p className="pub-application__card-title">
                                {money(document.total)}
                            </p>
                            <p className="pub-application__card-company">
                                {document.deposit_percent > 0 &&
                                    `${copy.deposit(document.deposit_percent)} : ${money(document.deposit_amount)}`}
                                {document.deposit_percent > 0 && <br />}
                                {isQuote &&
                                    document.valid_until &&
                                    `${copy.validUntil} ${date(document.valid_until)}`}
                                {!isQuote &&
                                    document.due_at &&
                                    `${copy.dueAt} ${date(document.due_at)}`}
                            </p>

                            <div className="pub-application__actions">
                                {document.can_answer && (
                                    <>
                                        <button
                                            type="button"
                                            className="pub-drawer__send"
                                            disabled={processing}
                                            onClick={() => answer("accept")}
                                        >
                                            {copy.accept}
                                        </button>
                                        <button
                                            type="button"
                                            className="pub-application__secondary"
                                            disabled={processing}
                                            onClick={() => answer("decline")}
                                        >
                                            {copy.decline}
                                        </button>
                                    </>
                                )}
                                {status && <p role="status">{status}</p>}
                                <a
                                    href={CommercialDocumentController.pdf.url({
                                        locale,
                                        token,
                                    })}
                                    className="pub-application__secondary"
                                >
                                    {copy.download}{" "}
                                    <span aria-hidden="true">↓</span>
                                </a>
                            </div>
                        </aside>
                    </div>
                </div>
            </PublicShell>
        </>
    );
}
