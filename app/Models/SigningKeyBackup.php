<?php

namespace App\Models;

use Database\Factories\SigningKeyBackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Sauvegarde de la clé de signature de l'application mobile, chiffrée sur le poste du
 * développeur avec une phrase secrète que le serveur ne connaît jamais : il ne garde qu'un
 * blob opaque. Une seule sauvegarde est courante ; les remplacées gardent leur fichier.
 */
class SigningKeyBackup extends Model
{
    /** @use HasFactory<SigningKeyBackupFactory> */
    use HasFactory;

    public const string DISK = AppRelease::DISK;

    public const int MAX_BYTES = 1_048_576;

    /** @var list<string> */
    protected $fillable = [
        'filename',
        'sha256',
        'key_fingerprint',
        'encryption',
        'notes',
    ];

    /** @var list<string> */
    protected $hidden = [
        'file_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'has_file' => 'boolean',
            'replaced_at' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return self::query()->where('has_file', true)->whereNull('replaced_at')->latest('id')->first();
    }

    /** Empreinte SHA-256 du certificat au format « AB:CD:… » (64 chiffres hexadécimaux, séparés ou non). */
    public static function normalizeFingerprint(string $fingerprint): string
    {
        return implode(':', str_split(strtoupper(str_replace(':', '', $fingerprint)), 2));
    }

    /**
     * Enregistre le blob chiffré reçu, après vérification de sa taille et de son SHA-256 :
     * il devient la sauvegarde courante et la précédente est marquée remplacée.
     *
     * @throws RuntimeException
     */
    public function attachBlob(string $sourcePath): void
    {
        if ($this->has_file) {
            throw new RuntimeException('Cette sauvegarde a déjà reçu son fichier : prépare-en une nouvelle.');
        }

        $size = (int) filesize($sourcePath);

        if ($size === 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('Fichier refusé : une sauvegarde chiffrée de 1 Mo au plus est attendue.');
        }

        $sha256 = (string) hash_file('sha256', $sourcePath);

        if (! hash_equals(strtolower($this->sha256), $sha256)) {
            throw new RuntimeException('Fichier refusé : son SHA-256 ne correspond pas à celui annoncé.');
        }

        $path = "signing-key-backups/{$this->id}.bin";
        $stream = fopen($sourcePath, 'rb');
        Storage::disk(self::DISK)->writeStream($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        DB::transaction(function () use ($path, $size): void {
            self::query()->where('has_file', true)->whereNull('replaced_at')->whereKeyNot($this->id)->update(['replaced_at' => now()]);
            $this->forceFill(['file_path' => $path, 'size' => $size, 'has_file' => true])->save();
        });
    }

    /** Adresse d'envoi du blob chiffré (PUT, corps brut), valable 15 minutes. */
    public function uploadUrl(): string
    {
        return URL::temporarySignedRoute('api.v1.signing-key-backups.upload', now()->addMinutes(15), ['backup' => $this->id]);
    }

    /** Adresse de téléchargement du blob chiffré, valable 15 minutes. */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute('api.v1.signing-key-backups.download', now()->addMinutes(15), ['backup' => $this->id]);
    }

    /**
     * @return array{id: int, filename: string, size: int|null, sha256: string, key_fingerprint: string, encryption: string, notes: string|null, has_file: bool, created_at: string|null, replaced_at: string|null}
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'key_fingerprint' => $this->key_fingerprint,
            'encryption' => $this->encryption,
            'notes' => $this->notes,
            'has_file' => $this->has_file,
            'created_at' => $this->created_at?->toIso8601String(),
            'replaced_at' => $this->replaced_at?->toIso8601String(),
        ];
    }
}
