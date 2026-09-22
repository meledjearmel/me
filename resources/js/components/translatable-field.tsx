import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type TranslatableFieldProps = {
    name: string;
    label: string;
    defaultValue?: { fr?: string; en?: string };
    errors?: { fr?: string; en?: string };
    textarea?: boolean;
    required?: boolean;
};

export default function TranslatableField({
    name,
    label,
    defaultValue,
    errors,
    textarea = false,
    required = false,
}: TranslatableFieldProps) {
    const Field = textarea ? Textarea : Input;

    return (
        <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
                <Label htmlFor={`${name}-fr`}>
                    {label} (FR){required && ' *'}
                </Label>
                <Field
                    id={`${name}-fr`}
                    name={`${name}[fr]`}
                    defaultValue={defaultValue?.fr}
                    required={required}
                />
                <InputError message={errors?.fr} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${name}-en`}>
                    {label} (EN){required && ' *'}
                </Label>
                <Field
                    id={`${name}-en`}
                    name={`${name}[en]`}
                    defaultValue={defaultValue?.en}
                    required={required}
                />
                <InputError message={errors?.en} />
            </div>
        </div>
    );
}
