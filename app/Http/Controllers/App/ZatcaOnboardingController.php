<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\TenantZatcaSetting;
use App\Services\Zatca\ZatcaOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ZatcaOnboardingController extends Controller
{
    public function __construct(
        protected ZatcaOnboardingService $zatcaService
    ) {}

    /**
     * Display the ZATCA Phase 2 Self-Onboarding Wizard.
     */
    public function index(): Response
    {
        $tenant = auth()->user()->tenant;
        $setting = TenantZatcaSetting::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['onboarding_status' => TenantZatcaSetting::STATUS_NOT_STARTED]
        );

        return Inertia::render('Settings/Zatca/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'legal_name' => $tenant->legal_name ?? $tenant->name,
                'vat_number' => $tenant->taxSettings?->tax_number ?? $tenant->vat_number ?? '',
            ],
            'setting' => [
                'environment' => $setting->environment ?? 'simulation',
                'onboarding_status' => $setting->onboarding_status ?? 'not_started',
                'egs_serial_number' => $setting->egs_serial_number,
                'has_csr' => ! empty($setting->csr_content),
                'has_compliance_csid' => ! empty($setting->compliance_csid),
                'has_production_csid' => ! empty($setting->production_csid),
                'production_csid_expires_at' => $setting->production_csid_expires_at?->format('Y-m-d'),
                'onboarded_at' => $setting->onboarded_at?->format('Y-m-d H:i'),
                'qr_enabled' => $setting->qr_enabled,
                'is_active' => $setting->isPhase2Active(),
                'last_error' => $setting->last_onboarding_error,
            ],
        ]);
    }

    /**
     * Step 1: Generate CSR and ECDSA private key.
     */
    public function generateCsr(Request $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $environment = $request->input('environment', 'simulation');
        $egsSerial = $request->input('egs_serial_number');

        $this->zatcaService->generateCsr($tenant, $environment, $egsSerial);

        return back()->with('success', 'تم توليد ملف طلب الشهادة (CSR) والمفتاح الخاص بنجاح.');
    }

    /**
     * Step 2: Request Compliance CSID with OTP.
     */
    public function requestCompliance(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|min:6',
        ]);

        $tenant = auth()->user()->tenant;

        try {
            $this->zatcaService->requestComplianceCsid($tenant, $request->input('otp'));

            return back()->with('success', 'تم استلام شهادة الامتثال المؤقتة بنجاح.');
        } catch (Throwable $e) {
            return back()->withErrors(['otp' => $e->getMessage()]);
        }
    }

    /**
     * Step 3: Run Compliance sample checks.
     */
    public function runChecks(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        try {
            $result = $this->zatcaService->runComplianceChecks($tenant);

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Step 4: Request Production CSID and activate Phase 2.
     */
    public function requestProduction(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        try {
            $this->zatcaService->requestProductionCsid($tenant);

            return back()->with('success', 'مبروك! تم تفعيل الربط الإنتاجي مع هيئة الزكاة والضريبة والجمارك بنجاح.');
        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reset onboarding wizard.
     */
    public function reset(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $this->zatcaService->resetOnboarding($tenant);

        return back()->with('success', 'تمت إعادة ضبط معالج الربط بنجاح.');
    }
}
