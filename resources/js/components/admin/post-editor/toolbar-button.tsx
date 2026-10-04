import type { LucideIcon } from "lucide-react";
import type { ComponentProps } from "react";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";

/** Bouton d'icône de l'éditeur, avec son libellé en infobulle. */
export default function ToolbarButton({
    icon: Icon,
    label,
    shortcut,
    active = false,
    className,
    ...props
}: {
    icon: LucideIcon;
    label: string;
    shortcut?: string;
    active?: boolean;
} & Omit<ComponentProps<typeof Button>, "children">) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={label}
                    aria-pressed={active}
                    // Garde la sélection de l'éditeur au clic.
                    onMouseDown={(event) => event.preventDefault()}
                    className={cn(
                        "size-8 text-muted-foreground",
                        active && "bg-accent text-foreground",
                        className,
                    )}
                    {...props}
                >
                    <Icon className="size-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {label}
                {shortcut && (
                    <span className="ml-2 text-muted-foreground">
                        {shortcut}
                    </span>
                )}
            </TooltipContent>
        </Tooltip>
    );
}
