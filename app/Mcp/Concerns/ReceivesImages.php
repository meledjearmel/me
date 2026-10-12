<?php

namespace App\Mcp\Concerns;

use App\Services\PublicUrlFetcher;
use finfo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use RuntimeException;

/**
 * Image envoyée à un outil MCP : adresse publique (url) ou fichier local en base64 (data),
 * JPEG, PNG, WebP ou GIF de 5 Mo au plus.
 */
trait ReceivesImages
{
    private const int MAX_IMAGE_BYTES = 5 * 1_048_576;

    /** @var array<string, string> */
    private const array IMAGE_EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /** @return array<string, mixed> */
    private function imageRules(): array
    {
        return [
            'url' => ['nullable', 'required_without:data', 'url:http,https', 'max:2000'],
            'data' => ['nullable', 'required_without:url', 'string'],
        ];
    }

    /**
     * @return array{bytes: string, extension: string}
     *
     * @throws RuntimeException
     */
    private function receiveImage(Request $request, PublicUrlFetcher $fetcher): array
    {
        $bytes = filled($request->get('url'))
            ? $fetcher->get($request->get('url'), self::MAX_IMAGE_BYTES)->body()
            : base64_decode((string) preg_replace('/^data:[^;]+;base64,/', '', trim((string) $request->get('data'))), true);

        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('data n\'est pas du base64 valide.');
        }

        $extension = self::IMAGE_EXTENSIONS[(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes)] ?? null;

        if ($extension === null || strlen($bytes) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Image refusée : JPEG, PNG, WebP ou GIF de 5 Mo au plus.');
        }

        return ['bytes' => $bytes, 'extension' => $extension];
    }

    /**
     * @return array<string, Type>
     */
    private function imageFields(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Adresse publique de l\'image.'),
            'data' => $schema->string()->description('Fichier image local encodé en base64 (préfixe data:image/…;base64, accepté).'),
        ];
    }
}
