<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CommercialDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentTemplateRequest;
use App\Http\Resources\Api\V1\DocumentTemplateResource;
use App\Models\DocumentTemplate;
use App\Services\CommercialDocuments;
use Illuminate\Http\Response;

/**
 * @tags Devis et factures
 */
class DocumentTemplateController extends Controller
{
    public function __construct(private readonly CommercialDocuments $documents) {}

    /**
     * Modèle d'un type de document
     *
     * Le design appliqué à tous les PDF du type : mise en page, couleurs, police, logo, textes
     * et blocs affichés. `type` : `quote`, `proforma` ou `invoice`.
     */
    public function show(CommercialDocumentType $type): DocumentTemplateResource
    {
        return new DocumentTemplateResource(DocumentTemplate::for($type));
    }

    /**
     * Modifier un modèle
     *
     * En `multipart/form-data` pour envoyer un logo (`logo`, PNG ou JPEG, 2 Mo) ; `remove_logo`
     * à `true` le retire. Les textes vides reprennent les valeurs par défaut.
     */
    public function update(DocumentTemplateRequest $request, CommercialDocumentType $type): DocumentTemplateResource
    {
        $template = DocumentTemplate::for($type);
        $template->update($request->templateData());

        if ($request->boolean('remove_logo')) {
            $template->clearMediaCollection('logo');
        }

        if ($request->hasFile('logo')) {
            $template->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return new DocumentTemplateResource($template->refresh());
    }

    /**
     * Prévisualiser un modèle
     *
     * Le PDF d'un document d'exemple avec les réglages envoyés, sans les enregistrer (mêmes champs
     * que la modification, plus `preview_locale` : `fr` ou `en`).
     */
    public function preview(DocumentTemplateRequest $request, CommercialDocumentType $type): Response
    {
        $saved = DocumentTemplate::for($type);
        $template = (new DocumentTemplate(['type' => $type]))->fill($request->templateData());
        $logo = $request->logoDataUri() ?? ($request->boolean('remove_logo') ? null : $saved->logoDataUri());

        return response(
            $this->documents->pdf($this->documents->sample($type, $request->validated('preview_locale') ?? 'fr'), $template, $logo),
            200,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="apercu.pdf"'],
        );
    }
}
