<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificationRequest;
use App\Models\Certification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Certifications et formations courtes de la page publique « Certifications ».
 */
class CertificationController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['kind', 'status'];

    public function index(Request $request): Response
    {
        return Inertia::render('admin/certifications/index', [
            'certifications' => $this->paginateList(
                Certification::query()->orderBy('sort_order')->orderByDesc('issued_on'),
                $request,
                ['name->fr', 'name->en', 'issuer'],
                self::FILTERABLE,
            ),
            'filters' => $this->listFilters($request, self::FILTERABLE),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/certifications/create');
    }

    public function store(CertificationRequest $request): RedirectResponse
    {
        $certification = Certification::query()->create($request->safe()->except('badge'));
        $this->syncBadge($certification, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification ajoutée.')]);

        return to_route('admin.certifications.index');
    }

    public function edit(Certification $certification): Response
    {
        return Inertia::render('admin/certifications/edit', [
            'certification' => [
                ...$certification->toArray(),
                'badge_url' => $certification->getFirstMediaUrl('badge') ?: null,
            ],
        ]);
    }

    public function update(CertificationRequest $request, Certification $certification): RedirectResponse
    {
        $certification->update($request->safe()->except('badge'));
        $this->syncBadge($certification, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification mise à jour.')]);

        return to_route('admin.certifications.index');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $certification->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification supprimée.')]);

        return to_route('admin.certifications.index');
    }

    public function destroyBadge(Certification $certification): RedirectResponse
    {
        $certification->clearMediaCollection('badge');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Badge retiré.')]);

        return back();
    }

    private function syncBadge(Certification $certification, CertificationRequest $request): void
    {
        if ($request->hasFile('badge')) {
            $certification->addMediaFromRequest('badge')->toMediaCollection('badge');
        }
    }
}
