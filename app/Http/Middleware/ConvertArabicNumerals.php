<?php

namespace App\Http\Middleware;

use App\Support\ArabicNumeralConverter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to automatically convert Arabic-Indic numerals to Western numerals.
 * 
 * This middleware processes all incoming request data and converts any Arabic
 * numerals (٠١٢٣٤٥٦٧٨٩) to their English equivalents (0123456789).
 * This ensures consistent data storage and prevents validation errors.
 */
class ConvertArabicNumerals
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Convert all input values (POST, PUT, PATCH data)
        $input = ArabicNumeralConverter::toEnglish($request->all());
        $request->merge($input);
        
        // Also convert query parameters
        $query = ArabicNumeralConverter::toEnglish($request->query());
        $request->query->replace($query);
        
        return $next($request);
    }
}
