import { Plus, Trash2 } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExperienceHighlight } from '@/types';

type Row = {
    key: string;
    id?: number;
    text?: { fr?: string; en?: string };
};

export default function HighlightsField({
    defaultHighlights = [],
    errors = {},
}: {
    defaultHighlights?: ExperienceHighlight[];
    errors?: Record<string, string>;
}) {
    const idPrefix = useId();
    const [rows, setRows] = useState<Row[]>(
        defaultHighlights.length > 0
            ? defaultHighlights.map((highlight) => ({
                  key: `${idPrefix}-${highlight.id}`,
                  id: highlight.id,
                  text: highlight.text,
              }))
            : [{ key: `${idPrefix}-0` }],
    );

    return (
        <div className="grid gap-3">
            <Label>Puces (réalisations marquantes)</Label>

            {rows.map((row, index) => (
                <div
                    key={row.key}
                    className="flex items-start gap-2 rounded-md border p-3"
                >
                    {row.id && (
                        <input
                            type="hidden"
                            name={`highlights[${index}][id]`}
                            value={row.id}
                        />
                    )}
                    <input
                        type="hidden"
                        name={`highlights[${index}][sort_order]`}
                        value={index}
                    />

                    <div className="grid flex-1 gap-2 sm:grid-cols-2">
                        <Input
                            name={`highlights[${index}][text][fr]`}
                            placeholder="Texte (FR)"
                            defaultValue={row.text?.fr}
                        />
                        <Input
                            name={`highlights[${index}][text][en]`}
                            placeholder="Texte (EN)"
                            defaultValue={row.text?.en}
                        />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            setRows((current) =>
                                current.filter((r) => r.key !== row.key),
                            )
                        }
                    >
                        <Trash2 className="text-destructive" />
                    </Button>
                </div>
            ))}

            <InputError message={errors.highlights} />

            <Button
                type="button"
                variant="outline"
                size="sm"
                className="w-fit"
                onClick={() =>
                    setRows((current) => [
                        ...current,
                        { key: `${idPrefix}-${current.length}-${Date.now()}` },
                    ])
                }
            >
                <Plus /> Ajouter une puce
            </Button>
        </div>
    );
}
