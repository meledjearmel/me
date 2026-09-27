<?php

use App\Models\Contact;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Collection;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.trash.index'))->assertRedirect(route('login'));
});

test('trashed records from several models appear in the list', function () {
    $user = User::factory()->create();

    $contact = Contact::factory()->create();
    $contact->delete();

    $testimonial = Testimonial::factory()->create();
    $testimonial->delete();

    $active = Contact::factory()->create();

    $this->actingAs($user)->get(route('admin.trash.index'))->assertInertia(fn ($page) => $page
        ->component('admin/trash/index')
        ->has('items.data', 2)
        ->where('items.data', function (Collection $items) use ($contact, $testimonial, $active) {
            $titles = $items->pluck('title');

            return $titles->contains($contact->name.' — '.$contact->subject)
                && $titles->contains($testimonial->author_name)
                && ! $titles->contains($active->name.' — '.$active->subject);
        })
    );
});

test('the type filter narrows the list to one model', function () {
    $user = User::factory()->create();

    $contact = Contact::factory()->create();
    $contact->delete();

    $testimonial = Testimonial::factory()->create();
    $testimonial->delete();

    $this->actingAs($user)->get(route('admin.trash.index', ['type' => 'contacts']))->assertInertia(fn ($page) => $page
        ->component('admin/trash/index')
        ->has('items.data', 1)
        ->where('items.data.0.type', 'contacts')
    );
});

test('search matches on the item title', function () {
    $user = User::factory()->create();

    $contact = Contact::factory()->create(['name' => 'Zenitram Pixel']);
    $contact->delete();

    $other = Contact::factory()->create(['name' => 'Someone Else']);
    $other->delete();

    $this->actingAs($user)->get(route('admin.trash.index', ['search' => 'zenitram']))->assertInertia(fn ($page) => $page
        ->component('admin/trash/index')
        ->has('items.data', 1)
    );
});

test('a trashed record can be restored', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $contact->delete();

    $this->actingAs($user)->patch(route('admin.trash.restore', ['type' => 'contacts', 'id' => $contact->id]))
        ->assertRedirect(route('admin.trash.index'));

    $this->assertNotSoftDeleted($contact);
});

test('a trashed record can be permanently deleted', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $contact->delete();

    $this->actingAs($user)->delete(route('admin.trash.force-delete', ['type' => 'contacts', 'id' => $contact->id]))
        ->assertRedirect(route('admin.trash.index'));

    $this->assertModelMissing($contact);
});

test('restoring an unknown type 404s', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('admin.trash.restore', ['type' => 'not-a-type', 'id' => 1]))
        ->assertNotFound();
});

test('restoring a record that is not trashed 404s', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)->patch(route('admin.trash.restore', ['type' => 'contacts', 'id' => $contact->id]))
        ->assertNotFound();
});
