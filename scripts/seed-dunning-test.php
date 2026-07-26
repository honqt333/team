<?php

declare(strict_types=1);
/**
 * Phase 6 Manual Test Seed — creates one overdue invoice so we can
 * exercise the dunning flow end-to-end.
 *
 *   php scripts/seed-dunning-test.php
 *
 * Safe to re-run; it cleans up the test tenant first.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;

echo "=== Phase 6 manual-test seed ===\n";

// 1. Reuse (or create) a test tenant
$tenant = Tenant::where('slug', 'dunning-test')->first();

if (! $tenant) {
    $tenant = Tenant::create([
        'name' => 'Dunning Test Tenant',
        'slug' => 'dunning-test',
        'email' => 'dunning-test@example.com',
        'phone' => '+966500000123',
        'status' => 'active',
    ]);
    echo "✓ Created tenant #{$tenant->id}\n";
} else {
    // Reset the opt-out flag in case a previous test set it
    $tenant->update(['dunning_opted_out' => false, 'dunning_language' => null]);
    echo "✓ Reusing tenant #{$tenant->id} (reset opt-out)\n";
}

// 2. Reuse (or create) a plan
$plan = Plan::where('slug', 'dunning-test-plan')->first();

if (! $plan) {
    $plan = Plan::create([
        'name' => 'Dunning Test Plan',
        'name_ar' => 'باقة اختبار التذكير',
        'name_en' => 'Dunning Test Plan',
        'slug' => 'dunning-test-plan',
        'price_monthly' => 100,
        'price_yearly' => 1000,
        'trial_days' => 14,
    ]);
    echo "✓ Created plan #{$plan->id}\n";
} else {
    echo "✓ Reusing plan #{$plan->id}\n";
}

// 3. Create (or reuse) an active subscription
$sub = Subscription::where('tenant_id', $tenant->id)
    ->where('plan_id', $plan->id)
    ->where('status', 'active')
    ->first();

if (! $sub) {
    $sub = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDays(60),
        'ends_at' => now()->addDays(30),
    ]);
    echo "✓ Created subscription #{$sub->id}\n";
} else {
    echo "✓ Reusing subscription #{$sub->id}\n";
}

// 4. Clean up any prior test invoices for this tenant so we start fresh
$deleted = SubscriptionInvoice::where('tenant_id', $tenant->id)
    ->where('invoice_number', 'LIKE', 'INV-DUN-%')
    ->delete();
echo "✓ Cleared {$deleted} prior test invoice(s)\n";

// 5. Create 3 overdue invoices at different tier thresholds:
//    - 3 days overdue → tier_1 (gentle)
//    - 8 days overdue → tier_2 (firm)
//    - 20 days overdue → tier_3 (urgent)
$invoices = [];
$scenarios = [
    ['days' => 3, 'number' => 'INV-DUN-T1', 'amount' => 115],
    ['days' => 8, 'number' => 'INV-DUN-T2', 'amount' => 230],
    ['days' => 20, 'number' => 'INV-DUN-T3', 'amount' => 345],
];

foreach ($scenarios as $s) {
    $inv = SubscriptionInvoice::create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $sub->id,
        'invoice_number' => $s['number'],
        'subtotal' => $s['amount'] / 1.15,
        'discount' => 0,
        'vat' => $s['amount'] - ($s['amount'] / 1.15),
        'total' => $s['amount'],
        'status' => 'pending',
        'issued_at' => now()->subDays($s['days'] + 7),
        'due_at' => now()->subDays($s['days']),
    ]);
    $invoices[] = $inv;
    echo "  → Created {$s['number']}: {$s['days']} days overdue, total {$s['amount']}\n";
}

// 6. Also create a PAID invoice that should be SKIPPED
SubscriptionInvoice::create([
    'tenant_id' => $tenant->id,
    'subscription_id' => $sub->id,
    'invoice_number' => 'INV-DUN-PAID',
    'subtotal' => 100,
    'discount' => 0,
    'vat' => 15,
    'total' => 115,
    'status' => 'paid',
    'issued_at' => now()->subDays(20),
    'due_at' => now()->subDays(10),
    'paid_at' => now()->subDays(9),
]);
echo "  → Created INV-DUN-PAID: 10 days overdue, status=paid (should be skipped)\n";

echo "\n=== Seed complete ===\n";
echo "Tenant ID: {$tenant->id}\n";
echo "Tenant email: {$tenant->email}\n";
echo 'Invoices created: '.count($invoices)."\n";
echo "\nNext steps:\n";
echo "  1. Run: php artisan dunning:send-reminders\n";
echo "  2. Check storage/logs/laravel.log for the queued mailable\n";
echo "  3. Open /system/billing/dunning-log to see the rows\n";
