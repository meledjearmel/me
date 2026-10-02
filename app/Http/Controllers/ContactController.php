<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Requests\ContactRequest;
use App\Jobs\SendPushNotification;
use App\Mail\ContactReceivedMail;
use App\Models\Contact;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $contact = Contact::query()->create([
            ...$request->safe()->except('website'),
            'status' => ContactStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        SendPushNotification::dispatch('Nouveau message', $contact->subject, ['type' => 'contact', 'id' => (string) $contact->id]);

        $ownerEmail = Profile::query()->value('email');

        if ($ownerEmail) {
            Mail::to($ownerEmail)->queue(new ContactReceivedMail($contact));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Message envoyé, merci !')]);

        return back();
    }
}
