<?php

namespace Database\Factories;

use App\Models\SigningKeyBackup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SigningKeyBackup>
 */
class SigningKeyBackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => 'me-admin-release.age',
            'sha256' => hash('sha256', fake()->uuid()),
            'key_fingerprint' => SigningKeyBackup::normalizeFingerprint(hash('sha256', fake()->uuid())),
            'encryption' => 'age',
        ];
    }

    /** Sauvegarde dont le blob chiffré a été reçu (courante tant qu'elle n'est pas remplacée). */
    public function uploaded(): static
    {
        return $this->afterCreating(function (SigningKeyBackup $backup): void {
            $backup->forceFill(['file_path' => "signing-key-backups/{$backup->id}.bin", 'size' => 2048, 'has_file' => true])->save();
        });
    }
}
