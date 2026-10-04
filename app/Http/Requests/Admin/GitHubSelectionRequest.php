<?php

namespace App\Http\Requests\Admin;

use App\Services\GitHubActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GitHubSelectionRequest extends FormRequest
{
    /**
     * Les dépôts choisis (« propriétaire/nom »), dans l'ordre d'affichage. Seuls les
     * dépôts connus de la dernière synchronisation sont acceptés ; une liste vide
     * revient au choix automatique.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $available = collect(app(GitHubActivity::class)->cached()['available'] ?? [])->pluck('full_name')->all();

        return [
            'repositories' => ['present', 'array', 'max:'.GitHubActivity::MAX_SELECTED],
            'repositories.*' => ['string', 'distinct', Rule::in($available)],
        ];
    }
}
