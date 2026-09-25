import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';

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
        <FieldSet>
            <FieldLegend variant="label">{label}</FieldLegend>
            <FieldGroup className="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto rounded-md border p-3">
                {options.map((option) => (
                    <Field key={option.id} orientation="horizontal">
                        <Checkbox
                            id={`${name}-${option.id}`}
                            name={`${name}[]`}
                            value={option.id}
                            defaultChecked={defaultSelectedIds.includes(
                                option.id,
                            )}
                        />
                        <FieldLabel
                            htmlFor={`${name}-${option.id}`}
                            className="font-normal"
                        >
                            {option.label}
                        </FieldLabel>
                    </Field>
                ))}
            </FieldGroup>
        </FieldSet>
    );
}
