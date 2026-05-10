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

    /**
     * Display the integrations dashboard.
     */
    public function integrations(): Response
    {
        return Inertia::render('Settings/Integrations');
    }

    /**
     * Display the website builder dashboard.
     */
    public function website(): Response
    {
        return Inertia::render('Settings/Website');
    }
}
