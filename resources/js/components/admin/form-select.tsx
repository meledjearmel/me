import type { ReactElement, ReactNode } from 'react';
import { Children, isValidElement, useState } from 'react';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const NONE = '__none__';

type FormSelectProps = {
    id?: string;
    name: string;
    defaultValue?: string | number | null;
    required?: boolean;
    disabled?: boolean;
    'aria-invalid'?: boolean;
    /** Des `<option>` : une option vide désactivée sert de texte d'invite, une option vide active de choix « aucun ». */
    children: ReactNode;
};

type Option = { value: string; label: ReactNode; disabled: boolean };

function readOptions(children: ReactNode): Option[] {
    return Children.toArray(children)
        .filter(
            (
                child,
            ): child is ReactElement<{
                value?: string | number;
                disabled?: boolean;
                children?: ReactNode;
            }> => isValidElement(child),
        )
        .map((child) => ({
            value: String(child.props.value ?? ''),
            label: child.props.children,
            disabled: Boolean(child.props.disabled),
        }));
}

/**
 * Liste déroulante shadcn qui se comporte comme un `<select>` dans un formulaire :
 * même API que l'ancien champ natif, la valeur est envoyée sous `name`.
 */
export default function FormSelect({
    id,
    name,
    defaultValue,
    required,
    disabled,
    children,
    ...rest
}: FormSelectProps) {
    const options = readOptions(children);
    const placeholder = options.find(
        (option) => option.value === '' && option.disabled,
    );
    const items = options.filter(
        (option) => !(option.value === '' && option.disabled),
    );
    const hasNone = items.some((option) => option.value === '');
    const [value, setValue] = useState(
        defaultValue === null || defaultValue === undefined
            ? ''
            : String(defaultValue),
    );

    return (
        <>
            <input type="hidden" name={name} value={value} />
            <Select
                value={value === '' ? (hasNone ? NONE : undefined) : value}
                onValueChange={(next) => setValue(next === NONE ? '' : next)}
                required={required}
                disabled={disabled}
            >
                <SelectTrigger id={id} className="w-full" {...rest}>
                    <SelectValue placeholder={placeholder?.label} />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        {items.map((option) => (
                            <SelectItem
                                key={option.value || NONE}
                                value={
                                    option.value === '' ? NONE : option.value
                                }
                                disabled={option.disabled}
                            >
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectGroup>
                </SelectContent>
            </Select>
        </>
    );
}
