<?php

use App\Enums\CertificationKind;
use App\Enums\PublicationStatus;
use App\Models\Certification;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('the certifications page does not exist without a published entry', function () {
    Profile::factory()->create();
    Certification::factory()->create(['status' => PublicationStatus::Draft]);

    $this->get('/fr/certifications')->assertNotFound();
    $this->get('/sitemap.xml')->assertDontSee('/fr/certifications');
});

test('the certifications page lists published entries in the page language, flagging expired ones', function () {
    Profile::factory()->create();
    Certification::factory()->create([
        'name' => ['fr' => 'Développeur certifié', 'en' => 'Certified developer'],
        'issued_on' => '2023-03-01',
        'expires_on' => '2024-03-01',
    ]);
    // Plus ancienne : la page trie de la plus récente à la plus ancienne.
    Certification::factory()->course()->create(['issued_on' => '2020-01-01']);
    Certification::factory()->create(['status' => PublicationStatus::Draft]);

    $this->get('/en/certifications')->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/certifications')
        ->where('certificationsEnabled', true)
        ->has('certifications', 2)
        ->where('certifications.0.name', 'Certified developer')
        ->where('certifications.0.expired', true)
        ->where('certifications.1.kind', 'course'));

    $this->get('/sitemap.xml')->assertSee('/fr/certifications');
});

test('a certification can be created with a badge, updated and sent to the trash from the admin', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.certifications.store'), [
        'kind' => 'certification',
        'name' => ['fr' => 'AWS Cloud Practitioner', 'en' => 'AWS Cloud Practitioner'],
        'issuer' => 'Amazon Web Services',
        'issued_on' => '2025-06-01',
        'credential_url' => 'https://www.credly.com/badges/123',
        'sort_order' => 0,
        'badge' => UploadedFile::fake()->image('badge.png'),
    ])->assertRedirect(route('admin.certifications.index'));

    $certification = Certification::query()->sole();
    expect($certification->kind)->toBe(CertificationKind::Certification)
        ->and($certification->getFirstMediaUrl('badge'))->not->toBe('');

    $this->actingAs($user)->put(route('admin.certifications.update', $certification), [
        'kind' => 'course',
        'name' => ['fr' => 'Cours Laravel', 'en' => 'Laravel course'],
        'issuer' => 'Laracasts',
        'issued_on' => '2025-06-01',
        'status' => 'draft',
        'sort_order' => 1,
    ])->assertRedirect(route('admin.certifications.index'));

    expect($certification->refresh()->kind)->toBe(CertificationKind::Course)
        ->and($certification->status)->toBe(PublicationStatus::Draft);

    $this->actingAs($user)->delete(route('admin.certifications.destroy', $certification))->assertRedirect();
    $this->assertSoftDeleted($certification);
});

test('a certification needs dates in order and a valid verification link', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.certifications.store'), [
        'kind' => 'diploma',
        'issuer' => '',
        'issued_on' => now()->addMonth()->toDateString(),
        'expires_on' => now()->subYear()->toDateString(),
        'credential_url' => 'not-a-link',
    ])->assertSessionHasErrors(['kind', 'name.fr', 'issuer', 'issued_on', 'expires_on', 'credential_url']);
});

test('certifications are managed through the API and restorable from its trash', function () {
    Sanctum::actingAs(User::factory()->create());

    $id = $this->postJson(route('api.v1.certifications.store'), [
        'kind' => 'course',
        'name' => ['fr' => 'Formation', 'en' => 'Course'],
        'issuer' => 'OpenClassrooms',
        'issued_on' => '2024-01-15',
    ])->assertCreated()->assertJsonPath('status', 'published')->json('id');

    $this->deleteJson(route('api.v1.certifications.destroy', $id))->assertNoContent();
    $this->patchJson(route('api.v1.trash.restore', ['type' => 'certifications', 'id' => $id]))->assertSuccessful();

    $this->getJson(route('api.v1.certifications.index', ['kind' => 'course']))->assertJsonCount(1, 'data');
});
