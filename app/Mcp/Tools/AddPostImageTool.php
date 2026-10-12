<?php

namespace App\Mcp\Tools;

use App\Enums\PublicationStatus;
use App\Models\Post;
use App\Services\PublicUrlFetcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Description('Ajoute une image à un brouillon d\'article : sa couverture (role « cover », remplace l\'ancienne), ou une image du contenu (role « content ») dont l\'outil renvoie la balise <img> à insérer dans body avec save-post-draft. Source : une adresse publique (url) ou un fichier local encodé en base64 (data), JPEG, PNG, WebP ou GIF de 5 Mo au plus. N\'utiliser que des images libres de droits ou du développeur (captures de ses projets).')]
class AddPostImageTool extends Tool
{
    private const int MAX_BYTES = 5 * 1_048_576;

    /** @var array<string, string> */
    private const array EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    public function handle(Request $request, PublicUrlFetcher $fetcher): Response
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'role' => ['required', 'in:cover,content'],
            'url' => ['nullable', 'required_without:data', 'url:http,https', 'max:2000'],
            'data' => ['nullable', 'required_without:url', 'string'],
            'alt' => ['nullable', 'required_if:role,content', 'string', 'max:200'],
        ]);

        $post = Post::query()->find($request->get('id'));

        if ($post?->status !== PublicationStatus::Draft) {
            return Response::error('Seul un brouillon d\'article existant peut recevoir des images.');
        }

        try {
            $bytes = filled($request->get('url'))
                ? $fetcher->get($request->get('url'), self::MAX_BYTES)->body()
                : $this->decode((string) $request->get('data'));
        } catch (RuntimeException $exception) {
            return Response::error($exception->getMessage());
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extension = self::EXTENSIONS[$mime] ?? null;

        if ($extension === null || strlen($bytes) > self::MAX_BYTES) {
            return Response::error('Image refusée : JPEG, PNG, WebP ou GIF de 5 Mo au plus.');
        }

        $fileName = Str::slug($request->get('alt') ?: $post->slug).'-'.Str::random(6).".{$extension}";

        if ($request->get('role') === 'cover') {
            $post->addMediaFromString($bytes)->usingFileName($fileName)->toMediaCollection('cover');

            return Response::text("Couverture du brouillon #{$post->id} enregistrée.");
        }

        // Même emplacement que les images insérées depuis l'éditeur de l'administration.
        $disk = config('media-library.disk_name');
        Storage::disk($disk)->put("blog/{$fileName}", $bytes);
        $url = Storage::disk($disk)->url("blog/{$fileName}");
        $alt = e($request->get('alt'));

        return Response::text("Image enregistrée. À insérer dans body (save-post-draft) à l'endroit voulu :\n<img src=\"{$url}\" alt=\"{$alt}\">");
    }

    /**
     * @throws RuntimeException
     */
    private function decode(string $data): string
    {
        $bytes = base64_decode((string) preg_replace('/^data:[^;]+;base64,/', '', trim($data)), true);

        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('data n\'est pas du base64 valide.');
        }

        return $bytes;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant du brouillon d\'article.')->required(),
            'role' => $schema->string()->enum(['cover', 'content'])->description('cover : image de couverture ; content : image du contenu.')->required(),
            'url' => $schema->string()->description('Adresse publique de l\'image.'),
            'data' => $schema->string()->description('Fichier image local encodé en base64 (préfixe data:image/…;base64, accepté).'),
            'alt' => $schema->string()->description('Description de l\'image pour l\'accessibilité (obligatoire pour content, 200 caractères max.).'),
        ];
    }
}
