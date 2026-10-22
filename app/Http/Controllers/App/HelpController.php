<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    /**
     * Display the Help & Knowledge Base page.
     */
    public function index(): Response
    {
        return Inertia::render('Help/Index', [
            'contactSupport' => [
                'whatsapp' => '+966 555 785 658',
                'email' => 'support@carag.sa',
                'phone' => '920000000',
                'hours' => 'السبت - الخميس: 8:00 ص - 10:00 م',
            ],
        ]);
    }
}
