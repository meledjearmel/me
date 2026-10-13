import { Form, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ProjectEstimateController from '@/actions/App/Http/Controllers/ProjectEstimateController';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { post } from '@/hooks/use-ai-text-assist';
import { useLocale, useTranslations } from '@/lib/i18n';
import type { PublicEstimatorItem } from '@/types';

type Quote = {
    billing: 'fixed' | 'hourly';
    currency: string;
    hourly_rate: number;
    hours_min: number;
    hours_max: number;
    price_min: number;
    price_max: number;
    weeks_min: number;
    weeks_max: number;
};

/**
 * Estimateur de projet en trois temps : le type de projet, les options, puis les
 * coordonnées. La fourchette est recalculée par le serveur à chaque choix, et
 * l'estimation envoyée arrive figée dans les demandes de collaboration.
 */
export default function Estimate({
    projectTypes,
    features,
    currencies,
    defaultCurrency,
}: {
    projectTypes: PublicEstimatorItem[];
    features: PublicEstimatorItem[];
    currencies: string[];
    /** La devise du pays du visiteur, choisie par le serveur. */
    defaultCurrency: string;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { submit } = useHttp();

    const [projectTypeId, setProjectTypeId] = useState<number | null>(
        projectTypes.length === 1 ? projectTypes[0].id : null,
    );
    const [featureIds, setFeatureIds] = useState<number[]>([]);
    const [currency, setCurrency] = useState(defaultCurrency);
    const [quote, setQuote] = useState<Quote | null>(null);
    const [sent, setSent] = useState(false);
    const [description, setDescription] = useState('');
    const [aiStatus, setAiStatus] = useState<
        'idle' | 'pending' | 'done' | 'error'
    >('idle');

    /** « Laisser l'IA choisir » : le type et les options d'après la description du visiteur. */
    const suggest = async () => {
        setAiStatus('pending');

        try {
            const match = await post<{
                project_type_id: number;
                feature_ids: number[];
            }>(ProjectEstimateController.suggest.url(locale), {
                description,
            });

            setProjectTypeId(match.project_type_id);
            setFeatureIds(match.feature_ids);
            setAiStatus('done');
        } catch {
            setAiStatus('error');
        }
    };

    useEffect(() => {
        if (projectTypeId === null) {
            return;
        }

        let cancelled = false;

        submit(
            ProjectEstimateController.quote(locale, {
                query: {
                    project_type_id: projectTypeId,
                    feature_ids: featureIds,
                    currency,
                },
            }),
        )
            .then((response) => {
                if (!cancelled) {
                    setQuote(response as Quote);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setQuote(null);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [projectTypeId, featureIds, currency, locale, submit]);

    const money = (amount: number) =>
        new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(
            amount,
        ) +
        ' ' +
        (currency === 'XOF' ? 'FCFA' : currency);

    const toggleFeature = (id: number) =>
        setFeatureIds((current) =>
            current.includes(id)
                ? current.filter((candidate) => candidate !== id)
                : [...current, id],
        );

    const restart = () => {
        setSent(false);
        setFeatureIds([]);
        setProjectTypeId(projectTypes.length === 1 ? projectTypes[0].id : null);
        setQuote(null);
    };

    return (
        <>
            <Seo
                title={t.estimate.title}
                description={t.estimate.lead}
                breadcrumbs={[[t.estimate.title, `/${locale}/estimate`]]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-estimate-title"
                    eyebrow={t.estimate.kicker}
                    title={t.estimate.heading}
                    lead={<p>{t.estimate.lead}</p>}
                />

                <section
                    className="pub-contact pub-booking"
                    aria-label={t.estimate.title}
                >
                    <div className="site-wrap">
                        <div className="pub-contact__card pub-booking__card">
                            {sent ? (
                                <div className="pub-drawer__done" role="status">
                                    <svg
                                        className="pub-drawer__check"
                                        viewBox="0 0 64 64"
                                        aria-hidden="true"
                                    >
                                        <circle cx="32" cy="32" r="28" />
                                        <path d="M19 33l9 9 17-19" />
                                    </svg>
                                    <h3>{t.estimate.doneTitle}</h3>
                                    <p>{t.estimate.done}</p>
                                    <button
                                        type="button"
                                        className="pub-contact__again"
                                        onClick={restart}
                                    >
                                        {t.estimate.again}
                                    </button>
                                </div>
                            ) : (
                                <>
                                    <div className="pub-booking__step">
                                        <p className="pub-booking__label">
                                            {t.estimate.aiTitle}
                                        </p>
                                        <p className="pub-booking__note">
                                            {t.estimate.aiHint}
                                        </p>
                                        <label className="pub-drawer__field pub-drawer__field--wide">
                                            <span className="sr-only">
                                                {t.estimate.aiTitle}
                                            </span>
                                            <textarea
                                                rows={3}
                                                maxLength={2000}
                                                value={description}
                                                placeholder={
                                                    t.estimate.aiPlaceholder
                                                }
                                                onChange={(event) =>
                                                    setDescription(
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </label>
                                        <button
                                            type="button"
                                            className="pub-filter"
                                            disabled={
                                                aiStatus === 'pending' ||
                                                description.trim().length < 15
                                            }
                                            onClick={() => void suggest()}
                                        >
                                            {aiStatus === 'pending'
                                                ? t.estimate.aiThinking
                                                : t.estimate.aiSubmit}
                                        </button>
                                        {(aiStatus === 'done' ||
                                            aiStatus === 'error') && (
                                            <p
                                                className="pub-booking__note"
                                                role="status"
                                            >
                                                {aiStatus === 'done'
                                                    ? t.estimate.aiDone
                                                    : t.estimate.aiError}
                                            </p>
                                        )}
                                    </div>

                                    <fieldset className="pub-booking__step">
                                        <legend>
                                            <span>01</span> {t.estimate.step1}
                                        </legend>
                                        <div className="pub-booking__types">
                                            {projectTypes.map((candidate) => (
                                                <button
                                                    key={candidate.id}
                                                    type="button"
                                                    className="pub-booking__type"
                                                    aria-pressed={
                                                        candidate.id ===
                                                        projectTypeId
                                                    }
                                                    onClick={() =>
                                                        setProjectTypeId(
                                                            candidate.id,
                                                        )
                                                    }
                                                >
                                                    <strong>
                                                        {candidate.name}
                                                    </strong>
                                                    {candidate.description && (
                                                        <span>
                                                            {
                                                                candidate.description
                                                            }
                                                        </span>
                                                    )}
                                                </button>
                                            ))}
                                        </div>
                                    </fieldset>

                                    {projectTypeId !== null &&
                                        features.length > 0 && (
                                            <fieldset className="pub-booking__step">
                                                <legend>
                                                    <span>02</span>{' '}
                                                    {t.estimate.step2}
                                                </legend>
                                                <p className="pub-booking__note">
                                                    {t.estimate.step2Hint}
                                                </p>
                                                <div className="pub-booking__types">
                                                    {features.map(
                                                        (candidate) => (
                                                            <button
                                                                key={
                                                                    candidate.id
                                                                }
                                                                type="button"
                                                                className="pub-booking__type"
                                                                aria-pressed={featureIds.includes(
                                                                    candidate.id,
                                                                )}
                                                                onClick={() =>
                                                                    toggleFeature(
                                                                        candidate.id,
                                                                    )
                                                                }
                                                            >
                                                                <strong>
                                                                    {
                                                                        candidate.name
                                                                    }
                                                                </strong>
                                                                {candidate.description && (
                                                                    <span>
                                                                        {
                                                                            candidate.description
                                                                        }
                                                                    </span>
                                                                )}
                                                            </button>
                                                        ),
                                                    )}
                                                </div>
                                            </fieldset>
                                        )}

                                    {projectTypeId !== null && (
                                        <div
                                            className="pub-booking__step"
                                            aria-live="polite"
                                        >
                                            {currencies.length > 1 && (
                                                <>
                                                    <p className="pub-booking__label">
                                                        {t.estimate.currency}
                                                    </p>
                                                    <div
                                                        className="pub-booking__times"
                                                        role="group"
                                                        aria-label={
                                                            t.estimate.currency
                                                        }
                                                    >
                                                        {currencies.map(
                                                            (candidate) => (
                                                                <button
                                                                    key={
                                                                        candidate
                                                                    }
                                                                    type="button"
                                                                    className="pub-filter"
                                                                    aria-pressed={
                                                                        candidate ===
                                                                        currency
                                                                    }
                                                                    onClick={() =>
                                                                        setCurrency(
                                                                            candidate,
                                                                        )
                                                                    }
                                                                >
                                                                    {candidate ===
                                                                    'XOF'
                                                                        ? 'FCFA'
                                                                        : candidate}
                                                                </button>
                                                            ),
                                                        )}
                                                    </div>
                                                </>
                                            )}

                                            <p className="pub-booking__summary">
                                                <span>
                                                    {quote?.billing === 'fixed'
                                                        ? t.estimate.fixedPrice
                                                        : t.estimate.result}
                                                </span>
                                                <strong>
                                                    {!quote ||
                                                    quote.currency !== currency
                                                        ? t.estimate.calculating
                                                        : quote.billing ===
                                                            'hourly'
                                                          ? `${t.estimate.hourlyPrice(`${quote.hours_min} – ${quote.hours_max} h`, money(quote.hourly_rate))} · ${t.estimate.weeks(quote.weeks_min, quote.weeks_max)}`
                                                          : `${money(quote.price_min)} – ${money(quote.price_max)} · ${t.estimate.weeks(quote.weeks_min, quote.weeks_max)}`}
                                                </strong>
                                                {quote?.billing === 'hourly' &&
                                                    quote.currency ===
                                                        currency && (
                                                        <span>
                                                            {t.estimate.hourlyTotal(
                                                                `${money(quote.price_min)} – ${money(quote.price_max)}`,
                                                            )}
                                                        </span>
                                                    )}
                                            </p>
                                            <p className="pub-booking__note">
                                                {quote?.billing === 'hourly'
                                                    ? `${t.estimate.hourlyNote} ${t.estimate.disclaimer}`
                                                    : t.estimate.disclaimer}
                                            </p>
                                        </div>
                                    )}

                                    {projectTypeId !== null && quote && (
                                        <Form
                                            {...ProjectEstimateController.store.form(
                                                locale,
                                            )}
                                            className="pub-booking__step pub-contact__form"
                                            options={{ preserveScroll: true }}
                                            onSuccess={() => setSent(true)}
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <h3 className="pub-booking__legend">
                                                        <span>
                                                            {features.length > 0
                                                                ? '03'
                                                                : '02'}
                                                        </span>{' '}
                                                        {t.estimate.step3}
                                                    </h3>

                                                    <input
                                                        type="text"
                                                        name="website"
                                                        tabIndex={-1}
                                                        autoComplete="off"
                                                        hidden
                                                        aria-hidden="true"
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="project_type_id"
                                                        value={projectTypeId}
                                                    />
                                                    {featureIds.map((id) => (
                                                        <input
                                                            key={id}
                                                            type="hidden"
                                                            name="feature_ids[]"
                                                            value={id}
                                                        />
                                                    ))}
                                                    <input
                                                        type="hidden"
                                                        name="currency"
                                                        value={currency}
                                                    />

                                                    <div className="pub-drawer__fields">
                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {
                                                                    t.estimate
                                                                        .name
                                                                }
                                                            </span>
                                                            <input
                                                                name="name"
                                                                autoComplete="name"
                                                                required
                                                            />
                                                            {errors.name && (
                                                                <small>
                                                                    {
                                                                        errors.name
                                                                    }
                                                                </small>
                                                            )}
                                                        </label>

                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {
                                                                    t.estimate
                                                                        .email
                                                                }
                                                            </span>
                                                            <input
                                                                type="email"
                                                                name="email"
                                                                autoComplete="email"
                                                                required
                                                            />
                                                            {errors.email && (
                                                                <small>
                                                                    {
                                                                        errors.email
                                                                    }
                                                                </small>
                                                            )}
                                                        </label>

                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {
                                                                    t.estimate
                                                                        .company
                                                                }
                                                            </span>
                                                            <input
                                                                name="company"
                                                                autoComplete="organization"
                                                            />
                                                        </label>

                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {t.fab.timeline}{' '}
                                                                (
                                                                {t.fab.optional}
                                                                )
                                                            </span>
                                                            <select
                                                                name="timeline"
                                                                defaultValue=""
                                                            >
                                                                <option value="">
                                                                    {
                                                                        t.fab
                                                                            .choose
                                                                    }
                                                                </option>
                                                                <option value="urgent">
                                                                    {
                                                                        t.fab
                                                                            .timeUrgent
                                                                    }
                                                                </option>
                                                                <option value="quarter">
                                                                    {
                                                                        t.fab
                                                                            .timeQuarter
                                                                    }
                                                                </option>
                                                                <option value="semester">
                                                                    {
                                                                        t.fab
                                                                            .timeSemester
                                                                    }
                                                                </option>
                                                                <option value="flexible">
                                                                    {
                                                                        t.fab
                                                                            .timeFlexible
                                                                    }
                                                                </option>
                                                            </select>
                                                        </label>

                                                        <label className="pub-drawer__field pub-drawer__field--wide">
                                                            <span>
                                                                {
                                                                    t.estimate
                                                                        .message
                                                                }
                                                            </span>
                                                            <textarea
                                                                name="message"
                                                                rows={4}
                                                            />
                                                            {errors.message && (
                                                                <small>
                                                                    {
                                                                        errors.message
                                                                    }
                                                                </small>
                                                            )}
                                                        </label>
                                                    </div>

                                                    <button
                                                        type="submit"
                                                        className="pub-drawer__send"
                                                        disabled={processing}
                                                    >
                                                        {processing
                                                            ? t.estimate.sending
                                                            : t.estimate.submit}
                                                        <span aria-hidden="true">
                                                            →
                                                        </span>
                                                    </button>
                                                </>
                                            )}
                                        </Form>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
