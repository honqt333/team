<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemAnnouncement;
use App\Models\NotificationSendLog;
use App\Models\Tenant;
use App\Services\Messaging\SmsService;
use App\Services\Messaging\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementsController extends Controller
{
    /**
     * List announcements.
     */
    public function index(Request $request): Response
    {
        $query = SystemAnnouncement::with('admin')
            ->withCount(['reads', 'sendLogs as sent_count' => fn($q) => $q->where('status', 'sent')]);
        
        if ($request->status === 'published') {
            $query->where('is_published', true);
        } elseif ($request->status === 'draft') {
            $query->where('is_published', false);
        }
        
        $announcements = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('System/Announcements/Index', [
            'announcements' => $announcements,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Create form.
     */
    public function create(): Response
    {
        $tenants = Tenant::select('id', 'trade_name', 'name')->get();
        
        return Inertia::render('System/Announcements/Create', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Store announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,important,maintenance',
            'target' => 'required|in:all,active,trial,expired,specific',
            'target_tenant_ids' => 'nullable|array',
            'channels' => 'required|array',
            'expires_at' => 'nullable|date',
        ]);

        $validated['admin_user_id'] = auth()->id();
        $validated['channels'] = $validated['channels'] ?? ['in_app'];

        $announcement = SystemAnnouncement::create($validated);

        return redirect()->route('system.announcements.show', $announcement)
            ->with('success', 'تم إنشاء الإعلان');
    }

    /**
     * Show announcement.
     */
    public function show(SystemAnnouncement $announcement): Response
    {
        $announcement->load(['admin', 'sendLogs.tenant']);
        
        $stats = [
            'reads_count' => $announcement->reads()->count(),
            'sent_count' => $announcement->sendLogs()->where('status', 'sent')->count(),
            'failed_count' => $announcement->sendLogs()->where('status', 'failed')->count(),
            'target_count' => $announcement->getTargetTenants()->count(),
        ];

        return Inertia::render('System/Announcements/Show', [
            'announcement' => $announcement,
            'stats' => $stats,
            'recentLogs' => $announcement->sendLogs()->with('tenant')->latest()->limit(20)->get(),
        ]);
    }

    /**
     * Publish announcement.
     */
    public function publish(SystemAnnouncement $announcement)
    {
        $announcement->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Send via selected channels
        $this->sendToChannels($announcement);

        return back()->with('success', 'تم نشر الإعلان');
    }

    /**
     * Unpublish announcement.
     */
    public function unpublish(SystemAnnouncement $announcement)
    {
        $announcement->update(['is_published' => false]);
        return back()->with('success', 'تم إيقاف الإعلان');
    }

    /**
     * Delete announcement.
     */
    public function destroy(SystemAnnouncement $announcement)
    {
        $announcement->delete();
        return redirect()->route('system.announcements.index')
            ->with('success', 'تم حذف الإعلان');
    }

    /**
     * Send announcement via channels.
     */
    protected function sendToChannels(SystemAnnouncement $announcement): void
    {
        $channels = $announcement->channels ?? ['in_app'];
        $tenants = $announcement->getTargetTenants()->get();

        foreach ($tenants as $tenant) {
            foreach ($channels as $channel) {
                if ($channel === 'in_app') continue; // In-app is automatic

                try {
                    match ($channel) {
                        'email' => $this->sendEmail($announcement, $tenant),
                        'sms' => $this->sendSms($announcement, $tenant),
                        default => null,
                    };

                    NotificationSendLog::logSend($announcement->id, $tenant->id, $channel, 'sent');
                } catch (\Exception $e) {
                    NotificationSendLog::logSend($announcement->id, $tenant->id, $channel, 'failed', $e->getMessage());
                }
            }
        }
    }

    protected function sendEmail(SystemAnnouncement $announcement, Tenant $tenant): void
    {
        $email = $tenant->email ?? $tenant->users()->first()?->email;
        if (!$email) return;

        $emailService = new EmailService();
        $emailService->send(
            $email,
            $announcement->title,
            $announcement->content,
            true,
            $tenant->id
        );
    }

    protected function sendSms(SystemAnnouncement $announcement, Tenant $tenant): void
    {
        $phone = $tenant->phone ?? $tenant->users()->first()?->phone;
        if (!$phone) return;

        $smsService = new SmsService();
        $smsService->send($phone, strip_tags($announcement->content), $tenant->id);
    }
}
