<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\CvDownload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Téléchargements du CV depuis le site : liste filtrable et détail.
 */
class CvDownloadController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['country_code', 'locale'];

    public function index(Request $request): Response
    {
        return Inertia::render('admin/cv-downloads/index', [
            'downloads' => $this->paginateList(
                CvDownload::query()->with('jobProfile:id,label')->latest(),
                $request,
                ['email', 'city', 'country', 'referrer_host', 'utm_source', 'utm_campaign'],
                self::FILTERABLE,
            ),
            'filters' => $this->listFilters($request, self::FILTERABLE),
            'summary' => [
                'total' => CvDownload::query()->count(),
                'last_30_days' => CvDownload::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'with_email' => CvDownload::query()->whereNotNull('email')->count(),
            ],
            'countries' => CvDownload::query()
                ->whereNotNull('country_code')
                ->selectRaw('country_code, max(country) as country')
                ->groupBy('country_code')
                ->orderBy('country')
                ->get(),
        ]);
    }

    public function show(CvDownload $cvDownload): Response
    {
        return Inertia::render('admin/cv-downloads/show', [
            'download' => [
                ...$cvDownload->load('jobProfile:id,label')->toArray(),
                'origin' => $cvDownload->origin(),
            ],
        ]);
    }

    public function destroy(CvDownload $cvDownload): RedirectResponse
    {
        $cvDownload->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Téléchargement supprimé.')]);

        return to_route('admin.cv-downloads.index');
    }
}
