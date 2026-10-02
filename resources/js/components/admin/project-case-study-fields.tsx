import { Plus, Trash2 } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Project, ProjectKeyFigure } from '@/types';

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

            <div className="grid gap-2 sm:grid-cols-3">
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

            <KeyFiguresField
                defaultFigures={project?.key_figures ?? []}
                errors={errors}
            />
        </>
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
