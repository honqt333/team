<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function hub()
    {
        return Inertia::render('Reports/Hub');
    }
}
