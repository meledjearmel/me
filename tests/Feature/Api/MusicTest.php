<?php

use App\Models\MusicGenre;
use App\Models\Track;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** Une copie jetable du MP3 silencieux des tests, acceptée par la validation et par la collection média. */
$sampleAudio = function (): UploadedFile {
    $path = tempnam(sys_get_temp_dir(), 'track').'.mp3';
    copy(base_path('tests/fixtures/silence.mp3'), $path);

    return new UploadedFile($path, 'silence.mp3', 'audio/mpeg', null, true);
};

beforeEach(function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());
});

test('un registre se crée, se lit, se modifie et se supprime', function () {
    $payload = [
        'key' => 'jazz',
        'label' => ['fr' => 'Jazz', 'en' => 'Jazz'],
        'sort_order' => 1,
    ];

    $id = $this->postJson(route('api.v1.music-genres.store'), $payload)
        ->assertCreated()
        ->assertJsonPath('label.fr', 'Jazz')
        ->json('id');

    $this->getJson(route('api.v1.music-genres.show', $id))
        ->assertOk()
        ->assertJsonPath('key', 'jazz');

    $this->putJson(route('api.v1.music-genres.update', $id), [...$payload, 'label' => ['fr' => 'Jazz doux', 'en' => 'Smooth jazz']])
        ->assertOk()
        ->assertJsonPath('label.fr', 'Jazz doux');

    $this->deleteJson(route('api.v1.music-genres.destroy', $id))->assertNoContent();
    expect(MusicGenre::query()->count())->toBe(0);
});

test('un registre refuse une clé déjà utilisée', function () {
    $genre = MusicGenre::factory()->create();

    $this->postJson(route('api.v1.music-genres.store'), [
        'key' => $genre->key,
        'label' => ['fr' => 'A', 'en' => 'A'],
    ])->assertUnprocessable()->assertJsonValidationErrors('key');
});

test('la liste des registres est paginée', function () {
    MusicGenre::factory()->count(3)->create();

    $this->getJson(route('api.v1.music-genres.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('une piste se crée avec son fichier audio, se modifie et se supprime', function () use ($sampleAudio) {
    $genre = MusicGenre::factory()->create();

    $id = $this->post(route('api.v1.tracks.store'), [
        'music_genre_id' => $genre->id,
        'title' => 'Midnight',
        'artist' => 'Someone',
        'audio' => $sampleAudio(),
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('title', 'Midnight')
        ->json('id');

    $show = $this->getJson(route('api.v1.tracks.show', $id))->assertOk();
    expect($show->json('audio_url'))->not->toBeNull();

    $this->putJson(route('api.v1.tracks.update', $id), [
        'music_genre_id' => $genre->id,
        'title' => 'Renamed',
    ])->assertOk()->assertJsonPath('title', 'Renamed');

    $this->deleteJson(route('api.v1.tracks.destroy', $id))->assertNoContent();
    expect(Track::query()->count())->toBe(0);
});

test('une piste exige un fichier audio à la création', function () {
    $genre = MusicGenre::factory()->create();

    $this->postJson(route('api.v1.tracks.store'), [
        'music_genre_id' => $genre->id,
        'title' => 'Midnight',
    ])->assertUnprocessable()->assertJsonValidationErrors('audio');
});

test('les pistes se filtrent par registre', function () {
    $genre = MusicGenre::factory()->create();
    Track::factory()->for($genre, 'genre')->create();
    Track::factory()->create();

    $this->getJson(route('api.v1.tracks.index', ['music_genre_id' => $genre->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.music_genre_id', $genre->id);
});
