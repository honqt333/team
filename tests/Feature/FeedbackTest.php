<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PerformanceBaselineSeeder::class)->run();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/feedback');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_feedback_page(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        $response = $this->actingAs($user)->get('/app/feedback');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Feedback/Index')
            ->has('feedbacks')
            ->has('stats')
            ->has('modules')
            ->has('types')
        );
    }

    public function test_user_can_submit_feedback_with_comments(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        $response = $this->actingAs($user)->post('/app/feedback', [
            'type' => 'bug',
            'module' => 'work_orders',
            'comments' => 'هناك خطأ عند فتح أمر العمل رقم 102',
            'allow_admin_access' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('feedbacks', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'bug',
            'module' => 'work_orders',
            'allow_admin_access' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_submit_feedback_with_video(): void
    {
        Storage::fake('public');

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        $fakeVideo = UploadedFile::fake()->create('screen-recording.webm', 1024, 'video/webm');

        $response = $this->actingAs($user)->post('/app/feedback', [
            'type' => 'feature_request',
            'module' => 'invoices',
            'comments' => 'طلب إضافة زر طباعة حرارية سريعة',
            'video' => $fakeVideo,
            'video_duration_seconds' => 45,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $feedback = Feedback::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($feedback);
        $this->assertNotNull($feedback->video_path);
        $this->assertEquals(45, $feedback->video_duration_seconds);
        Storage::disk('public')->assertExists($feedback->video_path);
    }

    public function test_user_can_stream_feedback_video(): void
    {
        Storage::fake('public');

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        $fakeVideo = UploadedFile::fake()->create('screen-recording.webm', 1024, 'video/webm');
        $videoPath = $fakeVideo->storeAs("tenants/{$tenant->id}/feedbacks", 'test.webm', 'public');

        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'bug',
            'module' => 'work_orders',
            'comments' => 'فيديو المشكلة',
            'video_path' => $videoPath,
        ]);

        $response = $this->actingAs($user)->get("/app/feedback/{$feedback->id}/video");

        $response->assertStatus(200);
        $this->assertTrue(str_contains((string) $response->headers->get('Content-Type'), 'video'));
    }

    public function test_user_can_mark_admin_reply_as_seen(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'suggestion',
            'module' => 'reports',
            'comments' => 'اقتراح تحسين',
            'admin_reply' => 'شكراً لك، تم تطبيق الاقتراح في التحديث الأخير.',
            'has_user_seen_reply' => false,
        ]);

        $response = $this->actingAs($user)->post("/app/feedback/{$feedback->id}/seen-reply");
        $response->assertRedirect();

        $this->assertTrue($feedback->fresh()->has_user_seen_reply);
    }

    public function test_user_cannot_delete_feedback(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'suggestion',
            'module' => 'general',
            'comments' => 'بلاغ تجريبي للحذف',
        ]);

        $response = $this->actingAs($user)->delete("/app/feedback/{$feedback->id}");
        $response->assertStatus(403);

        $this->assertNotSoftDeleted('feedbacks', ['id' => $feedback->id]);
    }

    public function test_system_admin_can_delete_feedback_from_system_panel(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();
        $admin = User::where('is_system_admin', true)->first();

        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'suggestion',
            'module' => 'general',
            'comments' => 'بلاغ سيحذفه مدير النظام',
        ]);

        $response = $this->actingAs($admin)->delete("/system/settings/feedbacks/{$feedback->id}");
        $response->assertRedirect();

        $this->assertSoftDeleted('feedbacks', ['id' => $feedback->id]);
    }

    public function test_system_admin_can_view_all_feedbacks_and_reply(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();
        $admin = User::where('is_system_admin', true)->first();

        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'bug',
            'module' => 'inventory',
            'comments' => 'الرصيد لا يتحدث',
            'status' => 'pending',
        ]);

        // The feedback list is part of the consolidated support hub:
        // `/system/settings/feedbacks` redirects to
        // `/system/support?tab=feedbacks` (routes/web.php:1399). The
        // test therefore follows the redirect and asserts that the hub
        // actually renders the feedbacks tab.
        $response = $this->actingAs($admin)->get('/system/settings/feedbacks');
        $response->assertRedirect(route('system.support.index', ['tab' => 'feedbacks']));

        $hub = $this->actingAs($admin)->get(route('system.support.index', ['tab' => 'feedbacks']));
        $hub->assertStatus(200);

        // Admin replies
        $replyResponse = $this->actingAs($admin)->post("/system/settings/feedbacks/{$feedback->id}/reply", [
            'admin_reply' => 'تم إصلاح مشكلة تحديث الرصيد بنجاح.',
            'status' => 'resolved',
        ]);

        $replyResponse->assertRedirect();
        $this->assertDatabaseHas('feedbacks', [
            'id' => $feedback->id,
            'admin_reply' => 'تم إصلاح مشكلة تحديث الرصيد بنجاح.',
            'status' => 'resolved',
            'admin_user_id' => $admin->id,
            'has_user_seen_reply' => 0,
        ]);
    }
}
