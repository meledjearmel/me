import { Label } from '@/components/ui/label';

type CheckboxGroupProps = {
    label: string;
    name: string;
    options: { id: number; label: string }[];
    defaultSelectedIds?: number[];
};

export default function CheckboxGroup({
    label,
    name,
    options,
    defaultSelectedIds = [],
}: CheckboxGroupProps) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <div className="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto rounded-md border p-3">
                {options.map((option) => (
                    <label
                        key={option.id}
                        className="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            name={`${name}[]`}
                            value={option.id}
                            defaultChecked={defaultSelectedIds.includes(
                                option.id,
                            )}
                            className="size-4"
                        />
                        {option.label}
                    </label>
                ))}
            </div>
        </div>
    );
}
