<?php

namespace App\Http\Resources\Api\V1;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Partie publique de l'adresse, donnée à la création et jamais changée. */
            'token' => $this->token,
            /** Le lien à envoyer à l'entreprise (privé, jamais indexé). */
            'url' => route('job-applications.show', ['locale' => $this->locale, 'token' => $this->token]),
            'company' => $this->company,
            'contact_name' => $this->contact_name,
            'position' => $this->position,
            /** Profil métier dont le CV est proposé au téléchargement ; null : pas de CV. */
            'job_profile_id' => $this->job_profile_id,
            /** Langue de la page et du CV : `fr` ou `en`. */
            'locale' => $this->locale,
            /** Mot d'accompagnement, paragraphes séparés par une ligne vide. */
            'intro' => $this->intro,
            'job_posting_url' => $this->job_posting_url,
            'job_posting_text' => $this->job_posting_text,
            /** Notes privées, jamais affichées sur la page. */
            'notes' => $this->notes,
            /** `draft` (lien inactif), `active` (en ligne) ou `archived` (lien coupé). */
            'status' => $this->status,
            /** La page s'ouvre-t-elle avec son lien (en ligne et pas expirée) ? */
            'is_open' => $this->isOpen(),
            /** Après cette date (AAAA-MM-JJ), le lien ne répond plus ; null : sans limite. */
            'expires_on' => $this->expires_on?->toDateString(),
            /**
             * Projets montrés, dans l'ordre.
             *
             * @var list<int>
             */
            'project_ids' => $this->whenLoaded('projects', fn (): array => $this->projects->modelKeys(), []),
            /** Consultations (une par visiteur et par jour, sans les robots ni mes propres visites). */
            'views_count' => $this->views_count,
            'cv_downloads_count' => $this->cv_downloads_count,
            'first_viewed_at' => $this->first_viewed_at,
            'last_viewed_at' => $this->last_viewed_at,
            /** Client IA qui a préparé la page via le serveur MCP (« Claude Code »…), sinon null. */
            'drafted_by' => $this->drafted_by,
            'created_at' => $this->created_at,
        ];
    }
}
