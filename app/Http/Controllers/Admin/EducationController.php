<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/educations/index', [
            'educations' => Education::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/educations/create');
    }

    public function store(EducationRequest $request): RedirectResponse
    {
        Education::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formation créée.')]);

        return to_route('admin.educations.index');
    }

    public function edit(Education $education): Response
    {
        return Inertia::render('admin/educations/edit', [
            'education' => $education,
        ]);
    }

    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        $education->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formation mise à jour.')]);

        return to_route('admin.educations.index');
    }

    public function destroy(Education $education): RedirectResponse
    {
        $education->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formation supprimée.')]);

        return to_route('admin.educations.index');
    }
}
