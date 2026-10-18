import { Bot } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

/**
 * Brouillon rédigé par une IA via le serveur MCP (« IA · Claude Code ») : à relire
 * avant publication. Rien n'est affiché une fois le contenu publié.
 */
export default function AiDraftBadge({
    draftedBy,
    status,
}: {
    draftedBy?: string | null;
    status: string;
}) {
    if (!draftedBy || status !== 'draft') {
        return null;
    }

    return (
        <Badge
            variant="outline"
            className="ml-2"
            title="Brouillon rédigé par une IA, à relire"
        >
            <Bot /> IA · {draftedBy}
        </Badge>
    );
}
