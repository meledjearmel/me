<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyIconImportRequest;
use App\Http\Requests\Admin\TechnologyIconSearchRequest;
use App\Http\Requests\Admin\TechnologyIconUploadRequest;
use App\Services\TechnologyIconLibrary;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;

/**
 * @tags Logos de technologies
 */
class TechnologyIconController extends Controller
{
    public function __construct(private readonly TechnologyIconLibrary $library) {}

    /**
     * Logos disponibles
     *
     * Bibliothèque des logos utilisables par une technologie : le `slug` se passe dans le
     * champ `icon` d'une technologie. `light_url` et `dark_url` sont les images à afficher
     * selon le thème (identiques si le logo n'a pas de variante par thème).
     *
     * @response array{data: list<array{slug: string, light_url: string|null, dark_url: string|null}>}
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->library->all()]);
    }

    /**
     * Chercher un logo dans le catalogue
     *
     * Recherche dans le catalogue public Iconify (Logos, Devicon, Simple Icons). L'`id` d'un
     * résultat se passe à l'import ; `preview_url` est une image d'aperçu (fond clair conseillé).
     *
     * @response array{data: list<array{id: string, name: string, collection: string, preview_url: string}>}
     * @response 502 array{message: string}
     */
    #[QueryParameter('q', description: 'Nom de la technologie (2 à 50 caractères).', type: 'string')]
    public function search(TechnologyIconSearchRequest $request): JsonResponse
    {
        $results = $this->library->search($request->validated('q'));

        if ($results === null) {
            return $this->unavailable();
        }

        return response()->json(['data' => $results]);
    }

    /**
     * Importer un logo du catalogue
     *
     * Télécharge un logo du catalogue dans la bibliothèque sous le nom `slug`. Sans `theme`, un
     * logo monochrome est décliné en variantes claire et sombre, un logo en couleur reste en un
     * seul fichier. Avec `theme` (`light` ou `dark`), seule la variante de ce thème est écrite :
     * on peut ainsi ajouter la variante d'un logo existant en reprenant son `slug`.
     *
     * @response 201 array{slug: string, light_url: string|null, dark_url: string|null}
     * @response 502 array{message: string}
     */
    public function store(TechnologyIconImportRequest $request): JsonResponse
    {
        $slug = $request->validated('slug');

        if (! $this->library->import($request->validated('icon'), $slug, $request->validated('theme'))) {
            return $this->unavailable();
        }

        return $this->created($slug);
    }

    /**
     * Envoyer un logo
     *
     * Envoie un fichier SVG (`file`, 200 Ko max) en `multipart/form-data`, avec le nom `slug` et
     * un `theme` optionnel (voir l'import d'un logo du catalogue). Un SVG contenant du script ou
     * des déclarations d'entités est refusé.
     *
     * @response 201 array{slug: string, light_url: string|null, dark_url: string|null}
     */
    public function upload(TechnologyIconUploadRequest $request): JsonResponse
    {
        $slug = $request->validated('slug');

        if (! $this->library->store($request->file('file')->get(), $slug, $request->validated('theme'))) {
            return response()->json([
                'message' => __('Ce fichier n\'est pas un SVG valide, ou il contient du script.'),
                'errors' => ['file' => [__('Ce fichier n\'est pas un SVG valide, ou il contient du script.')]],
            ], 422);
        }

        return $this->created($slug);
    }

    private function created(string $slug): JsonResponse
    {
        return response()->json([
            'slug' => $slug,
            'light_url' => $this->library->url($slug, 'light'),
            'dark_url' => $this->library->url($slug, 'dark'),
        ], 201);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => __('Le catalogue de logos est momentanément indisponible.'),
        ], 502);
    }
}
