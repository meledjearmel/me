import type {
    CommercialDocumentLine,
    CommercialDocumentStatus,
    CommercialDocumentType,
} from "@/types";

export const DOCUMENT_TYPES: Record<CommercialDocumentType, string> = {
    quote: "Devis",
    proforma: "Facture proforma",
    invoice: "Facture",
};

export const DOCUMENT_STATUSES: Record<CommercialDocumentStatus, string> = {
    draft: "Brouillon",
    issued: "Émis",
    accepted: "Accepté",
    declined: "Refusé",
    paid: "Payé",
    cancelled: "Annulé",
};

export const LINE_UNITS: Record<CommercialDocumentLine["unit"], string> = {
    hour: "Heures",
    day: "Jours",
    flat: "Forfait",
};

const amount = new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 });

/** « 4 800 CHF » */
export function formatMoney(value: number, currency: string): string {
    return `${amount.format(value)} ${currency}`;
}

/** Total des lignes, arrondi à l'unité comme côté serveur. */
export function linesTotal(
    lines: Pick<CommercialDocumentLine, "quantity" | "unit_price">[],
): number {
    return Math.round(
        lines.reduce(
            (sum, line) =>
                sum +
                (Number(line.quantity) || 0) * (Number(line.unit_price) || 0),
            0,
        ),
    );
}
