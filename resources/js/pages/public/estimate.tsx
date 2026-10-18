import { Form, useHttp } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import ProjectEstimateController from "@/actions/App/Http/Controllers/ProjectEstimateController";
import PageHero from "@/components/public/page-hero";
import PublicShell from "@/components/public/public-shell";
import Seo from "@/components/public/seo";
import { post } from "@/hooks/use-ai-text-assist";
import { useLocale, useTranslations } from "@/lib/i18n";
import type { PublicEstimatorItem } from "@/types";

type Quote = {
    billing: "fixed" | "hourly";
    currency: string;
    hourly_rate: number;
    hours_min: number;
    hours_max: number;
    price_min: number;
    price_max: number;
    weeks_min: number;
    weeks_max: number;
    /** Options récurrentes, hors total : le prix de la période choisie. */
    recurring: {
        id: number;
        period: string;
        discount_percent: number;
        price_min: number;
        price_max: number;
    }[];
};

type StepKey = "project" | "options" | "estimate" | "contact";

/**
 * Estimateur de projet en étapes, une à la fois : le projet (avec l'aide facultative de
 * l'IA), les options, l'estimation, puis les coordonnées. La fourchette est recalculée
 * par le serveur à chaque choix, et l'estimation envoyée arrive figée dans les demandes
 * de collaboration.
 */
