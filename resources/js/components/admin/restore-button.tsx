import { router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';

type RestoreButtonProps = {
    href: string;
};

export default function RestoreButton({ href }: RestoreButtonProps) {
    return (
        <Button
            variant="ghost"
            size="icon"
            aria-label="Restaurer"
            onClick={() => router.patch(href, {}, { preserveScroll: true })}
        >
            <RotateCcw />
        </Button>
    );
}
