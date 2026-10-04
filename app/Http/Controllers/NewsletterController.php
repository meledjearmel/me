<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscriptionRequest;
use App\Mail\NewsletterConfirmationMail;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Newsletter du blog : inscription confirmée par email, puis désinscription par le
 * lien présent dans chaque envoi (le jeton tient lieu d'identification).
 */
class NewsletterController extends Controller
{
    /**
     * La réponse est la même que l'adresse soit nouvelle ou déjà inscrite : le
     * formulaire ne révèle pas qui est abonné.
     */
    public function store(NewsletterSubscriptionRequest $request): RedirectResponse
    {
        abort_unless(SiteSetting::current()->blog_enabled, HttpResponse::HTTP_NOT_FOUND);

        $subscriber = Subscriber::query()->firstOrNew(['email' => mb_strtolower($request->validated('email'))]);

        if (! $subscriber->isActive()) {
            $subscriber->fill(['locale' => app()->getLocale(), 'ip_address' => $request->ip()]);
            $subscriber->forceFill(['confirmed_at' => null, 'unsubscribed_at' => null])->save();

            Mail::to($subscriber->email)->queue(new NewsletterConfirmationMail($subscriber));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Presque fini : confirmez votre inscription depuis l’email que je viens de vous envoyer.')]);

        return back();
    }

    public function confirm(string $locale, string $token): RedirectResponse
    {
        $subscriber = $this->find($token);

        if ($subscriber->unsubscribed_at === null && $subscriber->confirmed_at === null) {
            $subscriber->forceFill(['confirmed_at' => now()])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inscription confirmée : vous recevrez les prochains articles.')]);

        return redirect("/{$locale}/blog");
    }

    public function show(string $locale, string $token): Response
    {
        $subscriber = $this->find($token);

        return Inertia::render('public/newsletter-unsubscribe', [
            'token' => $token,
            'email' => $subscriber->email,
            'unsubscribed' => $subscriber->unsubscribed_at !== null,
        ]);
    }

    /**
     * Aussi appelée en un clic par les messageries (en-tête List-Unsubscribe-Post),
     * sans jeton CSRF : la route en est exemptée.
     */
    public function destroy(Request $request, string $locale, string $token): RedirectResponse|HttpResponse
    {
        $subscriber = $this->find($token);

        if ($subscriber->unsubscribed_at === null) {
            $subscriber->forceFill(['unsubscribed_at' => now()])->save();
        }

        if (! $request->header('X-Inertia')) {
            return response()->noContent();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vous êtes désinscrit de la newsletter.')]);

        return back();
    }

    private function find(string $token): Subscriber
    {
        return Subscriber::query()->where('token', $token)->firstOrFail();
    }
}
