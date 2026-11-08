<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Controllers\System\HelpSettingsController;
use App\Models\Setting;
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
                'whatsapp' => Setting::get('help.contact.whatsapp', '+966 555 785 658'),
                'email' => Setting::get('help.contact.email', env('MAIL_FROM_ADDRESS', 'support@khidmh.pro')),
                'phone' => Setting::get('help.contact.phone', '920000000'),
                'hours' => Setting::get('help.contact.hours', 'السبت - الخميس: 8:00 ص - 10:00 م'),
                'whatsapp_enabled' => Setting::get('help.contact.whatsapp_enabled', '1') === '1',
                'ticket_url' => Setting::get('help.contact.ticket_url', ''),
            ],
            'quickStartItems' => json_decode((string) Setting::get('help.quick_start_items'), true) ?: HelpSettingsController::DEFAULT_QUICK_START,
            'videoTutorials' => json_decode((string) Setting::get('help.video_tutorials'), true) ?: HelpSettingsController::DEFAULT_VIDEOS,
            'categories' => json_decode((string) Setting::get('help.categories'), true) ?: HelpSettingsController::DEFAULT_CATEGORIES,
            'topics' => json_decode((string) Setting::get('help.topics'), true) ?: HelpSettingsController::DEFAULT_TOPICS,
            'faqs' => json_decode((string) Setting::get('help.faqs'), true) ?: HelpSettingsController::DEFAULT_FAQS,
        ]);
    }
}
