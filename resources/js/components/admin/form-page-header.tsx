import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type FormPageHeaderProps = {
    title: string;
    description?: string;
    backHref: Parameters<typeof Link>[0]['href'];
    backLabel: string;
};

/** En-tête des pages de création / modification : titre et retour vers la liste. */
export default function FormPageHeader({
    title,
    description,
    backHref,
    backLabel,
}: FormPageHeaderProps) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-3">
            <Heading title={title} description={description} />
            <Button variant="outline" asChild>
                <Link href={backHref}>
                    <ArrowLeft data-icon="inline-start" />
                    {backLabel}
                </Link>
            </Button>
        </div>
    );
}
