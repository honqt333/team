<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Feedback;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportHubController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'feedbacks');

        if (! in_array($tab, ['feedbacks', 'contact_messages', 'help'], true)) {
            $tab = 'feedbacks';
        }

        $feedbacks = null;
        $feedbackStats = null;

        if ($tab === 'feedbacks') {
            $feedbacks = Feedback::withoutGlobalScopes()
                ->with([
                    'tenant:id,name,trade_name',
                    'user:id,name,email,phone',
                    'adminUser:id,name',
                ])
                ->when($request->search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('comments', 'like', "%{$search}%")
                            ->orWhere('admin_reply', 'like', "%{$search}%")
                            ->orWhereHas('tenant', function ($t) use ($search) {
                                $t->where('name', 'like', "%{$search}%")
                                    ->orWhere('trade_name', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->when($request->type, fn ($q, $type) => $q->where('type', $type))
                ->when($request->module, fn ($q, $module) => $q->where('module', $module))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();

            $feedbackStats = [
                'total' => Feedback::withoutGlobalScopes()->count(),
                'pending' => Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_PENDING)->count(),
                'in_progress' => Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_IN_PROGRESS)->count(),
                'resolved' => Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_RESOLVED)->count(),
                'bugs' => Feedback::withoutGlobalScopes()->where('type', Feedback::TYPE_BUG)->count(),
            ];
        }

        $contactMessages = null;

        if ($tab === 'contact_messages') {
            $contactMessages = ContactMessage::query()
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        $helpSettings = null;

        if ($tab === 'help') {
            $helpSettings = [
                'contact_whatsapp' => Setting::get('help.contact.whatsapp', '+966 555 785 658'),
                'contact_phone' => Setting::get('help.contact.phone', '920000000'),
                'contact_email' => Setting::get('help.contact.email', 'support@carag.sa'),
                'contact_hours' => Setting::get('help.contact.hours', 'السبت - الخميس: 8:00 ص - 10:00 م'),
                'contact_whatsapp_enabled' => Setting::get('help.contact.whatsapp_enabled', '1') === '1',
                'contact_ticket_url' => Setting::get('help.contact.ticket_url', ''),

                'quick_start_items' => json_decode((string) Setting::get('help.quick_start_items'), true) ?: HelpSettingsController::DEFAULT_QUICK_START,
                'video_tutorials' => json_decode((string) Setting::get('help.video_tutorials'), true) ?: HelpSettingsController::DEFAULT_VIDEOS,
                'categories' => json_decode((string) Setting::get('help.categories'), true) ?: HelpSettingsController::DEFAULT_CATEGORIES,
                'topics' => json_decode((string) Setting::get('help.topics'), true) ?: HelpSettingsController::DEFAULT_TOPICS,
                'faqs' => json_decode((string) Setting::get('help.faqs'), true) ?: HelpSettingsController::DEFAULT_FAQS,
            ];
        }

        $unreadMessagesCount = ContactMessage::where('is_read', false)->count();
        $pendingFeedbacksCount = Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_PENDING)->count();

        return Inertia::render('System/Support/SupportHub', [
            'tab' => $tab,
            'feedbacks' => $feedbacks,
            'feedbackStats' => $feedbackStats,
            'contactMessages' => $contactMessages,
            'helpSettings' => $helpSettings,
            'unreadMessagesCount' => $unreadMessagesCount,
            'pendingFeedbacksCount' => $pendingFeedbacksCount,
            'filters' => $request->only(['search', 'status', 'type', 'module']),
        ]);
    }
}
