<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Center;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchesController extends Controller
{
    /**
     * Display branches/centers list.
     */
    public function index(): Response
    {
        $tenant = auth()->user()->tenant;
        $branches = $tenant->centers()
            ->select('id', 'name', 'center_type', 'manager_name', 'phone', 'email', 'slug', 'is_active', 'created_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('Settings/Branches/Index', [
            'branches' => $branches,
        ]);
    }
}
