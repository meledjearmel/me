<?php

use App\Models\Education;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.educations.index'))->assertRedirect(route('login'));
});

test('authenticated users can create an education', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.educations.store'), [
        'institution' => 'Université',
        'degree' => ['fr' => 'Master', 'en' => 'Master'],
        'field' => ['fr' => 'Informatique', 'en' => 'Computer Science'],
        'start_date' => '2018-09-01',
        'sort_order' => 1,
    ]);

    $response->assertRedirect(route('admin.educations.index'));
    $this->assertDatabaseHas('educations', ['institution' => 'Université']);
});

test('authenticated users can update an education', function () {
    $user = User::factory()->create();
    $education = Education::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.educations.update', $education), [
        'institution' => 'Nouvelle université',
        'degree' => $education->getTranslations('degree'),
        'field' => $education->getTranslations('field'),
        'start_date' => $education->start_date->format('Y-m-d'),
        'sort_order' => $education->sort_order,
    ]);

    $response->assertRedirect(route('admin.educations.index'));
    expect($education->fresh()->institution)->toBe('Nouvelle université');
});

test('authenticated users can delete an education', function () {
    $user = User::factory()->create();
    $education = Education::factory()->create();

    $this->actingAs($user)->delete(route('admin.educations.destroy', $education))
        ->assertRedirect(route('admin.educations.index'));

    $this->assertSoftDeleted($education);
});

test('creating an education requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.educations.store'), []);

    $response->assertSessionHasErrors([
        'institution', 'degree.fr', 'degree.en', 'field.fr', 'field.en', 'start_date',
    ]);
});
