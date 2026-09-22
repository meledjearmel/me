<?php

use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.experiences.index'))->assertRedirect(route('login'));
});

test('authenticated users can create an experience with highlights', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.experiences.store'), [
        'company' => 'Acme',
        'role' => ['fr' => 'Ingénieur', 'en' => 'Engineer'],
        'start_date' => '2022-01-01',
        'sort_order' => 1,
        'highlights' => [
            ['text' => ['fr' => 'Puce 1', 'en' => 'Bullet 1'], 'sort_order' => 0],
        ],
    ]);

    $response->assertRedirect(route('admin.experiences.index'));
    $experience = Experience::query()->where('company', 'Acme')->firstOrFail();
    expect($experience->highlights)->toHaveCount(1);
});

test('authenticated users can update an experience and sync highlights', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create();
    $keptHighlight = ExperienceHighlight::factory()->create(['experience_id' => $experience->id]);
    $removedHighlight = ExperienceHighlight::factory()->create(['experience_id' => $experience->id]);

    $response = $this->actingAs($user)->put(route('admin.experiences.update', $experience), [
        'company' => $experience->company,
        'role' => $experience->getTranslations('role'),
        'start_date' => $experience->start_date->format('Y-m-d'),
        'sort_order' => $experience->sort_order,
        'highlights' => [
            ['id' => $keptHighlight->id, 'text' => ['fr' => 'Modifiée', 'en' => 'Updated'], 'sort_order' => 0],
            ['text' => ['fr' => 'Nouvelle', 'en' => 'New'], 'sort_order' => 1],
        ],
    ]);

    $response->assertRedirect(route('admin.experiences.index'));
    expect($experience->highlights()->count())->toBe(2);
    $this->assertSoftDeleted($removedHighlight);
    expect($keptHighlight->fresh()->getTranslation('text', 'fr'))->toBe('Modifiée');
});

test('authenticated users can delete an experience', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create();

    $this->actingAs($user)->delete(route('admin.experiences.destroy', $experience))
        ->assertRedirect(route('admin.experiences.index'));

    $this->assertSoftDeleted($experience);
});

test('creating an experience requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.experiences.store'), []);

    $response->assertSessionHasErrors(['company', 'role.fr', 'role.en', 'start_date']);
});
