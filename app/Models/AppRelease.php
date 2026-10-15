<?php

namespace App\Models;

use Database\Factories\AppReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use ZipArchive;

/**
 * Build Android (APK) de l'application mobile, privé : envoyé depuis Claude Code (MCP) ou
 * l'administration, publié une fois validé, puis proposé en mise à jour par l'app elle-même.
 */
class AppRelease extends Model
{
    /** @use HasFactory<AppReleaseFactory> */
    use HasFactory;

    public const string DISK = 'local';

    public const int MAX_BYTES = 128 * 1_048_576;

    /** @var list<string> */
    protected $fillable = [
        'version_name',
        'build_number',
        'notes',
        'expected_sha256',
    ];

    /** @var list<string> */
    protected $hidden = [
        'file_path',
        'expected_sha256',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'build_number' => 'integer',
            'size' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (AppRelease $release): void {
            if ($release->file_path !== null) {
                Storage::disk(self::DISK)->delete($release->file_path);
            }
        });
    }

    /**
     * @param  Builder<AppRelease>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    public static function latestPublished(): ?self
    {
        return self::query()->published()->orderByDesc('build_number')->first();
    }

    public static function highestBuildNumber(): int
    {
        return (int) self::query()->max('build_number');
    }

    public function hasFile(): bool
    {
        return $this->file_path !== null;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Enregistre l'APK depuis un fichier local, après vérification (archive APK, taille,
     * empreinte annoncée). Le fichier précédent est remplacé tant que le build n'est pas publié.
     *
     * @throws RuntimeException
     */
    public function attachApk(string $sourcePath): void
    {
        if ($this->isPublished()) {
            throw new RuntimeException('Ce build est déjà publié : son fichier ne peut plus changer.');
        }

        $size = (int) filesize($sourcePath);

        if ($size === 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('Fichier refusé : un APK de 128 Mo au plus est attendu.');
        }

        $zip = new ZipArchive;

        if ($zip->open($sourcePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Fichier refusé : ce n\'est pas un APK.');
        }

        $isApk = $zip->locateName('AndroidManifest.xml') !== false && $zip->locateName('classes.dex') !== false;
        $zip->close();

        if (! $isApk) {
            throw new RuntimeException('Fichier refusé : ce n\'est pas un APK.');
        }

        $sha256 = (string) hash_file('sha256', $sourcePath);

        if ($this->expected_sha256 !== null && ! hash_equals(strtolower($this->expected_sha256), $sha256)) {
            throw new RuntimeException('Fichier refusé : son SHA-256 ne correspond pas à celui annoncé.');
        }

        $path = 'app-releases/'.$this->fileName();
        $stream = fopen($sourcePath, 'rb');
        Storage::disk(self::DISK)->writeStream($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->forceFill(['file_path' => $path, 'size' => $size, 'sha256' => $sha256])->save();
    }

    public function fileName(): string
    {
        return "app-{$this->version_name}-{$this->build_number}.apk";
    }

    /** Adresse d'envoi de l'APK (PUT, corps brut), valable 15 minutes. */
    public function uploadUrl(): string
    {
        return URL::temporarySignedRoute('api.v1.app-releases.upload', now()->addMinutes(15), ['app_release' => $this->id]);
    }

    /** Adresse de téléchargement sans en-tête d'authentification (installeur Android), valable 30 minutes. */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute('api.v1.app-releases.download', now()->addMinutes(30), ['app_release' => $this->id]);
    }

    /**
     * @return array{id: int, version_name: string, build_number: int, notes: string|null, size: int|null, sha256: string|null, has_file: bool, published_at: string|null, created_at: string|null}
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'version_name' => $this->version_name,
            'build_number' => $this->build_number,
            'notes' => $this->notes,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'has_file' => $this->hasFile(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
