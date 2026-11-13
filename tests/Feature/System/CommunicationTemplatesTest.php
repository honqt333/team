<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\CommunicationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
        ]);
    }

    public function test_can_view_templates_index(): void
    {
        CommunicationTemplate::create([
            'code' => 'test_template',
            'name' => 'قالب تجريبي',
            'type' => 'email',
            'subject' => 'عنوان تجريبي',
            'content' => '<p>محتوى تجريبي</p>',
            'variables' => ['{app_name}'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('system.communication.templates.index'));

        $response->assertOk();
    }

    public function test_can_view_template_edit(): void
    {
        $template = CommunicationTemplate::create([
            'code' => 'test_template_edit',
            'name' => 'قالب تجريبي للتعديل',
            'type' => 'email',
            'subject' => 'عنوان تجريبي',
            'content' => '<p>محتوى</p>',
            'variables' => ['{app_name}'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('system.communication.templates.edit', $template->id));

        $response->assertOk();
    }

    public function test_can_update_template(): void
    {
        $template = CommunicationTemplate::create([
            'code' => 'test_template_update',
            'name' => 'قالب تجريبي للتحديث',
            'type' => 'email',
            'subject' => 'عنوان قديم',
            'content' => '<p>محتوى قديم</p>',
            'variables' => ['{app_name}'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('system.communication.templates.update', $template->id), [
                'subject' => 'عنوان جديد محدث',
                'content' => '<p>محتوى جديد محدث</p>',
                'is_active' => false,
            ]);

        $response->assertRedirect(route('system.communication.templates.index'));

        $template->refresh();
        $this->assertSame('عنوان جديد محدث', $template->subject);
        $this->assertSame('<p>محتوى جديد محدث</p>', $template->content);
        $this->assertFalse($template->is_active);
    }

    public function test_update_strips_xss_bypass_vectors_from_template_content(): void
    {
        $template = CommunicationTemplate::create([
            'code' => 'test_template_xss',
            'name' => 'قالب أمان',
            'type' => 'email',
            'subject' => 'عنوان',
            'content' => '<p>آمن</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('system.communication.templates.update', $template->id), [
                'subject' => 'عنوان',
                'content' => '<p>مرحبا</p><svg/onload=alert(1)><img src="x"onerror="alert(2)"><a href="javascript:alert(3)">click</a>',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('system.communication.templates.index'));

        $template->refresh();
        $this->assertStringNotContainsString('onload', $template->content);
        $this->assertStringNotContainsString('onerror', $template->content);
        $this->assertStringNotContainsString('javascript:', $template->content);
        $this->assertStringNotContainsString('<svg', $template->content);
        $this->assertStringContainsString('<p>مرحبا</p>', $template->content);
    }
}
