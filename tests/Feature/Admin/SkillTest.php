<?php

use App\Models\Domain;
use App\Models\Skill;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.skills.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a skill', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.skills.store'), [
        'domain_id' => $domain->id,
        'name' => ['fr' => 'Laravel', 'en' => 'Laravel'],
        'sort_order' => 1,
    ]);

    $response->assertRedirect(route('admin.skills.index'));
    $this->assertDatabaseHas('skills', ['domain_id' => $domain->id]);
});

test('authenticated users can update a skill', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.skills.update', $skill), [
        'domain_id' => $skill->domain_id,
        'name' => ['fr' => 'Nouveau', 'en' => 'New'],
        'sort_order' => 3,
    ]);

    $response->assertRedirect(route('admin.skills.index'));
    expect($skill->fresh()->getTranslation('name', 'fr'))->toBe('Nouveau');
});

test('authenticated users can delete a skill', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->create();

    $this->actingAs($user)->delete(route('admin.skills.destroy', $skill))
        ->assertRedirect(route('admin.skills.index'));

    $this->assertSoftDeleted($skill);
});

test('creating a skill requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.skills.store'), []);

    $response->assertSessionHasErrors(['domain_id', 'name.fr', 'name.en']);
});
