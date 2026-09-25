import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import EngagementController from '@/actions/App/Http/Controllers/EngagementController';
import PubDialog, { DialogDone } from '@/components/public/pub-dialog';
import { useLocale, useTranslations } from '@/lib/i18n';

type Tab = 'freelance' | 'hiring';

const CURRENCIES = [
    { value: 'CHF', label: 'CHF' },
    { value: 'EUR', label: 'EUR' },
    { value: 'USD', label: 'USD' },
    { value: 'XOF', label: 'FCFA' },
] as const;

/**
 * Budget indicatif : soit un montant fixe (forfait), soit un tarif par période
 * (heure, jour, semaine, mois ou année). Facultatif : sans montant, rien n'est envoyé.
 */
function BudgetField({ errors }: { errors: Record<string, string> }) {
    const t = useTranslations();
    const [type, setType] = useState<'fixed' | 'period'>('fixed');
    const periods = [
        { value: 'hour', label: t.fab.periodHour },
        { value: 'day', label: t.fab.periodDay },
        { value: 'week', label: t.fab.periodWeek },
        { value: 'month', label: t.fab.periodMonth },
        { value: 'year', label: t.fab.periodYear },
    ];

    return (
        <fieldset className="pub-drawer__field pub-drawer__field--wide pub-budget">
            <legend>
                {t.fab.budget} ({t.fab.optional})
            </legend>

            <input type="hidden" name="budget_type" value={type} />

            <div className="pub-budget__row">
                <div className="pub-budget__type" role="group" aria-label={t.fab.budget}>
                    {(['fixed', 'period'] as const).map((option) => (
                        <button
                            key={option}
                            type="button"
                            aria-pressed={type === option}
                            onClick={() => setType(option)}
                        >
                            {option === 'fixed' ? t.fab.budgetFixed : t.fab.budgetPeriod}
                        </button>
                    ))}
                </div>

                <input
                    name="budget_amount"
                    type="number"
                    inputMode="numeric"
                    min={0}
                    step={1}
                    placeholder={t.fab.budgetAmount}
                    aria-label={t.fab.budgetAmount}
                />

                <select name="budget_currency" defaultValue="CHF" aria-label="Devise">
                    {CURRENCIES.map((currency) => (
                        <option key={currency.value} value={currency.value}>
                            {currency.label}
                        </option>
                    ))}
                </select>

                {type === 'period' && (
                    <select name="budget_period" defaultValue="hour" aria-label={t.fab.budgetPeriod}>
                        {periods.map((period) => (
                            <option key={period.value} value={period.value}>
                                / {period.label.toLowerCase()}
                            </option>
                        ))}
                    </select>
                )}
            </div>

            {(errors.budget_amount || errors.budget_currency || errors.budget_period) && (
                <small>{errors.budget_amount ?? errors.budget_currency ?? errors.budget_period}</small>
            )}
        </fieldset>
    );
}

/** Champs communs aux deux onglets : nom, email, entreprise. */
function Identity({
    errors,
    companyRequired,
    companyOwnLine = false,
}: {
    errors: Record<string, string>;
    companyRequired: boolean;
    /** L'entreprise occupe toute une ligne (onglet Freelance) au lieu de partager la sienne. */
    companyOwnLine?: boolean;
}) {
    const t = useTranslations();

    return (
        <>
            <label className="pub-drawer__field">
                <span>{t.fab.yourName}</span>
                <input name="name" autoComplete="name" required />
                {errors.name && <small>{errors.name}</small>}
            </label>

            <label className="pub-drawer__field">
                <span>{t.fab.yourEmail}</span>
                <input type="email" name="email" autoComplete="email" required />
                {errors.email && <small>{errors.email}</small>}
            </label>

            <label className={`pub-drawer__field${companyOwnLine ? ' pub-drawer__field--wide' : ''}`}>
                <span>
                    {t.fab.company}
                    {companyRequired ? '' : ` (${t.fab.optional})`}
                </span>
                <input name="company" autoComplete="organization" required={companyRequired} />
                {errors.company && <small>{errors.company}</small>}
            </label>
        </>
    );
}

/**
 * « Collaborer » : deux onglets. Freelance recueille ce qu'il faut pour cadrer un
 * projet. Embauche demande le poste visé et envoie par email mon CV adapté au
 * profil choisi. Les deux formulaires restent montés pour garder ce qui est saisi
 * quand on change d'onglet.
 */
