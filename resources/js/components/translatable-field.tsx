import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type TranslatableFieldProps = {
    name: string;
    label: string;
    defaultValue?: { fr?: string; en?: string };
    errors?: { fr?: string; en?: string };
    textarea?: boolean;
    required?: boolean;
    maxLength?: number;
};

export default function TranslatableField({
    name,
    label,
    defaultValue,
    errors,
    textarea = false,
    required = false,
    maxLength,
}: TranslatableFieldProps) {
    const Control = textarea ? Textarea : Input;

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {(['fr', 'en'] as const).map((locale) => (
                <Field key={locale} data-invalid={!!errors?.[locale]}>
                    <FieldLabel htmlFor={`${name}-${locale}`}>
                        {label} ({locale.toUpperCase()}){required && ' *'}
                    </FieldLabel>
                    <Control
                        id={`${name}-${locale}`}
                        name={`${name}[${locale}]`}
                        defaultValue={defaultValue?.[locale]}
                        required={required}
                        maxLength={maxLength}
                        aria-invalid={!!errors?.[locale]}
                    />
                    <FieldError>{errors?.[locale]}</FieldError>
                </Field>
            ))}
        </div>
    );
}
