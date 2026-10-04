<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Abonnés à la newsletter du blog : liste filtrable par état et suppression.
 */
class SubscriberController extends Controller
{
    use PaginatesAdminLists;

    /** @var list<string> */
    private const array FILTERABLE = ['locale'];

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $query = Subscriber::query()
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($status === 'pending', fn ($query) => $query->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($status === 'unsubscribed', fn ($query) => $query->whereNotNull('unsubscribed_at'))
            ->latest();

        return Inertia::render('admin/subscribers/index', [
            'subscribers' => $this->paginateList($query, $request, ['email'], self::FILTERABLE),
            'filters' => [
                ...$this->listFilters($request, self::FILTERABLE),
                'status' => is_string($status) ? $status : '',
            ],
            'summary' => [
                'active' => Subscriber::query()->active()->count(),
                'pending' => Subscriber::query()->whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
                'unsubscribed' => Subscriber::query()->whereNotNull('unsubscribed_at')->count(),
            ],
        ]);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Abonné supprimé.')]);

        return to_route('admin.subscribers.index');
    }
}
