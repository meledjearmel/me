import { Plus, Trash2 } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Project, ProjectDecision, ProjectKeyFigure } from '@/types';

/** Au-delà, la bande de chiffres de la page publique perd sa lisibilité. */
const MAX_KEY_FIGURES = 4;

type Row = { key: string; figure?: ProjectKeyFigure };

/**
 * Les éléments propres à l'étude de cas : accroche, fiche d'identité
 * (rôle, client, plateforme) et chiffres clés du résultat.
 */
export default function ProjectCaseStudyFields({
    project,
    errors,
}: {
    project?: Project;
    errors: Record<string, string>;
}) {
    const translatableErrors = (name: string) => ({
        fr: errors[`${name}.fr`],
        en: errors[`${name}.en`],
    });

    return (
        <>
            <TranslatableField
                name="tagline"
                label="Accroche (bandeau de la page projet)"
                maxLength={160}
                defaultValue={project?.tagline ?? undefined}
                errors={translatableErrors('tagline')}
            />

            {/* Un champ par ligne : chacun a déjà ses colonnes français et anglais. */}
            <div className="grid gap-4">
                <TranslatableField
                    name="role"
                    label="Rôle"
                    maxLength={120}
                    defaultValue={project?.role ?? undefined}
                    errors={translatableErrors('role')}
                />
                <TranslatableField
                    name="client"
                    label="Client / cadre"
                    maxLength={120}
                    defaultValue={project?.client ?? undefined}
                    errors={translatableErrors('client')}
                />
                <TranslatableField
                    name="platform"
                    label="Plateforme"
                    maxLength={120}
                    defaultValue={project?.platform ?? undefined}
                    errors={translatableErrors('platform')}
                />
            </div>

            <div className="grid gap-2 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="started_on">Début (mois)</Label>
                    <Input
                        id="started_on"
                        name="started_on"
                        type="month"
                        defaultValue={project?.started_on ?? ''}
                    />
                    <InputError message={errors.started_on} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="ended_on">Fin (vide : en cours)</Label>
                    <Input
                        id="ended_on"
                        name="ended_on"
                        type="month"
                        defaultValue={project?.ended_on ?? ''}
                    />
                    <InputError message={errors.ended_on} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="team_size">Taille de l’équipe</Label>
                    <Input
                        id="team_size"
                        name="team_size"
                        type="number"
                        min={1}
                        max={200}
                        defaultValue={project?.team_size ?? ''}
                    />
                    <InputError message={errors.team_size} />
                </div>
            </div>

            <KeyFiguresField
                defaultFigures={project?.key_figures ?? []}
                errors={errors}
            />
        </>
    );
}

/** Au-delà, la section perd son rôle de synthèse. */
const MAX_DECISIONS = 6;

type DecisionRow = { key: string; decision?: ProjectDecision };

/** Choix techniques argumentés : « le choix » et « pourquoi », en français et en anglais. */
export function ProjectDecisionsField({
    project,
    errors,
}: {
    project?: Project;
    errors: Record<string, string>;
}) {
    const idPrefix = useId();
    const [rows, setRows] = useState<DecisionRow[]>(
        (project?.decisions ?? []).map((decision, index) => ({
            key: `${idPrefix}-${index}`,
            decision,
        })),
    );

    return (
        <div className="grid gap-3">
            <Label>
                Choix techniques argumentés ({MAX_DECISIONS} max., facultatif)
            </Label>

            {rows.map((row, index) => (
                <div
                    key={row.key}
                    className="flex items-start gap-2 rounded-md border p-3"
                >
                    <div className="grid flex-1 gap-2 sm:grid-cols-2">
                        {(['fr', 'en'] as const).map((locale) => (
                            <div key={locale} className="grid gap-2">
                                <Input
                                    name={`decisions[${index}][choice][${locale}]`}
                                    placeholder={`Le choix (${locale.toUpperCase()})`}
                                    maxLength={160}
                                    defaultValue={row.decision?.choice[locale]}
                                />
                                <InputError
                                    message={
                                        errors[
                                            `decisions.${index}.choice.${locale}`
                                        ]
                                    }
                                />
                                <Textarea
                                    name={`decisions[${index}][reason][${locale}]`}
                                    placeholder={`Pourquoi (${locale.toUpperCase()})`}
                                    maxLength={600}
                                    rows={3}
                                    defaultValue={row.decision?.reason[locale]}
                                />
                                <InputError
                                    message={
                                        errors[
                                            `decisions.${index}.reason.${locale}`
                                        ]
                                    }
                                />
                            </div>
                        ))}
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Retirer ce choix"
                        onClick={() =>
                            setRows((current) =>
                                current.filter(
                                    (candidate) => candidate.key !== row.key,
                                ),
                            )
                        }
                    >
                        <Trash2 className="text-destructive" />
                    </Button>
                </div>
            ))}

            <InputError message={errors.decisions} />

            {rows.length < MAX_DECISIONS && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={() =>
                        setRows((current) => [
                            ...current,
                            {
                                key: `${idPrefix}-${current.length}-${Date.now()}`,
                            },
                        ])
                    }
                >
                    <Plus /> Ajouter un choix
                </Button>
            )}
        </div>
    );
}

function KeyFiguresField({
    defaultFigures,
    errors,
}: {
    defaultFigures: ProjectKeyFigure[];
    errors: Record<string, string>;
}) {
    const idPrefix = useId();
    const [rows, setRows] = useState<Row[]>(
        defaultFigures.map((figure, index) => ({
            key: `${idPrefix}-${index}`,
            figure,
        })),
    );

    return (
        <div className="grid gap-3">
            <Label>Chiffres clés du résultat ({MAX_KEY_FIGURES} max.)</Label>

            {rows.map((row, index) => (
                <div
                    key={row.key}
                    className="flex items-start gap-2 rounded-md border p-3"
                >
                    <div className="grid flex-1 gap-2 sm:grid-cols-[8rem_1fr_1fr]">
                        <div className="grid gap-1">
                            <Input
                                name={`key_figures[${index}][value]`}
                                placeholder="630+"
                                maxLength={20}
                                defaultValue={row.figure?.value}
                            />
                            <InputError
                                message={errors[`key_figures.${index}.value`]}
                            />
                        </div>
                        <div className="grid gap-1">
                            <Input
                                name={`key_figures[${index}][label][fr]`}
                                placeholder="Légende (FR)"
                                maxLength={80}
                                defaultValue={row.figure?.label.fr}
                            />
                            <InputError
                                message={
                                    errors[`key_figures.${index}.label.fr`]
                                }
                            />
                        </div>
                        <div className="grid gap-1">
                            <Input
                                name={`key_figures[${index}][label][en]`}
                                placeholder="Légende (EN)"
                                maxLength={80}
                                defaultValue={row.figure?.label.en}
                            />
                            <InputError
                                message={
                                    errors[`key_figures.${index}.label.en`]
                                }
                            />
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Retirer ce chiffre"
                        onClick={() =>
                            setRows((current) =>
                                current.filter(
                                    (candidate) => candidate.key !== row.key,
                                ),
                            )
                        }
                    >
                        <Trash2 className="text-destructive" />
                    </Button>
                </div>
            ))}

            <InputError message={errors.key_figures} />

            {rows.length < MAX_KEY_FIGURES && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={() =>
                        setRows((current) => [
                            ...current,
                            {
                                key: `${idPrefix}-${current.length}-${Date.now()}`,
                            },
                        ])
                    }
                >
                    <Plus /> Ajouter un chiffre
                </Button>
            )}
        </div>
    );
}
