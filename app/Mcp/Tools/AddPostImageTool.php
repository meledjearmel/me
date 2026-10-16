<?php

namespace App\Mcp\Tools;

use App\Enums\PublicationStatus;
use App\Mcp\Concerns\NamesAiClient;
use App\Mcp\Concerns\ReceivesImages;
use App\Models\Post;
use App\Services\Ai\ContentRevisions;
use App\Services\PublicUrlFetcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Name('add-post-image')]
#[Description('Ajoute une image à un article : sa couverture (role « cover », remplace l\'ancienne), ou une image du contenu (role « content ») dont l\'outil renvoie la balise <img> à insérer dans body. Sur un brouillon : couverture enregistrée tout de suite, balise à insérer avec save-post-draft. Sur un article publié : la couverture est proposée comme une révision (summary obligatoire) que le développeur applique ou rejette ; la balise d\'une image du contenu s\'insère dans body avec propose-revision (en français et en anglais), rien n\'étant visible avant. Source : une adresse publique (url) ou un fichier local encodé en base64 (data), JPEG, PNG, WebP ou GIF de 5 Mo au plus. N\'utiliser que des images libres de droits ou du développeur (captures de ses projets).')]
class AddPostImageTool extends Tool
{
    use NamesAiClient, ReceivesImages;

    public function handle(Request $request, PublicUrlFetcher $fetcher, ContentRevisions $revisions): Response
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'role' => ['required', 'in:cover,content'],
            'alt' => ['nullable', 'required_if:role,content', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:1000'],
            ...$this->imageRules(),
        ]);

        $post = Post::query()->find($request->get('id'));

        if ($post === null) {
            return Response::error('Article introuvable.');
        }

        $isPublished = $post->status !== PublicationStatus::Draft;
        $isCover = $request->get('role') === 'cover';

        if ($isPublished && $isCover && blank($request->get('summary'))) {
            return Response::error('Article publié : la couverture sera proposée comme une révision, décris-la dans summary (ce qu\'elle montre et pourquoi).');
        }

        try {
            ['bytes' => $bytes, 'extension' => $extension] = $this->receiveImage($request, $fetcher);
        } catch (RuntimeException $exception) {
            return Response::error($exception->getMessage());
        }

        if ($isCover && $isPublished) {
            $revision = $revisions->proposeImage($post, 'cover', $bytes, $extension, $request->get('summary'), $this->aiClient($request));

            return Response::text("Révision #{$revision->id} proposée ; rien n'a changé sur le site. Le développeur la relit et l'applique ici : ".route('admin.revisions.show', $revision));
        }

        $fileName = Str::slug($request->get('alt') ?: $post->slug).'-'.Str::random(6).".{$extension}";

        if ($isCover) {
            $post->addMediaFromString($bytes)->usingFileName($fileName)->toMediaCollection('cover');

            return Response::text("Couverture du brouillon #{$post->id} enregistrée.");
        }

        // Même emplacement que les images insérées depuis l'éditeur de l'administration.
        $disk = config('media-library.disk_name');
        Storage::disk($disk)->put("blog/{$fileName}", $bytes);
        $url = Storage::disk($disk)->url("blog/{$fileName}");
        $alt = e($request->get('alt'));
        $insertWith = $isPublished ? 'propose-revision, en français et en anglais' : 'save-post-draft';

        return Response::text("Image enregistrée. À insérer dans body ({$insertWith}) à l'endroit voulu :\n<img src=\"{$url}\" alt=\"{$alt}\">");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant de l\'article (brouillon ou publié).')->required(),
            'role' => $schema->string()->enum(['cover', 'content'])->description('cover : image de couverture ; content : image du contenu.')->required(),
            ...$this->imageFields($schema),
            'alt' => $schema->string()->description('Description de l\'image pour l\'accessibilité (obligatoire pour content, 200 caractères max.).'),
            'summary' => $schema->string()->description('Couverture d\'un article publié uniquement (obligatoire) : ce que montre l\'image et pourquoi la changer, en une à trois phrases, pour le développeur.'),
        ];
    }
}
