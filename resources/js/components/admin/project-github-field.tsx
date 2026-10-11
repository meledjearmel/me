import FormSelect from '@/components/admin/form-select';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';

export type GitHubRepositoryOption = {
    full_name: string;
    private: boolean;
    url: string;
};

/**
 * Dépôt GitHub lié au projet, pris dans la dernière synchronisation (privés compris) :
 * relie le projet à son activité sur la page À propos et sur sa propre page.
 */
export default function ProjectGitHubField({
    repositories,
    defaultValue,
    error,
}: {
    repositories: GitHubRepositoryOption[];
    defaultValue?: string | null;
    error?: string;
}) {
    // Un dépôt lié mais absent de la dernière synchronisation reste proposé.
    const options =
        defaultValue &&
        !repositories.some(
            (repository) => repository.full_name === defaultValue,
        )
            ? [
                  { full_name: defaultValue, private: false, url: '' },
                  ...repositories,
              ]
            : repositories;

    return (
        <Field data-invalid={!!error}>
            <FieldLabel htmlFor="github_repository">
                Dépôt GitHub lié
            </FieldLabel>
            <FormSelect
                id="github_repository"
                name="github_repository"
                defaultValue={defaultValue ?? ''}
                aria-invalid={!!error}
            >
                <option value="">Aucun</option>
                {options.map((repository) => (
                    <option
                        key={repository.full_name}
                        value={repository.full_name}
                    >
                        {repository.full_name}
                        {repository.private ? ' (privé)' : ''}
                    </option>
                ))}
            </FormSelect>
            <FieldDescription>
                Montre l'activité du dépôt sur la page du projet et renvoie vers
                le projet depuis la page À propos. Un dépôt privé n'est jamais
                nommé publiquement.
                {repositories.length === 0 &&
                    ' Aucun dépôt synchronisé : lance une synchronisation depuis « Dépôts GitHub ».'}
            </FieldDescription>
            <FieldError>{error}</FieldError>
        </Field>
    );
}
