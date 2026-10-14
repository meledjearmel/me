import { Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import FormSelect from "@/components/admin/form-select";
import { ESTIMATOR_CURRENCIES } from "@/components/admin/estimator-rate-inputs";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Textarea } from "@/components/ui/textarea";
import {
    formatMoney,
    LINE_UNITS,
    linesTotal,
} from "@/lib/commercial-documents";
import type {
    CommercialDocument,
    CommercialDocumentLine,
    CommercialDocumentType,
} from "@/types";

type Defaults = Pick<
    CommercialDocument,
    "deposit_percent" | "payment_terms" | "valid_until" | "due_at"
>;

const EMPTY_LINE: CommercialDocumentLine = {
    label: "",
    description: null,
    quantity: 1,
    unit: "flat",
    unit_price: 0,
};

/**
 * Champs d'un devis, d'une proforma ou d'une facture en brouillon : le client, le projet,
 * les lignes (avec le total recalculé en direct), l'acompte, les conditions et les dates.
 */
export default function CommercialDocumentFields({
    type,
    document,
    defaults,
    errors,
}: {
    type: CommercialDocumentType;
    document?: CommercialDocument;
    defaults?: Defaults;
    errors: Record<string, string | undefined>;
}) {
    const [lines, setLines] = useState<CommercialDocumentLine[]>(
        document?.lines ?? [EMPTY_LINE],
    );
    const [currency, setCurrency] = useState<string>(
        document?.currency ?? "CHF",
    );
    const values = document ?? defaults;
    const isQuote = type === "quote";

    const updateLine = (
        index: number,
        changes: Partial<CommercialDocumentLine>,
    ) =>
        setLines((current) =>
            current.map((line, position) =>
                position === index ? { ...line, ...changes } : line,
            ),
        );

    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.client_name}>
                    <FieldLabel htmlFor="client_name">
                        Nom du client *
                    </FieldLabel>
                    <Input
                        id="client_name"
                        name="client_name"
                        required
                        defaultValue={document?.client_name}
                    />
                    <FieldError>{errors.client_name}</FieldError>
                </Field>
                <Field data-invalid={!!errors.client_company}>
                    <FieldLabel htmlFor="client_company">Société</FieldLabel>
                    <Input
                        id="client_company"
                        name="client_company"
                        defaultValue={document?.client_company ?? ""}
                    />
                    <FieldError>{errors.client_company}</FieldError>
                </Field>
                <Field data-invalid={!!errors.client_email}>
                    <FieldLabel htmlFor="client_email">Email</FieldLabel>
                    <Input
                        id="client_email"
                        name="client_email"
                        type="email"
                        defaultValue={document?.client_email ?? ""}
                    />
                    <FieldDescription>
                        Nécessaire pour envoyer le document.
                    </FieldDescription>
                    <FieldError>{errors.client_email}</FieldError>
                </Field>
                <Field data-invalid={!!errors.locale}>
                    <FieldLabel htmlFor="locale">Langue du document</FieldLabel>
                    <FormSelect
                        id="locale"
                        name="locale"
                        defaultValue={document?.locale ?? "fr"}
                    >
                        <option value="fr">Français</option>
                        <option value="en">Anglais</option>
                    </FormSelect>
                    <FieldError>{errors.locale}</FieldError>
                </Field>
            </div>

            <Field data-invalid={!!errors.client_address}>
                <FieldLabel htmlFor="client_address">
                    Adresse du client
                </FieldLabel>
                <Textarea
                    id="client_address"
                    name="client_address"
                    rows={2}
                    defaultValue={document?.client_address ?? ""}
                />
                <FieldError>{errors.client_address}</FieldError>
            </Field>

            <div className="grid gap-4 sm:grid-cols-[1fr_10rem]">
                <Field data-invalid={!!errors.title}>
                    <FieldLabel htmlFor="title">Projet *</FieldLabel>
                    <Input
                        id="title"
                        name="title"
                        required
                        defaultValue={document?.title}
                    />
                    <FieldError>{errors.title}</FieldError>
                </Field>
                <Field data-invalid={!!errors.currency}>
                    <FieldLabel htmlFor="currency">Devise *</FieldLabel>
                    <FormSelect
                        id="currency"
                        name="currency"
                        required
                        defaultValue={currency}
                        onValueChange={setCurrency}
                    >
                        {ESTIMATOR_CURRENCIES.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.currency}</FieldError>
                </Field>
            </div>

            <div className="space-y-3">
                <p className="text-sm font-medium">Lignes *</p>
                {lines.map((line, index) => (
                    <div
                        key={index}
                        className="space-y-3 rounded-lg border p-3"
                    >
                        <div className="flex items-start gap-2">
                            <Input
                                name={`lines[${index}][label]`}
                                value={line.label}
                                onChange={(event) =>
                                    updateLine(index, {
                                        label: event.target.value,
                                    })
                                }
                                placeholder="Désignation"
                                aria-label="Désignation"
                                required
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Retirer la ligne"
                                disabled={lines.length === 1}
                                onClick={() =>
                                    setLines((current) =>
                                        current.filter(
                                            (_, position) => position !== index,
                                        ),
                                    )
                                }
                            >
                                <Trash2 />
                            </Button>
                        </div>
                        <Textarea
                            name={`lines[${index}][description]`}
                            value={line.description ?? ""}
                            onChange={(event) =>
                                updateLine(index, {
                                    description: event.target.value,
                                })
                            }
                            placeholder="Détail (facultatif)"
                            aria-label="Détail"
                            rows={1}
                        />
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <Input
                                name={`lines[${index}][quantity]`}
                                type="number"
                                min={0}
                                step="0.5"
                                value={line.quantity}
                                onChange={(event) =>
                                    updateLine(index, {
                                        quantity: Number(event.target.value),
                                    })
                                }
                                aria-label="Quantité"
                            />
                            <NativeSelect
                                name={`lines[${index}][unit]`}
                                value={line.unit}
                                onChange={(event) =>
                                    updateLine(index, {
                                        unit: event.target
                                            .value as CommercialDocumentLine["unit"],
                                    })
                                }
                                aria-label="Unité"
                            >
                                {Object.entries(LINE_UNITS).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </NativeSelect>
                            <Input
                                name={`lines[${index}][unit_price]`}
                                type="number"
                                min={0}
                                value={line.unit_price}
                                onChange={(event) =>
                                    updateLine(index, {
                                        unit_price: Number(event.target.value),
                                    })
                                }
                                aria-label="Prix unitaire"
                            />
                            <p className="self-center text-right text-sm font-medium tabular-nums">
                                {formatMoney(linesTotal([line]), currency)}
                            </p>
                        </div>
                        <FieldError>
                            {errors[`lines.${index}.label`] ??
                                errors[`lines.${index}.quantity`] ??
                                errors[`lines.${index}.unit_price`]}
                        </FieldError>
                    </div>
                ))}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            setLines((current) => [...current, EMPTY_LINE])
                        }
                    >
                        <Plus data-icon="inline-start" /> Ajouter une ligne
                    </Button>
                    <p className="text-base font-semibold tabular-nums">
                        Total : {formatMoney(linesTotal(lines), currency)}
                    </p>
                </div>
                <FieldError>{errors.lines}</FieldError>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.deposit_percent}>
                    <FieldLabel htmlFor="deposit_percent">
                        Acompte (%)
                    </FieldLabel>
                    <Input
                        id="deposit_percent"
                        name="deposit_percent"
                        type="number"
                        min={0}
                        max={100}
                        required
                        defaultValue={values?.deposit_percent ?? 0}
                    />
                    <FieldError>{errors.deposit_percent}</FieldError>
                </Field>
                {isQuote ? (
                    <Field data-invalid={!!errors.valid_until}>
                        <FieldLabel htmlFor="valid_until">
                            Valable jusqu'au
                        </FieldLabel>
                        <Input
                            id="valid_until"
                            name="valid_until"
                            type="date"
                            defaultValue={
                                values?.valid_until?.slice(0, 10) ?? ""
                            }
                        />
                        <FieldError>{errors.valid_until}</FieldError>
                    </Field>
                ) : (
                    <Field data-invalid={!!errors.due_at}>
                        <FieldLabel htmlFor="due_at">
                            Échéance de paiement
                        </FieldLabel>
                        <Input
                            id="due_at"
                            name="due_at"
                            type="date"
                            defaultValue={values?.due_at?.slice(0, 10) ?? ""}
                        />
                        <FieldError>{errors.due_at}</FieldError>
                    </Field>
                )}
            </div>

            <Field data-invalid={!!errors.payment_terms}>
                <FieldLabel htmlFor="payment_terms">
                    Conditions de paiement
                </FieldLabel>
                <Textarea
                    id="payment_terms"
                    name="payment_terms"
                    rows={3}
                    defaultValue={values?.payment_terms ?? ""}
                />
                <FieldError>{errors.payment_terms}</FieldError>
            </Field>

            <Field data-invalid={!!errors.notes}>
                <FieldLabel htmlFor="notes">Remarques</FieldLabel>
                <Textarea
                    id="notes"
                    name="notes"
                    rows={3}
                    defaultValue={document?.notes ?? ""}
                />
                <FieldDescription>
                    Ce qui est compris ou non, le planning, les livrables…
                </FieldDescription>
                <FieldError>{errors.notes}</FieldError>
            </Field>
        </>
    );
}
