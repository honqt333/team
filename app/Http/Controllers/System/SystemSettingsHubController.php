<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\DunningConfig;
use App\Models\Billing\DunningReminderLog;
use App\Models\PaymentSettings;
use App\Models\Setting;
use App\Services\Billing\DunningService;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsHubController extends Controller
{
    public function __construct(
        protected DunningService $dunning,
        protected ?SubscriptionLifecycleService $lifecycle = null
    ) {
        $this->lifecycle ??= app(SubscriptionLifecycleService::class);
    }

    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'general');

        if (! in_array($tab, ['general', 'company', 'payment', 'dunning'], true)) {
            $tab = 'general';
        }

        // 1. General Settings
        $generalSettings = [
            'security_2fa_sms_enabled' => Setting::get('security.2fa_sms_enabled', 'false') === 'true',
            'subscription_grace_period_days' => $this->lifecycle->getGracePeriodDays(),
            'brand_logo_url' => Setting::get('system.brand_logo') ?: null,
            'brand_favicon_url' => Setting::get('system.brand_favicon') ?: null,
        ];

        // 2. Company Settings
        $paymentSettingsRow = PaymentSettings::first() ?? new PaymentSettings;
        $companySettings = [
            'company_legal_name' => $paymentSettingsRow->company_legal_name,
            'company_legal_name_en' => $paymentSettingsRow->company_legal_name_en,
            'company_vat_number' => $paymentSettingsRow->company_vat_number,
            'company_cr_number' => $paymentSettingsRow->company_cr_number,
            'company_phone' => $paymentSettingsRow->company_phone,
            'company_email' => $paymentSettingsRow->company_email,
            'company_website' => $paymentSettingsRow->company_website,
            'company_logo_url' => $paymentSettingsRow->company_logo_path
                ? Storage::url($paymentSettingsRow->company_logo_path)
                : null,
            'company_stamp_url' => $paymentSettingsRow->company_stamp_path
                ? Storage::url($paymentSettingsRow->company_stamp_path)
                : null,
            'subscription_vat_enabled' => (bool) ($paymentSettingsRow->subscription_vat_enabled ?? false),
            'subscription_vat_rate' => (float) ($paymentSettingsRow->subscription_vat_rate ?? 15),
            'subscription_vat_inclusive' => (bool) ($paymentSettingsRow->subscription_vat_inclusive ?? false),
        ];

        // 3. Payment Gateways Settings
        $paymentGatewaysSettings = [
            'moyasar' => $this->maskGatewayConfig($paymentSettingsRow->moyasar),
            'tap' => $this->maskGatewayConfig($paymentSettingsRow->tap),
            'paytabs' => $this->maskGatewayConfig($paymentSettingsRow->paytabs),
            'tabby' => $this->maskGatewayConfig($paymentSettingsRow->tabby),
            'tamara' => $this->maskGatewayConfig($paymentSettingsRow->tamara),
            'default_gateway' => $paymentSettingsRow->default_gateway,
            'bank_transfer_enabled' => (bool) $paymentSettingsRow->bank_transfer_enabled,
            'bank_accounts' => $paymentSettingsRow->bank_accounts ?? [],
            'bank_transfer_instructions' => $paymentSettingsRow->bank_transfer_instructions,
            'bank_transfer_require_receipt' => (bool) ($paymentSettingsRow->bank_transfer_require_receipt ?? true),
            'bank_transfer_max_days' => $paymentSettingsRow->bank_transfer_max_days ?? 7,
            'bank_transfer_min_amount' => (float) ($paymentSettingsRow->bank_transfer_min_amount ?? 0),
            'bank_transfer_receipt_instructions' => $paymentSettingsRow->bank_transfer_receipt_instructions,
            'mada_enabled' => (bool) ($paymentSettingsRow->mada_enabled ?? true),
            'visa_enabled' => (bool) ($paymentSettingsRow->visa_enabled ?? true),
            'mastercard_enabled' => (bool) ($paymentSettingsRow->mastercard_enabled ?? true),
            'applepay_enabled' => (bool) ($paymentSettingsRow->applepay_enabled ?? true),
            'stcpay_enabled' => (bool) ($paymentSettingsRow->stcpay_enabled ?? false),
        ];

        // 4. Dunning Automation Settings & Logs
        $globalDunning = $this->dunning->ensureGlobalDefault();
        $dunningConfig = [
            'enabled' => $globalDunning->enabled,
            'active_tiers' => $globalDunning->active_tiers,
            'channel_matrix' => $globalDunning->channel_matrix ?: DunningConfig::defaultChannelMatrix(),
            'quiet_hours_start' => $globalDunning->quiet_hours_start ? $globalDunning->quiet_hours_start->format('H:i') : null,
            'quiet_hours_end' => $globalDunning->quiet_hours_end ? $globalDunning->quiet_hours_end->format('H:i') : null,
            'auto_suspend_after_days' => $globalDunning->auto_suspend_after_days,
        ];

        $dunningLogs = null;

        if ($tab === 'dunning') {
            $logsQuery = DunningReminderLog::with('tenant');

            if ($request->filled('status')) {
                $logsQuery->where('status', $request->query('status'));
            }

            if ($request->filled('tier')) {
                $logsQuery->where('tier_key', $request->query('tier'));
            }

            if ($request->filled('tenant_id')) {
                $logsQuery->where('tenant_id', (int) $request->query('tenant_id'));
            }
            $dunningLogs = $logsQuery->latest('id')->paginate(20, ['*'], 'page')->withQueryString();
        }

        return Inertia::render('System/Settings/SettingsHub', [
            'tab' => $tab,
            'sub' => $request->query('sub', 'rules'),
            'generalSettings' => $generalSettings,
            'companySettings' => $companySettings,
            'paymentSettings' => $paymentGatewaysSettings,
            'dunningConfig' => $dunningConfig,
            'tierDefinitions' => DunningConfig::tierDefinitions(),
            'defaultChannelMatrix' => DunningConfig::defaultChannelMatrix(),
            'dunningLogs' => $dunningLogs,
            'dunningFilters' => [
                'status' => $request->query('status'),
                'tier' => $request->query('tier'),
                'tenant_id' => $request->query('tenant_id'),
            ],
        ]);
    }

    private function maskGatewayConfig(?array $config): array
    {
        if (! $config) {
            return ['enabled' => false];
        }

        $masked = $config;

        foreach (['secret_key', 'server_key', 'api_token', 'notification_token'] as $key) {
            if (! empty($masked[$key])) {
                $masked[$key.'_masked'] = '••••••••'.substr($masked[$key], -4);
                $masked[$key] = '';
            }
        }

        return $masked;
    }
}
