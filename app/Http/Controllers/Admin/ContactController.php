<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/contacts/index', [
            'contacts' => Contact::query()->latest()->get(),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        if ($contact->status === ContactStatus::New) {
            $contact->update(['status' => ContactStatus::Read]);
        }

        return Inertia::render('admin/contacts/edit', [
            'contact' => $contact,
        ]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact mis à jour.')]);

        return to_route('admin.contacts.index');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact supprimé.')]);

        return to_route('admin.contacts.index');
    }
}
