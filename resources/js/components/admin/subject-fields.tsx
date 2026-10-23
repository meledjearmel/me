import CheckboxGroup from '@/components/admin/checkbox-group';
import { FieldError } from '@/components/ui/field';
import type { Education, Experience, Project } from '@/types';

export type SubjectOptions = {
    projects: Pick<Project, 'id' | 'title'>[];
    experiences: Pick<Experience, 'id' | 'role' | 'company'>[];
    educations: Pick<Education, 'id' | 'degree' | 'institution'>[];
};

/**
 * Ce dont parle un avis : plusieurs projets, expériences et formations possibles.
 * Le champ caché vide envoie une liste vide quand rien n'est coché, pour retirer
 * les rattachements existants.
 */
export default function SubjectFields({
    projects,
    experiences,
    educations,
    selected,
    errors,
}: SubjectOptions & {
    selected?: {
        project_ids: number[];
        experience_ids: number[];
        education_ids: number[];
    };
    errors: Record<string, string | undefined>;
}) {
    const groups = [
        {
            name: 'project_ids',
            label: 'Projets liés',
            options: projects.map((project) => ({
                id: project.id,
                label: project.title.fr,
            })),
        },
        {
            name: 'experience_ids',
            label: 'Expériences liées',
            options: experiences.map((experience) => ({
                id: experience.id,
                label: `${experience.role.fr} — ${experience.company}`,
            })),
        },
        {
            name: 'education_ids',
            label: 'Formations liées',
            options: educations.map((education) => ({
                id: education.id,
                label: `${education.degree.fr} — ${education.institution}`,
            })),
        },
    ] as const;

    return (
        <>
            {groups.map((group) =>
                group.options.length === 0 ? null : (
                    <div key={group.name} className="grid gap-2">
                        <input
                            type="hidden"
                            name={`${group.name}[]`}
                            value=""
                        />
                        <CheckboxGroup
                            label={group.label}
                            name={group.name}
                            options={group.options}
                            defaultSelectedIds={selected?.[group.name] ?? []}
                        />
                        <FieldError>
                            {errors[group.name] ?? errors[`${group.name}.0`]}
                        </FieldError>
                    </div>
                ),
            )}
        </>
    );
}
