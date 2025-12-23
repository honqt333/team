<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/Index');
    }
}