export default function Estimate({
    projectTypes,
    features,
    defaultCurrency,
    preselectedTypeId,
}: {
    projectTypes: PublicEstimatorItem[];
    features: PublicEstimatorItem[];
    currencies: string[];
    /** La devise du pays du visiteur, choisie par le serveur. */
    defaultCurrency: string;
    /** Type de projet présélectionné par le lien d'une offre (?type=…). */
    preselectedTypeId: number | null;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { submit } = useHttp();

    const [stepIndex, setStepIndex] = useState(0);
    const [projectTypeId, setProjectTypeId] = useState<number | null>(
        preselectedTypeId ??
            (projectTypes.length === 1 ? projectTypes[0].id : null),
    );
    const [featureIds, setFeatureIds] = useState<number[]>([]);
    /** Option récurrente → période choisie (à défaut, la première proposée). */
    const [periods, setPeriods] = useState<Record<number, string>>({});

    // Une option peut être réservée à certains types : seules celles du type choisi sont proposées.
    const isOffered = (feature: PublicEstimatorItem, typeId: number | null) =>
        feature.project_type_ids.length === 0 ||
        (typeId !== null && feature.project_type_ids.includes(typeId));
    const availableFeatures = features.filter((feature) =>
        isOffered(feature, projectTypeId),
    );

    // Sans option pour ce type, l'étape des options n'a pas lieu d'être.
    const steps: StepKey[] =
        availableFeatures.length > 0
            ? ["project", "options", "estimate", "contact"]
            : ["project", "estimate", "contact"];

    /** Changer de type retire les options qui ne lui sont pas proposées. */
    const chooseProjectType = (typeId: number) => {
        setProjectTypeId(typeId);
        setFeatureIds((current) =>
            current.filter((id) => {
                const feature = features.find(
                    (candidate) => candidate.id === id,
                );

                return feature !== undefined && isOffered(feature, typeId);
            }),
        );
    };
    // La devise suit le pays du visiteur, choisie par le serveur : pas de sélecteur,
    // pour que chacun ne voie que le tarif de son marché.
    const currency = defaultCurrency;
    const [quote, setQuote] = useState<Quote | null>(null);
    const [sent, setSent] = useState(false);
    const [description, setDescription] = useState("");
    const [aiStatus, setAiStatus] = useState<
        "idle" | "pending" | "done" | "error"
    >("idle");
    const [blocked, setBlocked] = useState(false);
    const headingRef = useRef<HTMLHeadingElement>(null);
    const hasNavigated = useRef(false);

    const step = steps[stepIndex];

    // Le titre de l'étape prend le focus à chaque changement, pour les lecteurs d'écran.
    useEffect(() => {
        if (hasNavigated.current) {
            headingRef.current?.focus();
        }
    }, [stepIndex]);

    /** « Laisser l'IA choisir » : le type et les options d'après la description du visiteur. */
    const suggest = async () => {
        setAiStatus("pending");

        try {
            const match = await post<{
                project_type_id: number;
                feature_ids: number[];
            }>(ProjectEstimateController.suggest.url(locale), {
                description,
            });

            setProjectTypeId(match.project_type_id);
            setFeatureIds(match.feature_ids);
            setBlocked(false);
            setAiStatus("done");
        } catch {
            setAiStatus("error");
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
                    periods,
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
    }, [projectTypeId, featureIds, periods, currency, locale, submit]);

    const money = (amount: number) =>
        new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(
            amount,
        ) +
        " " +
        (currency === "XOF" ? "FCFA" : currency);

    const currentQuote = quote && quote.currency === currency ? quote : null;
    const quoteLabel = currentQuote
        ? currentQuote.billing === "hourly"
            ? `${t.estimate.hourlyPrice(`${currentQuote.hours_min} – ${currentQuote.hours_max} h`, money(currentQuote.hourly_rate))} · ${t.estimate.weeks(currentQuote.weeks_min, currentQuote.weeks_max)}`
            : `${money(currentQuote.price_min)} – ${money(currentQuote.price_max)} · ${t.estimate.weeks(currentQuote.weeks_min, currentQuote.weeks_max)}`
        : t.estimate.calculating;
    // « + 150 – 250 CHF / mois » par option récurrente, à côté de la fourchette du projet.
    const recurringLabel = (currentQuote?.recurring ?? [])
        .map(
            (line) =>
                ` + ${money(line.price_min)} – ${money(line.price_max)} ${t.estimate.perPeriod[line.period] ?? ""}`,
        )
        .join("");

    const toggleFeature = (id: number) =>
        setFeatureIds((current) =>
            current.includes(id)
                ? current.filter((candidate) => candidate !== id)
                : [...current, id],
        );

    const goTo = (index: number) => {
        hasNavigated.current = true;
        setStepIndex(Math.max(0, Math.min(index, steps.length - 1)));
    };

    const next = () => {
        if (step === "project" && projectTypeId === null) {
            setBlocked(true);

            return;
        }

        goTo(stepIndex + 1);
    };

    const restart = () => {
        setSent(false);
        setFeatureIds([]);
        setPeriods({});
        setProjectTypeId(projectTypes.length === 1 ? projectTypes[0].id : null);
        setQuote(null);
        setDescription("");
        setAiStatus("idle");
        goTo(0);
    };

    const titles: Record<StepKey, string> = {
        project: t.estimate.step1,
        options: t.estimate.step2,
        estimate: t.estimate.stepEstimate,
        contact: t.estimate.step3,
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
                        <div className="pub-contact__card pub-booking__card pub-estimate">
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
                                    <div className="pub-estimate__progress">
                                        <p className="pub-booking__label">
                                            {t.estimate.progress(
                                                stepIndex + 1,
                                                steps.length,
                                            )}
                                            <span className="pub-estimate__current">
                                                {` · ${titles[step]}`}
                                            </span>
                                        </p>
                                        <ol>
                                            {steps.map((key, index) => (
                                                <li
                                                    key={key}
                                                    data-state={
                                                        index < stepIndex
                                                            ? "done"
                                                            : index ===
                                                                stepIndex
                                                              ? "current"
                                                              : "todo"
                                                    }
                                                    aria-current={
                                                        index === stepIndex
                                                            ? "step"
                                                            : undefined
                                                    }
                                                >
                                                    {index < stepIndex ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                goTo(index)
                                                            }
                                                        >
                                                            {titles[key]}
                                                        </button>
                                                    ) : (
                                                        <span>
                                                            {titles[key]}
                                                        </span>
                                                    )}
                                                </li>
                                            ))}
                                        </ol>
                                    </div>

                                    <div
                                        key={step}
                                        className="pub-booking__step pub-estimate__panel"
                                    >
                                        <h2
                                            ref={headingRef}
                                            tabIndex={-1}
                                            className="pub-booking__legend"
                                        >
                                            <span>
                                                {String(stepIndex + 1).padStart(
                                                    2,
                                                    "0",
                                                )}
                                            </span>{" "}
                                            {titles[step]}
                                        </h2>

                                        {(step === "options" ||
                                            step === "contact") && (
                                            <p
                                                className="pub-estimate__mini"
                                                aria-live="polite"
                                            >
                                                <span>{t.estimate.result}</span>{" "}
                                                {quoteLabel}
                                                {recurringLabel}
                                            </p>
                                        )}

                                        {step === "project" && (
                                            <>
                                                <div className="pub-estimate__ai">
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
                                                                t.estimate
                                                                    .aiPlaceholder
                                                            }
                                                            onChange={(event) =>
                                                                setDescription(
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                    </label>
                                                    <div className="pub-booking__times">
                                                        <button
                                                            type="button"
                                                            className="pub-filter"
                                                            disabled={
                                                                aiStatus ===
                                                                    "pending" ||
                                                                description.trim()
                                                                    .length < 15
                                                            }
                                                            onClick={() =>
                                                                void suggest()
                                                            }
                                                        >
                                                            {aiStatus ===
                                                            "pending"
                                                                ? t.estimate
                                                                      .aiThinking
                                                                : t.estimate
                                                                      .aiSubmit}
                                                        </button>
                                                    </div>
                                                    {(aiStatus === "done" ||
                                                        aiStatus ===
                                                            "error") && (
                                                        <p
                                                            className="pub-booking__note"
                                                            role="status"
                                                        >
                                                            {aiStatus === "done"
                                                                ? t.estimate
                                                                      .aiDone
                                                                : t.estimate
                                                                      .aiError}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="pub-booking__types">
                                                    {projectTypes.map(
                                                        (candidate) => (
                                                            <button
                                                                key={
                                                                    candidate.id
                                                                }
                                                                type="button"
                                                                className="pub-booking__type"
                                                                aria-pressed={
                                                                    candidate.id ===
                                                                    projectTypeId
                                                                }
                                                                onClick={() => {
                                                                    chooseProjectType(
                                                                        candidate.id,
                                                                    );
                                                                    setBlocked(
                                                                        false,
                                                                    );
                                                                }}
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
                                                {blocked && (
                                                    <small className="pub-booking__error">
                                                        {t.estimate.chooseType}
                                                    </small>
                                                )}
                                            </>
                                        )}

                                        {step === "options" && (
                                            <>
                                                <p className="pub-booking__note">
                                                    {t.estimate.step2Hint}
                                                </p>
                                                <div className="pub-booking__types">
                                                    {availableFeatures.map(
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
                                                                    {candidate.periods && (
                                                                        <em className="pub-estimate__tag">
                                                                            {
                                                                                t
                                                                                    .estimate
                                                                                    .recurringTag
                                                                            }
                                                                        </em>
                                                                    )}
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
                                            </>
                                        )}

                                        {step === "estimate" && (
                                            <>
                                                <p
                                                    className="pub-booking__summary"
                                                    aria-live="polite"
                                                >
                                                    <span>
                                                        {currentQuote?.billing ===
                                                        "fixed"
                                                            ? t.estimate
                                                                  .fixedPrice
                                                            : t.estimate.result}
                                                    </span>
                                                    <strong>
                                                        {quoteLabel}
                                                    </strong>
                                                    {currentQuote?.billing ===
                                                        "hourly" && (
                                                        <span>
                                                            {t.estimate.hourlyTotal(
                                                                `${money(currentQuote.price_min)} – ${money(currentQuote.price_max)}`,
                                                            )}
                                                        </span>
                                                    )}
                                                </p>
                                                {currentQuote &&
                                                    currentQuote.recurring
                                                        .length > 0 && (
                                                        <div className="pub-estimate__recurring">
                                                            <p className="pub-booking__label">
                                                                {
                                                                    t.estimate
                                                                        .recurringTitle
                                                                }
                                                            </p>
                                                            {currentQuote.recurring.map(
                                                                (line) => {
                                                                    const feature =
                                                                        features.find(
                                                                            (
                                                                                candidate,
                                                                            ) =>
                                                                                candidate.id ===
                                                                                line.id,
                                                                        );
                                                                    const offered =
                                                                        Object.entries(
                                                                            feature?.periods ??
                                                                                {},
                                                                        );

                                                                    return (
                                                                        <div
                                                                            key={
                                                                                line.id
                                                                            }
                                                                            className="pub-estimate__recurring-line"
                                                                        >
                                                                            <p className="pub-booking__summary">
                                                                                <span>
                                                                                    {
                                                                                        feature?.name
                                                                                    }
                                                                                </span>
                                                                                <strong>
                                                                                    {`${money(line.price_min)} – ${money(line.price_max)} ${t.estimate.perPeriod[line.period] ?? ""}`}
                                                                                </strong>
                                                                            </p>
                                                                            {offered.length >
                                                                                1 && (
                                                                                <div
                                                                                    className="pub-booking__times"
                                                                                    role="group"
                                                                                    aria-label={
                                                                                        feature?.name
                                                                                    }
                                                                                >
                                                                                    {offered.map(
                                                                                        ([
                                                                                            period,
                                                                                            discount,
                                                                                        ]) => (
                                                                                            <button
                                                                                                key={
                                                                                                    period
                                                                                                }
                                                                                                type="button"
                                                                                                className="pub-filter"
                                                                                                aria-pressed={
                                                                                                    period ===
                                                                                                    line.period
                                                                                                }
                                                                                                onClick={() =>
                                                                                                    setPeriods(
                                                                                                        (
                                                                                                            current,
                                                                                                        ) => ({
                                                                                                            ...current,
                                                                                                            [line.id]:
                                                                                                                period,
                                                                                                        }),
                                                                                                    )
                                                                                                }
                                                                                            >
                                                                                                {
                                                                                                    t
                                                                                                        .estimate
                                                                                                        .periods[
                                                                                                        period
                                                                                                    ]
                                                                                                }
                                                                                                {discount
                                                                                                    ? ` ${t.estimate.discount(discount)}`
                                                                                                    : ""}
                                                                                            </button>
                                                                                        ),
                                                                                    )}
                                                                                </div>
                                                                            )}
                                                                        </div>
                                                                    );
                                                                },
                                                            )}
                                                            <p className="pub-booking__note">
                                                                {
                                                                    t.estimate
                                                                        .recurringNote
                                                                }
                                                            </p>
                                                        </div>
                                                    )}
                                                <p className="pub-booking__note">
                                                    {currentQuote?.billing ===
                                                    "hourly"
                                                        ? `${t.estimate.hourlyNote} ${t.estimate.disclaimer}`
                                                        : t.estimate.disclaimer}
                                                </p>
                                                <p className="pub-booking__note">
                                                    {
                                                        t.estimate
                                                            .stepEstimateHint
                                                    }
                                                </p>
                                            </>
                                        )}

                                        {step === "contact" &&
                                            projectTypeId !== null && (
                                                <Form
                                                    {...ProjectEstimateController.store.form(
                                                        locale,
                                                    )}
                                                    className="pub-contact__form"
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                    onSuccess={() =>
                                                        setSent(true)
                                                    }
                                                >
                                                    {({
                                                        processing,
                                                        errors,
                                                    }) => (
                                                        <>
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
                                                                value={
                                                                    projectTypeId
                                                                }
                                                            />
                                                            {featureIds.map(
                                                                (id) => (
                                                                    <input
                                                                        key={id}
                                                                        type="hidden"
                                                                        name="feature_ids[]"
                                                                        value={
                                                                            id
                                                                        }
                                                                    />
                                                                ),
                                                            )}
                                                            {Object.entries(
                                                                periods,
                                                            )
                                                                .filter(
                                                                    ([id]) =>
                                                                        featureIds.includes(
                                                                            Number(
                                                                                id,
                                                                            ),
                                                                        ),
                                                                )
                                                                .map(
                                                                    ([
                                                                        id,
                                                                        period,
                                                                    ]) => (
                                                                        <input
                                                                            key={
                                                                                id
                                                                            }
                                                                            type="hidden"
                                                                            name={`periods[${id}]`}
                                                                            value={
                                                                                period
                                                                            }
                                                                        />
                                                                    ),
                                                                )}
                                                            <input
                                                                type="hidden"
                                                                name="currency"
                                                                value={currency}
                                                            />

                                                            <div className="pub-drawer__fields">
                                                                <label className="pub-drawer__field">
                                                                    <span>
                                                                        {
                                                                            t
                                                                                .estimate
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
                                                                            t
                                                                                .estimate
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
                                                                            t
                                                                                .estimate
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
                                                                        {
                                                                            t
                                                                                .fab
                                                                                .timeline
                                                                        }{" "}
                                                                        (
                                                                        {
                                                                            t
                                                                                .fab
                                                                                .optional
                                                                        }
                                                                        )
                                                                    </span>
                                                                    <select
                                                                        name="timeline"
                                                                        defaultValue=""
                                                                    >
                                                                        <option value="">
                                                                            {
                                                                                t
                                                                                    .fab
                                                                                    .choose
                                                                            }
                                                                        </option>
                                                                        <option value="urgent">
                                                                            {
                                                                                t
                                                                                    .fab
                                                                                    .timeUrgent
                                                                            }
                                                                        </option>
                                                                        <option value="quarter">
                                                                            {
                                                                                t
                                                                                    .fab
                                                                                    .timeQuarter
                                                                            }
                                                                        </option>
                                                                        <option value="semester">
                                                                            {
                                                                                t
                                                                                    .fab
                                                                                    .timeSemester
                                                                            }
                                                                        </option>
                                                                        <option value="flexible">
                                                                            {
                                                                                t
                                                                                    .fab
                                                                                    .timeFlexible
                                                                            }
                                                                        </option>
                                                                    </select>
                                                                </label>

                                                                <label className="pub-drawer__field pub-drawer__field--wide">
                                                                    <span>
                                                                        {
                                                                            t
                                                                                .estimate
                                                                                .message
                                                                        }
                                                                    </span>
                                                                    <textarea
                                                                        name="message"
                                                                        rows={4}
                                                                        defaultValue={
                                                                            description
                                                                        }
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

                                                            <div className="pub-estimate__nav">
                                                                <button
                                                                    type="button"
                                                                    className="pub-booking__link"
                                                                    onClick={() =>
                                                                        goTo(
                                                                            stepIndex -
                                                                                1,
                                                                        )
                                                                    }
                                                                >
                                                                    ←{" "}
                                                                    {
                                                                        t
                                                                            .estimate
                                                                            .back
                                                                    }
                                                                </button>
                                                                <button
                                                                    type="submit"
                                                                    className="pub-drawer__send"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    {processing
                                                                        ? t
                                                                              .estimate
                                                                              .sending
                                                                        : t
                                                                              .estimate
                                                                              .submit}
                                                                    <span aria-hidden="true">
                                                                        →
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        </>
                                                    )}
                                                </Form>
                                            )}

                                        {step !== "contact" && (
                                            <div className="pub-estimate__nav">
                                                {stepIndex > 0 ? (
                                                    <button
                                                        type="button"
                                                        className="pub-booking__link"
                                                        onClick={() =>
                                                            goTo(stepIndex - 1)
                                                        }
                                                    >
                                                        ← {t.estimate.back}
                                                    </button>
                                                ) : (
                                                    <span />
                                                )}
                                                <button
                                                    type="button"
                                                    className="pub-drawer__send"
                                                    onClick={next}
                                                    disabled={
                                                        step === "estimate" &&
                                                        !currentQuote
                                                    }
                                                >
                                                    {step === "estimate"
                                                        ? t.estimate.send
                                                        : step === "options" &&
                                                            featureIds.length ===
                                                                0
                                                          ? t.estimate.skip
                                                          : t.estimate.next}
                                                    <span aria-hidden="true">
                                                        →
                                                    </span>
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
