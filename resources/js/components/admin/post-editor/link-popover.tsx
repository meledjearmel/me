import type { Editor } from "@tiptap/core";
import { Link2, Unlink } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import ToolbarButton from "./toolbar-button";

/** Ajoute, modifie ou retire le lien de la sélection. */
export default function LinkPopover({
    editor,
    active,
}: {
    editor: Editor;
    active: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [url, setUrl] = useState("");

    function apply() {
        const href = url.trim();

        if (href === "") {
            editor.chain().focus().extendMarkRange("link").unsetLink().run();
        } else {
            editor
                .chain()
                .focus()
                .extendMarkRange("link")
                .setLink({
                    href: /^(https?:|mailto:|\/|#)/i.test(href)
                        ? href
                        : `https://${href}`,
                })
                .run();
        }

        setOpen(false);
    }

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (next) {
                    setUrl(editor.getAttributes("link").href ?? "");
                }
            }}
        >
            <PopoverTrigger asChild>
                <ToolbarButton
                    icon={Link2}
                    label="Lien"
                    shortcut="Ctrl+K"
                    active={active}
                />
            </PopoverTrigger>
            <PopoverContent className="w-80 p-2" align="start">
                <form
                    className="flex gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        apply();
                    }}
                >
                    <Input
                        autoFocus
                        value={url}
                        onChange={(event) => setUrl(event.target.value)}
                        placeholder="https://…"
                        aria-label="Adresse du lien"
                    />
                    <Button type="submit" size="sm">
                        OK
                    </Button>
                    {active && (
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Retirer le lien"
                            onClick={() => {
                                editor
                                    .chain()
                                    .focus()
                                    .extendMarkRange("link")
                                    .unsetLink()
                                    .run();
                                setOpen(false);
                            }}
                        >
                            <Unlink />
                        </Button>
                    )}
                </form>
            </PopoverContent>
        </Popover>
    );
}
