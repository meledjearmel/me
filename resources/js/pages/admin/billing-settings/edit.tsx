import { Form, Head } from "@inertiajs/react";
import BillingSettingsController from "@/actions/App/Http/Controllers/Admin/BillingSettingsController";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { edit as pageEdit } from "@/routes/admin/billing-settings";
import type { BillingSettings } from "@/types";

type TextField = {
    name: keyof BillingSettings;
    label: string;
    description?: string;
    textarea?: boolean;
};

const ISSUER_FIELDS: TextField[] = [
    {
        name: "billing_business_name",
        label: "Nom ou raison sociale",
        description: "Vide : le nom du profil.",
    },
    {
        name: "billing_legal_form",
        label: "Statut",
        description: "Ex. « Entreprise individuelle », « Indépendant ».",
    },
    { name: "billing_address", label: "Adresse", textarea: true },
    {
        name: "billing_registration_number",
        label: "Numéro d’entreprise",
        description: "IDE, SIRET, RCCM…",
    },
    { name: "billing_iban", label: "IBAN" },
    { name: "billing_bank", label: "Banque" },
];

/** L'émetteur des devis et factures, la mention de TVA et les valeurs par défaut des documents. */
export default function BillingSettingsEdit({
    settings,
}: {
    settings: BillingSettings;
}) {
    return (
        <>
            <Head title="Réglages de facturation" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <Heading
                    title="Réglages de facturation"
                    description="Ce qui figure sur chaque devis, proforma et facture"
                />

                <Form
                    {...BillingSettingsController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            {ISSUER_FIELDS.map((field) => (
                                <Field
                                    key={field.name}
                                    data-invalid={!!errors[field.name]}
                                >
                                    <FieldLabel htmlFor={field.name}>
                                        {field.label}
                                    </FieldLabel>
                                    {field.textarea ? (
                                        <Textarea
                                            id={field.name}
                                            name={field.name}
                                            rows={3}
                                            defaultValue={String(
                                                settings[field.name] ?? "",
                                            )}
                                        />
                                    ) : (
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            defaultValue={String(
                                                settings[field.name] ?? "",
                                            )}
                                        />
                                    )}
                                    {field.description && (
                                        <FieldDescription>
                                            {field.description}
                                        </FieldDescription>
                                    )}
                                    <FieldError>
                                        {errors[field.name]}
                                    </FieldError>
                                </Field>
                            ))}

                            <Field data-invalid={!!errors.billing_vat_mention}>
                                <FieldLabel htmlFor="billing_vat_mention">
                                    Mention de TVA
                                </FieldLabel>
                                <Input
                                    id="billing_vat_mention"
                                    name="billing_vat_mention"
                                    placeholder="TVA non applicable"
                                    defaultValue={
                                        settings.billing_vat_mention ?? ""
                                    }
                                />
                                <FieldDescription>
                                    Vide : « TVA non applicable » (« VAT not
                                    applicable » en anglais). Indiquez la
                                    formule exigée dans votre pays, par exemple
                                    « TVA non applicable, art. 293 B du CGI » en
                                    France.
                                </FieldDescription>
                                <FieldError>
                                    {errors.billing_vat_mention}
                                </FieldError>
                            </Field>

                            <Field
                                data-invalid={!!errors.billing_payment_terms}
                            >
                                <FieldLabel htmlFor="billing_payment_terms">
                                    Conditions de paiement par défaut
                                </FieldLabel>
                                <Textarea
                                    id="billing_payment_terms"
                                    name="billing_payment_terms"
                                    rows={3}
                                    defaultValue={
                                        settings.billing_payment_terms ?? ""
                                    }
                                />
                                <FieldError>
                                    {errors.billing_payment_terms}
                                </FieldError>
                            </Field>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <Field
                                    data-invalid={
                                        !!errors.billing_quote_validity_days
                                    }
                                >
                                    <FieldLabel htmlFor="billing_quote_validity_days">
                                        Validité d’un devis (jours)
                                    </FieldLabel>
                                    <Input
                                        id="billing_quote_validity_days"
                                        name="billing_quote_validity_days"
                                        type="number"
                                        min={1}
                                        required
                                        defaultValue={
                                            settings.billing_quote_validity_days
                                        }
                                    />
                                    <FieldError>
                                        {errors.billing_quote_validity_days}
                                    </FieldError>
                                </Field>
                                <Field
                                    data-invalid={
                                        !!errors.billing_invoice_due_days
                                    }
                                >
                                    <FieldLabel htmlFor="billing_invoice_due_days">
                                        Échéance d’une facture (jours)
                                    </FieldLabel>
                                    <Input
                                        id="billing_invoice_due_days"
                                        name="billing_invoice_due_days"
                                        type="number"
                                        min={0}
                                        required
                                        defaultValue={
                                            settings.billing_invoice_due_days
                                        }
                                    />
                                    <FieldError>
                                        {errors.billing_invoice_due_days}
                                    </FieldError>
                                </Field>
                                <Field
                                    data-invalid={
                                        !!errors.billing_deposit_percent
                                    }
                                >
                                    <FieldLabel htmlFor="billing_deposit_percent">
                                        Acompte par défaut (%)
                                    </FieldLabel>
                                    <Input
                                        id="billing_deposit_percent"
                                        name="billing_deposit_percent"
                                        type="number"
                                        min={0}
                                        max={100}
                                        required
                                        defaultValue={
                                            settings.billing_deposit_percent
                                        }
                                    />
                                    <FieldError>
                                        {errors.billing_deposit_percent}
                                    </FieldError>
                                </Field>
                            </div>

                            <div className="space-y-2">
                                <p className="text-sm font-medium">Relances</p>
                                <p className="text-sm text-muted-foreground">
                                    Chaque matin, les devis sans réponse et les
                                    factures en retard sont signalés ; vous
                                    relancez avec un message relu, rien ne part
                                    tout seul.
                                </p>
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {(
                                        [
                                            [
                                                "billing_quote_reminder_days",
                                                "Devis sans réponse depuis (jours)",
                                                1,
                                            ],
                                            [
                                                "billing_invoice_reminder_days",
                                                "Facture en retard depuis (jours)",
                                                0,
                                            ],
                                            [
                                                "billing_max_reminders",
                                                "Relances au plus (0 : aucune)",
                                                0,
                                            ],
                                        ] as const
                                    ).map(([name, label, min]) => (
                                        <Field
                                            key={name}
                                            data-invalid={!!errors[name]}
                                        >
                                            <FieldLabel htmlFor={name}>
                                                {label}
                                            </FieldLabel>
                                            <Input
                                                id={name}
                                                name={name}
                                                type="number"
                                                min={min}
                                                required
                                                defaultValue={settings[name]}
                                            />
                                            <FieldError>
                                                {errors[name]}
                                            </FieldError>
                                        </Field>
                                    ))}
                                </div>
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

BillingSettingsEdit.layout = {
    breadcrumbs: [{ title: "Réglages de facturation", href: pageEdit() }],
};
