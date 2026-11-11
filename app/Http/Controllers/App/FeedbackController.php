<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Support\TenancyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FeedbackController extends Controller
{
    /**
     * Display a listing of tenant feedbacks.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Feedback::class);

        $query = Feedback::query()
            ->with(['user:id,name,photo_path', 'adminUser:id,name'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('comments', 'like', "%{$search}%")
                        ->orWhere('admin_reply', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->module, function ($q, $module) {
                $q->where('module', $module);
            })
            ->orderByDesc('id');

        // Statistics for overview cards
        $stats = [
            'total' => Feedback::count(),
            'pending' => Feedback::where('status', Feedback::STATUS_PENDING)->count(),
            'in_progress' => Feedback::where('status', Feedback::STATUS_IN_PROGRESS)->count(),
            'resolved' => Feedback::where('status', Feedback::STATUS_RESOLVED)->count(),
            'unread_replies' => Feedback::whereNotNull('admin_reply')->where('has_user_seen_reply', false)->count(),
        ];

        $modules = $this->getAvailableModules();
        $types = $this->getAvailableTypes();

        return Inertia::render('Feedback/Index', [
            'feedbacks' => $query->paginate(12)->withQueryString(),
            'stats' => $stats,
            'modules' => $modules,
            'types' => $types,
            'filters' => $request->only(['search', 'type', 'status', 'module']),
        ]);
    }

    /**
     * Store a newly created feedback.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Feedback::class);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:bug,feature_request,suggestion'],
            'module' => ['required', 'string', 'max:50'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'allow_admin_access' => ['nullable', 'boolean'],
            'video' => ['nullable', 'file', 'mimes:webm,mp4,ogg,mkv', 'max:51200'],
            'video_duration_seconds' => ['nullable', 'integer', 'max:120'],
        ]);

        if (empty($validated['comments']) && ! $request->hasFile('video')) {
            return back()->withErrors(['comments' => __('messages.feedback_comments_or_video_required')]);
        }

        $tenantId = TenancyContext::tenantId() ?? $request->user()->tenant_id;
        $videoPath = null;

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $extension = $file->getClientOriginalExtension() ?: 'webm';
            $fileName = Str::uuid().'.'.$extension;
            $videoPath = $file->storeAs("tenants/{$tenantId}/feedbacks", $fileName, 'public');
        }

        Feedback::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'module' => $validated['module'],
            'comments' => $validated['comments'] ?? null,
            'allow_admin_access' => (bool) ($validated['allow_admin_access'] ?? false),
            'video_path' => $videoPath,
            'video_duration_seconds' => $validated['video_duration_seconds'] ?? null,
            'status' => Feedback::STATUS_PENDING,
        ]);

        return back()->with('success', __('messages.feedback_submitted_successfully'));
    }

    /**
     * Stream user feedback video with support for byte range requests.
     */
    public function streamVideo(Feedback $feedback): BinaryFileResponse
    {
        $this->authorize('view', $feedback);

        if (! $feedback->video_path || ! Storage::disk('public')->exists($feedback->video_path)) {
            abort(404, 'Video not found.');
        }

        $path = Storage::disk('public')->path($feedback->video_path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mime = match (strtolower($extension)) {
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
            'ogg', 'ogv' => 'video/ogg',
            'mkv' => 'video/x-matroska',
            default => mime_content_type($path) ?: 'video/webm',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Stream admin reply video with support for byte range requests.
     */
    public function streamReplyVideo(Feedback $feedback): BinaryFileResponse
    {
        $this->authorize('view', $feedback);

        if (! $feedback->admin_reply_video_path || ! Storage::disk('public')->exists($feedback->admin_reply_video_path)) {
            abort(404, 'Reply video not found.');
        }

        $path = Storage::disk('public')->path($feedback->admin_reply_video_path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mime = match (strtolower($extension)) {
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
            'ogg', 'ogv' => 'video/ogg',
            'mkv' => 'video/x-matroska',
            default => mime_content_type($path) ?: 'video/webm',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Mark admin reply as seen by the user.
     */
    public function markReplySeen(Feedback $feedback): RedirectResponse
    {
        $this->authorize('view', $feedback);

        $feedback->update(['has_user_seen_reply' => true]);

        return back();
    }

    /**
     * Remove the specified feedback from storage.
     * Deleting feedback is not allowed from tenant/client portal.
     * Deletions can only be performed from the system management panel.
     */
    public function destroy(Feedback $feedback): RedirectResponse
    {
        abort(403, 'لا يُسمح بحذف البلاغات من واجهة المشتركين. الحذف متاح فقط من لوحة إدارة النظام.');
    }

    /**
     * List of available modules in Carag.
     *
     * @return array<int, array{id: string, name: string}>
     */
    protected function getAvailableModules(): array
    {
        return [
            ['id' => 'work_orders', 'name' => 'كروت العمل'],
            ['id' => 'quotes', 'name' => 'التقييمات وعروض الأسعار'],
            ['id' => 'invoices', 'name' => 'الفواتير والمدفوعات'],
            ['id' => 'customers', 'name' => 'إدارة العملاء'],
            ['id' => 'vehicles', 'name' => 'إدارة المركبات'],
            ['id' => 'purchases', 'name' => 'المشتريات والموردين'],
            ['id' => 'inventory', 'name' => 'إدارة المخزون'],
            ['id' => 'reports', 'name' => 'التقارير والتحليلات'],
            ['id' => 'hr_payroll', 'name' => 'الموظفين والرواتب'],
            ['id' => 'appointments', 'name' => 'المواعيد والحجوزات'],
            ['id' => 'website', 'name' => 'صانع الموقع'],
            ['id' => 'settings', 'name' => 'إعدادات النظام'],
            ['id' => 'other', 'name' => 'عام / أخرى'],
        ];
    }

    /**
     * List of available feedback types.
     *
     * @return array<int, array{id: string, name: string, description: string}>
     */
    protected function getAvailableTypes(): array
    {
        return [
            [
                'id' => Feedback::TYPE_BUG,
                'name' => 'خطأ أو مشكلة برمجية',
                'description' => 'للإبلاغ عن عطل، سلوك غير متوقع، أو توقف في وظيفة معينة',
            ],
            [
                'id' => Feedback::TYPE_FEATURE_REQUEST,
                'name' => 'طلب ميزة جديدة',
                'description' => 'لطلب ميزة أو خاصية جديدة تحتاجها لتطوير عمل ورشتك',
            ],
            [
                'id' => Feedback::TYPE_SUGGESTION,
                'name' => 'اقتراح تحسين',
                'description' => 'لاقتراح تحسين على التصميم أو سرعة وسلاسة الاستخدام',
            ],
        ];
    }
}
