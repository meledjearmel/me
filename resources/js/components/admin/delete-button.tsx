import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

type DeleteButtonProps = {
    href: string;
    confirmMessage: string;
};

export default function DeleteButton({
    href,
    confirmMessage,
}: DeleteButtonProps) {
    return (
        <Button
            variant="ghost"
            size="icon"
            onClick={() => {
                if (confirm(confirmMessage)) {
                    router.delete(href, { preserveScroll: true });
                }
            }}
        >
            <Trash2 className="text-destructive" />
        </Button>
    );
}
