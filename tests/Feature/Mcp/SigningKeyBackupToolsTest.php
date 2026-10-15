<?php

use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\GetSigningKeyBackupTool;
use App\Mcp\Tools\PrepareSigningKeyBackupTool;
use App\Models\SigningKeyBackup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->server = PortfolioServer::actingAs(User::factory()->create());
});

function encryptedBlob(): string
{
    return 'age-encryption.org/v1 '.bin2hex(random_bytes(64));
}

/**
 * @return array{filename: string, sha256: string, key_fingerprint: string, encryption: string}
 */
function signingKeyBackupArguments(string $blob): array
{
    return [
        'filename' => 'me-admin-release.age',
        'sha256' => hash('sha256', $blob),
        'key_fingerprint' => strtolower(hash('sha256', 'certificate')),
        'encryption' => 'age',
    ];
}

test('prepare-signing-key-backup registers the backup and gives a signed upload command', function () {
    $this->server->tool(PrepareSigningKeyBackupTool::class, signingKeyBackupArguments(encryptedBlob()))
        ->assertOk()
        ->assertSee(['upload_url', 'curl --fail-with-body -T me-admin-release.age', 'signature=']);

    expect(SigningKeyBackup::query()->sole())
        ->has_file->toBeFalse()
        ->key_fingerprint->toBe(SigningKeyBackup::normalizeFingerprint(hash('sha256', 'certificate')))
        ->and(SigningKeyBackup::current())->toBeNull();
});

test('the signed url receives the encrypted blob when its sha256 matches', function () {
    $blob = encryptedBlob();
    $backup = SigningKeyBackup::factory()->create(['sha256' => hash('sha256', $blob)]);

    $this->call('PUT', $backup->uploadUrl(), content: $blob)
        ->assertOk()
        ->assertJsonPath('data.has_file', true)
        ->assertJsonPath('data.sha256', hash('sha256', $blob))
        ->assertJsonPath('data.key_fingerprint', $backup->key_fingerprint);

    Storage::disk('local')->assertExists($backup->refresh()->file_path);
    expect(SigningKeyBackup::current()->is($backup))->toBeTrue();
});

test('the upload is refused and nothing is kept when the sha256 differs', function () {
    $backup = SigningKeyBackup::factory()->create();

    $this->call('PUT', $backup->uploadUrl(), content: encryptedBlob())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Fichier refusé : son SHA-256 ne correspond pas à celui annoncé.');

    expect($backup->refresh()->has_file)->toBeFalse();
    Storage::disk('local')->assertDirectoryEmpty('signing-key-backups');
});

test('expired or tampered signed urls are refused', function () {
    $blob = encryptedBlob();
    $backup = SigningKeyBackup::factory()->uploaded()->create(['sha256' => hash('sha256', $blob)]);
    $upload = SigningKeyBackup::factory()->create(['sha256' => hash('sha256', $blob)]);

    $this->call('PUT', route('api.v1.signing-key-backups.upload', $upload), content: $blob)->assertForbidden();
    $this->call('PUT', str_replace("/{$upload->id}/", "/{$backup->id}/", $upload->uploadUrl()), content: $blob)->assertForbidden();
    $this->get(route('api.v1.signing-key-backups.download', $backup))->assertForbidden();

    $downloadUrl = $backup->downloadUrl();
    $this->travel(16)->minutes();

    $this->get($downloadUrl)->assertForbidden();
    $this->call('PUT', $upload->uploadUrl(), content: $blob)->assertOk();
});

test('a new backup becomes current and the old one keeps its file', function () {
    $old = SigningKeyBackup::factory()->uploaded()->create();
    Storage::disk('local')->put($old->file_path, 'old blob');
    $blob = encryptedBlob();
    $new = SigningKeyBackup::factory()->create(['sha256' => hash('sha256', $blob)]);

    $this->call('PUT', $new->uploadUrl(), content: $blob)->assertOk();
    $this->call('PUT', $new->uploadUrl(), content: $blob)->assertUnprocessable();

    expect($old->refresh()->replaced_at)->not->toBeNull()
        ->and($new->refresh()->replaced_at)->toBeNull()
        ->and(SigningKeyBackup::current()->is($new))->toBeTrue();
    Storage::disk('local')->assertExists($old->file_path);
});

test('get-signing-key-backup returns null without backup, then the current one with a download url', function () {
    $this->server->tool(GetSigningKeyBackupTool::class)->assertOk()->assertSee('"backup":null');

    $backup = SigningKeyBackup::factory()->uploaded()->create();
    Storage::disk('local')->put($backup->file_path, 'blob');

    $this->server->tool(GetSigningKeyBackupTool::class)
        ->assertOk()
        ->assertSee([$backup->key_fingerprint, 'download_url', 'signature=']);

    $this->get($backup->downloadUrl())
        ->assertOk()
        ->assertDownload('me-admin-release.age')
        ->assertHeader('Content-Type', 'application/octet-stream');
});

test('the tools are refused to a token without mcp access', function () {
    $plain = User::factory()->create()->createToken('iPhone', ['read'])->plainTextToken;

    $this->withToken($plain)->postJson('/mcp/portfolio', [
        'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'get-signing-key-backup', 'arguments' => []],
    ])->assertForbidden();

});

test('the admin page shows the current signing key backup', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.app-releases.index'))
        ->assertInertia(fn ($page) => $page->where('signingKeyBackup', null));

    $backup = SigningKeyBackup::factory()->uploaded()->create();

    $this->get(route('admin.app-releases.index'))
        ->assertInertia(fn ($page) => $page->where('signingKeyBackup.key_fingerprint', $backup->key_fingerprint));
});
