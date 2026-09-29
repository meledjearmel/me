<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Congratulation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CongratulationController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/congratulations/index', [
            'congratulations' => $this->paginateList(Congratulation::query()->latest()->latest('id'), $request, ['reason'], ['source']),
            'filters' => $this->listFilters($request, ['source']),
        ]);
    }
}
