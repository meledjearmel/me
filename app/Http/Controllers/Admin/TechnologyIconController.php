<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyIconImportRequest;
use App\Http\Requests\Admin\TechnologyIconSearchRequest;
use App\Http\Requests\Admin\TechnologyIconUploadRequest;
use App\Services\TechnologyIconLibrary;
use Illuminate\Http\JsonResponse;

class TechnologyIconController extends Controller
{
    public function __construct(private readonly TechnologyIconLibrary $library) {}

    public function search(TechnologyIconSearchRequest $request): JsonResponse
    {
        $results = $this->library->search($request->validated('q'));

        if ($results === null) {
            return $this->unavailable();
        }

        return response()->json(['icons' => $results]);
    }

    public function store(TechnologyIconImportRequest $request): JsonResponse
    {
        $slug = $request->validated('slug');

        if (! $this->library->import($request->validated('icon'), $slug, $request->validated('theme'))) {
            return $this->unavailable();
        }

        return $this->created($slug);
    }

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
            'icon' => [
                'slug' => $slug,
                'light_url' => $this->library->url($slug, 'light'),
                'dark_url' => $this->library->url($slug, 'dark'),
            ],
        ], 201);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => __('Le catalogue de logos est momentanément indisponible.'),
        ], 502);
    }
}
