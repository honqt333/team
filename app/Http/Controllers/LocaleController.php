<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Set the application locale.
     */
    public function setLocale(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:ar,en',
        ]);

        $locale = $request->locale;
        
        // Store in session
        session(['locale' => $locale]);
        
        // Set for current request
        App::setLocale($locale);
        
        return response()->json([
            'success' => true,
            'locale' => $locale,
        ]);
    }

    /**
     * Get the current locale.
     */
    public function getLocale()
    {
        return response()->json([
            'locale' => session('locale', config('app.locale', 'ar')),
        ]);
    }
}
