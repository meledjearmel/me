<?php

use App\Models\MusicGenre;
use App\Models\Profile;
use App\Models\Track;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Une copie jetable du MP3 du dépôt : l'envoi déplace le fichier source. */
function sampleAudioPath(): string
{
    $path = tempnam(sys_get_temp_dir(), 'track').'.mp3';
    copy(public_path('audio/journey.mp3'), $path);

    return $path;
}

function sampleAudio(): UploadedFile
{
    return new UploadedFile(sampleAudioPath(), 'journey.mp3', 'audio/mpeg', null, true);
}

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.music-genres.index'))->assertRedirect(route('login'));
    $this->get(route('admin.tracks.index'))->assertRedirect(route('login'));
});

test('authenticated users can create and update a genre', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.music-genres.store'), [
        'key' => 'jazz',
        'label' => ['fr' => 'Jazz', 'en' => 'Jazz'],
        'sort_order' => 1,
    ])->assertRedirect(route('admin.music-genres.index'));

    $genre = MusicGenre::query()->where('key', 'jazz')->firstOrFail();

    $this->actingAs($user)->put(route('admin.music-genres.update', $genre), [
        'key' => 'jazz',
        'label' => ['fr' => 'Jazz doux', 'en' => 'Smooth jazz'],
        'sort_order' => 2,
    ])->assertRedirect(route('admin.music-genres.index'));

    expect($genre->fresh()->getTranslation('label', 'fr'))->toBe('Jazz doux');
});

test('creating a genre requires the mandatory fields', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.music-genres.store'), [])
        ->assertSessionHasErrors(['key', 'label.fr', 'label.en']);
});

test('authenticated users can create a track with its audio file', function () {
    $genre = MusicGenre::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('admin.tracks.store'), [
        'music_genre_id' => $genre->id,
        'title' => 'Midnight',
        'artist' => 'Someone',
        'audio' => sampleAudio(),
    ])->assertRedirect(route('admin.tracks.index'));

    $track = Track::query()->where('title', 'Midnight')->firstOrFail();
    expect($track->audioUrl())->not->toBeNull();
});

test('a track requires an audio file when created', function () {
    $genre = MusicGenre::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('admin.tracks.store'), [
        'music_genre_id' => $genre->id,
        'title' => 'Midnight',
    ])->assertSessionHasErrors('audio');
});

test('a track can be updated without a new audio file', function () {
    $track = Track::factory()->create();

    $this->actingAs(User::factory()->create())->put(route('admin.tracks.update', $track), [
        'music_genre_id' => $track->music_genre_id,
        'title' => 'Renamed',
    ])->assertRedirect(route('admin.tracks.index'));

    expect($track->fresh()->title)->toBe('Renamed');
});

test('the public playlist only exposes genres with playable tracks', function () {
    $withAudio = MusicGenre::factory()->create(['key' => 'lofi']);
    $track = Track::factory()->for($withAudio, 'genre')->create();
    $track->addMedia(sampleAudioPath())->toMediaCollection('audio');

    $silent = MusicGenre::factory()->create(['key' => 'silent']);
    Track::factory()->for($silent, 'genre')->create();
    MusicGenre::factory()->create(['key' => 'empty']);

    Profile::factory()->create();

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->has('playlist', 1)
        ->where('playlist.0.key', 'lofi')
        ->has('playlist.0.tracks', 1));
});
