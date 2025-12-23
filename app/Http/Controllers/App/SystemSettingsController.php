<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    /**
     * Display the system settings page with side panel navigation.
     */
    public function index(): Response
    {
        $makes = VehicleMake::ordered()->get();
        
        return Inertia::render('Settings/System/Index', [
            'makes' => $makes,
        ]);
    }
}
