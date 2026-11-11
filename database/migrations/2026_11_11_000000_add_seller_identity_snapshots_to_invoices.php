<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freeze the issuer identity on the document at creation time.
     *
     * Tenant invoices previously printed the platform company name/VAT
     * from PaymentSettings. Even after switching them to the tenant,
     * a later rename would have rewritten history on reprint. These
     * columns keep the name/VAT/CR that were in force when the
     * document was issued.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('seller_name_snapshot')->nullable()->after('customer_address_snapshot');
            $table->string('seller_name_en_snapshot')->nullable()->after('seller_name_snapshot');
            $table->string('seller_vat_snapshot')->nullable()->after('seller_name_en_snapshot');
            $table->string('seller_cr_snapshot')->nullable()->after('seller_vat_snapshot');
        });

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('seller_name_snapshot')->nullable()->after('invoice_number');
            $table->string('seller_name_en_snapshot')->nullable()->after('seller_name_snapshot');
            $table->string('seller_vat_snapshot')->nullable()->after('seller_name_en_snapshot');
            $table->string('seller_cr_snapshot')->nullable()->after('seller_vat_snapshot');
        });

        $this->backfillTenantInvoices();
        $this->backfillSubscriptionInvoices();
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'seller_name_snapshot',
                'seller_name_en_snapshot',
                'seller_vat_snapshot',
                'seller_cr_snapshot',
            ]);
        });

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'seller_name_snapshot',
                'seller_name_en_snapshot',
                'seller_vat_snapshot',
                'seller_cr_snapshot',
            ]);
        });
    }

    private function backfillTenantInvoices(): void
    {
        DB::table('invoices')
            ->select('id', 'tenant_id')
            ->whereNull('seller_name_snapshot')
            ->orderBy('id')
            ->chunkById(500, function ($invoices): void {
                $tenantIds = $invoices->pluck('tenant_id')->filter()->unique()->values();

                $tenants = DB::table('tenants')
                    ->whereIn('id', $tenantIds)
                    ->get()
                    ->keyBy('id');

                $taxSettings = DB::table('tenant_tax_settings')
                    ->whereIn('tenant_id', $tenantIds)
                    ->get()
                    ->keyBy('tenant_id');

                foreach ($invoices as $invoice) {
                    $tenant = $tenants->get($invoice->tenant_id);

                    if (! $tenant) {
                        continue;
                    }

                    DB::table('invoices')->where('id', $invoice->id)->update([
                        'seller_name_snapshot' => $tenant->legal_name
                            ?: ($tenant->legal_name_en ?: ($tenant->trade_name ?: $tenant->name)),
                        'seller_name_en_snapshot' => $tenant->legal_name_en
                            ?: ($tenant->legal_name ?: ($tenant->trade_name ?: $tenant->name)),
                        'seller_vat_snapshot' => $taxSettings->get($tenant->id)?->tax_number
                            ?: $tenant->vat_number,
                        'seller_cr_snapshot' => $tenant->cr_number,
                    ]);
                }
            });
    }

    private function backfillSubscriptionInvoices(): void
    {
        $settings = DB::table('payment_settings')->first();

        if (! $settings) {
            return;
        }

        DB::table('subscription_invoices')
            ->whereNull('seller_name_snapshot')
            ->update([
                'seller_name_snapshot' => $settings->company_legal_name
                    ?: ($settings->company_legal_name_en ?: config('app.name', 'Khidmh Pro')),
                'seller_name_en_snapshot' => $settings->company_legal_name_en
                    ?: ($settings->company_legal_name ?: config('app.name', 'Khidmh Pro')),
                'seller_vat_snapshot' => $settings->company_vat_number,
                'seller_cr_snapshot' => $settings->company_cr_number,
            ]);
    }
};
