<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommercialDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentTemplateRequest;
use App\Models\DocumentTemplate;
use App\Services\CommercialDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le design des devis, proformas et factures : un modèle par type, édité à côté d'un aperçu
 * en direct sur un document d'exemple (les réglages non enregistrés sont prévisualisés).
 */
class DocumentTemplateController extends Controller
{
    public function __construct(private readonly CommercialDocuments $documents) {}

    public function edit(CommercialDocumentType $type): Response
    {
        $template = DocumentTemplate::for($type);

        return Inertia::render('admin/document-templates/edit', [
            'type' => $type->value,
            'template' => [
                ...$template->only(['layout', 'primary_color', 'accent_color', 'font', 'show_logo', 'show_bank_details', 'show_signature']),
                'title' => $template->getTranslations('title'),
                'intro' => $template->getTranslations('intro'),
                'footer' => $template->getTranslations('footer'),
                'logo_url' => $template->getFirstMediaUrl('logo') ?: null,
            ],
            'fonts' => DocumentTemplate::FONTS,
        ]);
    }

    public function update(DocumentTemplateRequest $request, CommercialDocumentType $type): RedirectResponse
    {
        $template = DocumentTemplate::for($type);
        $template->update($request->templateData());

        if ($request->boolean('remove_logo')) {
            $template->clearMediaCollection('logo');
        }

        if ($request->hasFile('logo')) {
            $template->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Modèle enregistré.')]);

        return back();
    }

    /** L'aperçu PDF des réglages en cours (non enregistrés), sur un document d'exemple. */
    public function preview(DocumentTemplateRequest $request, CommercialDocumentType $type): HttpResponse
    {
        $saved = DocumentTemplate::for($type);
        $template = (new DocumentTemplate(['type' => $type]))->fill($request->templateData());
        $logo = $request->logoDataUri() ?? ($request->boolean('remove_logo') ? null : $saved->logoDataUri());

        $pdf = $this->documents->pdf(
            $this->documents->sample($type, $request->validated('preview_locale') ?? 'fr'),
            $template,
            $logo,
        );

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="apercu.pdf"']);
    }
}
