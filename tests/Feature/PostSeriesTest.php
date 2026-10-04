<?php

use App\Models\Post;
use App\Models\PostSeries;
use App\Models\Profile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/** @return array<string, mixed> */
function seriesPostPayload(array $extra = []): array
{
    return [
        'title' => ['fr' => 'Partie', 'en' => 'Part'],
        'slug' => 'partie-'.fake()->unique()->numberBetween(1, 9999),
        'body' => ['fr' => '<p>Contenu</p>'],
        'status' => 'published',
        ...$extra,
    ];
}

test('naming a series in the post form creates it once and places the post in it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.posts.store'), seriesPostPayload(['series' => 'Laravel de A à Z', 'series_position' => 1]))->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('admin.posts.store'), seriesPostPayload(['series' => 'Laravel de A à Z', 'series_position' => 2]))->assertSessionHasNoErrors();

    $series = PostSeries::query()->sole();
    expect($series->getTranslation('name', 'fr'))->toBe('Laravel de A à Z')
        ->and($series->posts()->orderBy('series_position')->pluck('series_position')->all())->toBe([1, 2]);
});

test('a series needs a part number, and emptying the name takes the post out of it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.posts.store'), seriesPostPayload(['series' => 'Sans numéro']))
        ->assertSessionHasErrors('series_position');

    $post = Post::factory()->for(PostSeries::factory(), 'series')->create(['series_position' => 1]);

    $this->actingAs($user)->put(route('admin.posts.update', $post), seriesPostPayload(['slug' => $post->slug, 'series' => '']))->assertSessionHasNoErrors();

    expect($post->refresh()->post_series_id)->toBeNull()
        ->and($post->series_position)->toBeNull();
});

test('a post page lists the published parts of its series in order', function () {
    Profile::factory()->create();
    $series = PostSeries::factory()->create(['name' => ['fr' => 'Ma série', 'en' => 'My series']]);
    Post::factory()->for($series, 'series')->create(['slug' => 'deux', 'series_position' => 2, 'title' => ['fr' => 'Deux', 'en' => 'Two']]);
    Post::factory()->for($series, 'series')->create(['slug' => 'un', 'series_position' => 1, 'title' => ['fr' => 'Un', 'en' => 'One']]);
    Post::factory()->for($series, 'series')->draft()->create(['slug' => 'trois', 'series_position' => 3]);

    $this->get('/en/blog/deux')->assertInertia(fn ($page) => $page
        ->where('series.name', 'My series')
        ->where('series.parts', [
            ['slug' => 'un', 'title' => 'One', 'position' => 1, 'current' => false],
            ['slug' => 'deux', 'title' => 'Two', 'position' => 2, 'current' => true],
        ]));

    $this->get('/fr/blog/'.Post::factory()->create()->slug)->assertInertia(fn ($page) => $page->where('series', null));
});

test('a series can be renamed and deleted from the admin, its posts staying published', function () {
    $user = User::factory()->create();
    $series = PostSeries::factory()->create();
    $post = Post::factory()->for($series, 'series')->create(['series_position' => 1]);

    $this->actingAs($user)->get(route('admin.post-series.index'))->assertOk();
    $this->actingAs($user)->put(route('admin.post-series.update', $series), ['name' => ['fr' => 'Nom FR', 'en' => 'EN name']])
        ->assertRedirect(route('admin.post-series.index'));
    expect($series->refresh()->getTranslation('name', 'en'))->toBe('EN name');

    $this->actingAs($user)->delete(route('admin.post-series.destroy', $series))->assertRedirect();

    $this->assertModelMissing($series);
    expect($post->refresh()->post_series_id)->toBeNull()
        ->and($post->isPublished())->toBeTrue();
});

test('the API exposes the series of a post and can rename a series', function () {
    Sanctum::actingAs(User::factory()->create());
    $series = PostSeries::factory()->create(['name' => ['fr' => 'Série', 'en' => 'Series']]);
    $post = Post::factory()->for($series, 'series')->create(['series_position' => 3]);

    $this->getJson(route('api.v1.posts.show', $post))
        ->assertJsonPath('series', 'Série')
        ->assertJsonPath('series_position', 3);

    $this->putJson(route('api.v1.post-series.update', $series), ['name' => ['fr' => 'Série', 'en' => 'Renamed']])
        ->assertOk()
        ->assertJsonPath('name.en', 'Renamed')
        ->assertJsonPath('posts_count', 1);
});
