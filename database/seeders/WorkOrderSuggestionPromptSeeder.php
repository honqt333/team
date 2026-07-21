<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Prompt;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeds the default `work_order.suggest` Prompt for tenant id = 1
 * (system / shared catalog). Tenants that need different copy can
 * create their own override by inserting a new row with the same
 * `key` and their own `tenant_id`.
 *
 * The matching system prompt template is hard-coded in
 * `App\Services\AI\WorkOrderSuggestionService` and used as a
 * last-resort fallback when no DB row matches.
 *
 * Run with: `php artisan db:seed --class=WorkOrderSuggestionPromptSeeder`
 *
 * Spec: docs/features/ai-service-suggester/design.md §5/§12.
 */
class WorkOrderSuggestionPromptSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve the system tenant id. Falls back to a "default" tenant
        // for local environments where tenant 1 may not exist yet.
        $systemTenantId = $this->resolveSystemTenantId();

        $content = <<<'AR'
أنت فني خبير في ورشة سيارات. بناءً على شكوى العميل، اقترح 4 إلى 8 أصناف أو خدمات
من كتالوج المركز فقط. الرد يجب أن يكون JSON حصراً بدون أي نص إضافي.

صيغة الرد:
{
  "suggestions": [
    {
      "item_type": "service" | "part",
      "item_id": <int>,
      "name": "<string>",
      "reason": "<string>",
      "confidence": <float 0..1>,
      "qty": <int 1..4>
    }
  ]
}

قواعد:
- لا تخترع أصنافاً غير موجودة في الكتالوج المرفق
- إذا كانت الشكوى غير واضحة، اقترح فحصاً وقائياً بثقة منخفضة
- اطرح 4-8 اقتراحات بالضبط، مرتبة من الأعلى ثقة إلى الأدنى
AR;

        $prompt = Prompt::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $systemTenantId)
            ->where('key', 'work_order.suggest')
            ->where('version', 1)
            ->first();

        if ($prompt === null) {
            $payload = [
                'tenant_id' => $systemTenantId,
                'key' => 'work_order.suggest',
                'version' => 1,
                'content' => $content,
                'model' => 'gpt-4o-mini',
                'temperature' => 0.20,
                'active' => true,
            ];

            Prompt::query()->withoutGlobalScopes()->create($payload);

            $this->command?->info("Seeded work_order.suggest prompt for tenant {$systemTenantId}.");

            return;
        }

        $prompt->fill([
            'content' => $content,
            'model' => 'gpt-4o-mini',
            'temperature' => 0.20,
            'active' => true,
        ])->save();

        $this->command?->info("Updated work_order.suggest prompt for tenant {$systemTenantId}.");
    }

    private function resolveSystemTenantId(): int
    {
        $tenant = Tenant::query()->withoutGlobalScopes()->orderBy('id')->first();

        if ($tenant !== null) {
            return (int) $tenant->id;
        }

        // No tenant at all — fabricate a sentinel so the prompt can
        // still be seeded for fresh installs. The Prompt observer will
        // throw if `tenant_id` is empty, so we keep this fallback.
        $tenant = Tenant::query()->withoutGlobalScopes()->create([
            'name' => 'System',
            'slug' => 'system',
            'status' => 'active',
        ]);

        return (int) $tenant->id;
    }
}
