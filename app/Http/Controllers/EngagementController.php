<?php

namespace App\Http\Controllers;

use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Http\Requests\EngagementRequest;
use App\Jobs\SendEngagementMails;
use App\Models\Engagement;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class EngagementController extends Controller
{
    public function store(EngagementRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('website');

        // Sans montant, il n'y a pas de budget : on ne garde ni type, ni devise, ni période.
        if (blank($data['budget_amount'] ?? null)) {
            $data = [...$data, 'budget_type' => null, 'budget_amount' => null, 'budget_currency' => null, 'budget_period' => null];
        } elseif (($data['budget_type'] ?? 'fixed') !== 'period') {
            $data = [...$data, 'budget_type' => 'fixed', 'budget_period' => null];
        }

        $engagement = Engagement::query()->create([
            ...$data,
            'locale' => app()->getLocale(),
            'status' => EngagementStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        SendEngagementMails::dispatch($engagement->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $engagement->type === EngagementType::Hiring
                ? __('Merci ! Votre CV arrive par email.')
                : __('Merci ! Je reviens vers vous très vite.'),
        ]);

        return back();
    }
}