export default function EngageDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { props } = usePage<{ cvProfiles: { id: number; label: string }[] }>();
    const [tab, setTab] = useState<Tab>('freelance');
    const [done, setDone] = useState<Tab | null>(null);

    const change = (next: boolean) => {
        onOpenChange(next);

        if (!next) {
            window.setTimeout(() => setDone(null), 300);
        }
    };

    return (
        <PubDialog
            open={open}
            onOpenChange={change}
            title={t.fab.engageTitle}
            description={t.fab.engageIntro}
        >
            {done ? (
                <DialogDone
                    title={t.fab.reviewDoneTitle}
                    text={done === 'hiring' ? t.fab.hiringDone : t.fab.freelanceDone}
                    onClose={() => change(false)}
                    closeLabel={t.skills.close}
                />
            ) : (
                <>
                    <div className="pub-tabs" role="tablist" aria-label={t.fab.engageTitle}>
                        {(['freelance', 'hiring'] as const).map((name) => (
                            <button
                                key={name}
                                type="button"
                                role="tab"
                                id={`pub-tab-${name}`}
                                aria-selected={tab === name}
                                aria-controls={`pub-panel-${name}`}
                                onClick={() => setTab(name)}
                            >
                                {name === 'freelance' ? t.fab.tabFreelance : t.fab.tabHiring}
                            </button>
                        ))}
                    </div>

                    <div
                        id="pub-panel-freelance"
                        role="tabpanel"
                        aria-labelledby="pub-tab-freelance"
                        hidden={tab !== 'freelance'}
                    >
                        <Form
                            {...EngagementController.store.form(locale)}
                            className="pub-dialog__form"
                            resetOnSuccess
                            onSuccess={() => setDone('freelance')}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input type="hidden" name="type" value="freelance" />
                                    <input type="text" name="website" tabIndex={-1} autoComplete="off" hidden aria-hidden="true" />

                                    <div className="pub-drawer__fields">
                                        <Identity errors={errors} companyRequired={false} companyOwnLine />

                                        <label className="pub-drawer__field">
                                            <span>{t.fab.projectType}</span>
                                            <select name="subject" required defaultValue="">
                                                <option value="" disabled>
                                                    {t.fab.choose}
                                                </option>
                                                {[t.fab.projWeb, t.fab.projApp, t.fab.projMobile, t.fab.projInfra, t.fab.projOther].map(
                                                    (option) => (
                                                        <option key={option} value={option}>
                                                            {option}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            {errors.subject && <small>{errors.subject}</small>}
                                        </label>

                                        <label className="pub-drawer__field">
                                            <span>
                                                {t.fab.timeline} ({t.fab.optional})
                                            </span>
                                            <select name="timeline" defaultValue="">
                                                <option value="">{t.fab.choose}</option>
                                                <option value="urgent">{t.fab.timeUrgent}</option>
                                                <option value="quarter">{t.fab.timeQuarter}</option>
                                                <option value="semester">{t.fab.timeSemester}</option>
                                                <option value="flexible">{t.fab.timeFlexible}</option>
                                            </select>
                                        </label>

                                        <BudgetField errors={errors} />


                                        <label className="pub-drawer__field pub-drawer__field--wide">
                                            <span>{t.fab.projectMessage}</span>
                                            <textarea name="message" rows={3} required />
                                            {errors.message && <small>{errors.message}</small>}
                                        </label>
                                    </div>

                                    <button type="submit" className="pub-drawer__send" disabled={processing}>
                                        {processing ? t.contact.sending : t.fab.freelanceSend}
                                        <span aria-hidden="true">→</span>
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>

                    <div
                        id="pub-panel-hiring"
                        role="tabpanel"
                        aria-labelledby="pub-tab-hiring"
                        hidden={tab !== 'hiring'}
                    >
                        <p className="pub-dialog__note">{t.fab.hiringIntro}</p>

                        <Form
                            {...EngagementController.store.form(locale)}
                            className="pub-dialog__form"
                            resetOnSuccess
                            onSuccess={() => setDone('hiring')}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input type="hidden" name="type" value="hiring" />
                                    <input type="text" name="website" tabIndex={-1} autoComplete="off" hidden aria-hidden="true" />

                                    <div className="pub-drawer__fields">
                                        <Identity errors={errors} companyRequired />

                                        <label className="pub-drawer__field">
                                            <span>{t.fab.position}</span>
                                            <input name="subject" required />
                                            {errors.subject && <small>{errors.subject}</small>}
                                        </label>

                                        <label className="pub-drawer__field">
                                            <span>{t.fab.cvProfile}</span>
                                            <select name="job_profile_id" required defaultValue="">
                                                <option value="" disabled>
                                                    {t.fab.choose}
                                                </option>
                                                {props.cvProfiles.map((profile) => (
                                                    <option key={profile.id} value={profile.id}>
                                                        {profile.label}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.job_profile_id && <small>{errors.job_profile_id}</small>}
                                        </label>

                                        <label className="pub-drawer__field">
                                            <span>
                                                {t.fab.contract} ({t.fab.optional})
                                            </span>
                                            <select name="contract" defaultValue="">
                                                <option value="">{t.fab.choose}</option>
                                                <option value="cdi">{t.fab.contractCdi}</option>
                                                <option value="cdd">{t.fab.contractCdd}</option>
                                                <option value="mission">{t.fab.contractMission}</option>
                                                <option value="other">{t.fab.contractOther}</option>
                                            </select>
                                        </label>

                                        <label className="pub-drawer__field pub-drawer__field--wide">
                                            <span>{t.fab.hiringMessage}</span>
                                            <textarea name="message" rows={2} />
                                            {errors.message && <small>{errors.message}</small>}
                                        </label>
                                    </div>

                                    <button type="submit" className="pub-drawer__send" disabled={processing}>
                                        {processing ? t.contact.sending : t.fab.hiringSend}
                                        <span aria-hidden="true">→</span>
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>
                </>
            )}
        </PubDialog>
    );
}
