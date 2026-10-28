<?php

declare(strict_types=1);

namespace App\Services\Zatca;

use App\Models\Tenant;
use App\Models\TenantZatcaSetting;
use App\Support\ArabicNumeralConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ZatcaOnboardingService
{
    /**
     * ZATCA API Endpoints.
     */
    public const ENDPOINTS = [
        'sandbox' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal',
        'simulation' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation',
        'production' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core',
    ];

    /**
     * The four document types ZATCA asks an onboarding EGS to submit
     * for validation before a production CSID is issued.
     */
    public const COMPLIANCE_DOCUMENTS = [
        ['name' => 'فاتورة ضريبية مبسطة (Simplified Tax Invoice)', 'code' => 'ZATCA-SIMP-01'],
        ['name' => 'إشعار دائن مبسط (Simplified Credit Note)', 'code' => 'ZATCA-CR-02'],
        ['name' => 'فاتورة ضريبية قياسية (Standard Tax Invoice)', 'code' => 'ZATCA-STD-03'],
        ['name' => 'إشعار مدين قياسي (Standard Debit Note)', 'code' => 'ZATCA-DB-04'],
    ];

    /**
     * Marker for a compliance CSID this application fabricated locally.
     */
    public const SIMULATED_CSID_PREFIX = 'CSID_SIM_';

    /**
     * Marker for a production CSID this application fabricated locally.
     *
     * The prefix is deliberately NOT `PROD_CSID_`: a simulated value
     * must never be mistakable for a certificate issued by ZATCA.
     */
    public const SIMULATED_PRODUCTION_CSID_PREFIX = 'SIM_PROD_CSID_';

    public const SIMULATED_PRODUCTION_SECRET_PREFIX = 'SIM_PROD_SEC_';

    /**
     * Validate that the tenant meets all prerequisites for ZATCA Phase 2 onboarding.
     *
     * @return array<string> List of validation error messages, or empty if eligible.
     */
    public function validatePrerequisites(Tenant $tenant): array
    {
        $errors = [];
        $taxSettings = $tenant->taxSettings;
        $vatNumber = $taxSettings?->tax_number ?? $tenant->vat_number;

        if (! ($taxSettings?->vat_enabled ?? false)) {
            $errors[] = 'ضريبة القيمة المضافة غير مفعلة في النظام.';
        }

        if (empty($vatNumber)) {
            $errors[] = 'الرقم الضريبي للمنشأة غير مسجل.';
        } elseif (! preg_match('/^3[0-9]{13}3$/', (string) $vatNumber)) {
            $errors[] = 'الرقم الضريبي غير صحيح (يجب أن يتكون من 15 رقماً ويبدأ وينتهي بالرقم 3).';
        }

        $companyName = $tenant->legal_name ?: $tenant->name;

        if (empty($companyName)) {
            $errors[] = 'اسم المنشأة غير مسجل.';
        }

        return $errors;
    }

    /**
     * Check if tenant is eligible to start Phase 2 onboarding.
     */
    public function isEligibleForPhase2(Tenant $tenant): bool
    {
        return empty($this->validatePrerequisites($tenant));
    }

    /**
     * Step 1: Generate Private Key and CSR according to ZATCA standards.
     */
    public function generateCsr(Tenant $tenant, string $environment = 'simulation', ?string $egsSerial = null): TenantZatcaSetting
    {
        $errors = $this->validatePrerequisites($tenant);

        if (! empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $setting = TenantZatcaSetting::firstOrNew(['tenant_id' => $tenant->id]);

        $companyName = $tenant->legal_name ?: $tenant->name ?: 'Carag Workshop';
        $serial = $egsSerial ?: ('1-CARAG|2-V2|3-'.Str::upper(Str::random(10)));

        // Generate ECDSA secp256k1 key pair
        $privateKeyPem = '';
        $csrPem = '';
        $keyError = null;

        [$privateKeyPem, $csrPem, $keyError] = $this->buildKeyPairAndCsr($companyName);

        // There is deliberately NO fallback that fabricates a PEM.
        // The old fallback produced a random blob dressed up as an EC
        // private key and a "certificate request" that was really just
        // base64 of a VAT number — ZATCA rejects both, so the tenant
        // would only discover it at the very last step. Failing loudly
        // here is the honest outcome.
        if ($keyError !== null) {
            throw new RuntimeException(
                'تعذر توليد مفتاح التوقيع الإلكتروني (EC/secp256k1) أو طلب الشهادة (CSR): '.$keyError
            );
        }

        $setting->fill([
            'environment' => $environment,
            'onboarding_status' => TenantZatcaSetting::STATUS_CSR_READY,
            'egs_serial_number' => $serial,
            'csr_content' => $csrPem,
            'private_key' => $privateKeyPem,
            'last_onboarding_error' => null,
        ]);

        $setting->save();

        return $setting;
    }

    /**
     * Build a real ECDSA (secp256k1) key pair and a CSR for it.
     *
     * PHP validates `private_key_bits` for every key type, EC included.
     * Without it `openssl_pkey_new()` fails on PHP 8.4+, which is why
     * this service used to fall through to a fabricated PEM on every
     * single call. 384 is the minimum PHP accepts; the actual key size
     * of an EC key comes from the curve (secp256k1 = 256 bit).
     *
     * @return array{0: string, 1: string, 2: string|null} [privateKeyPem, csrPem, error]
     */
    private function buildKeyPairAndCsr(string $companyName): array
    {
        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp256k1',
        ];

        $keyPair = @openssl_pkey_new($config);

        if ($keyPair === false) {
            return ['', '', self::opensslError() ?? 'فشل توليد زوج المفاتيح.'];
        }

        $privateKeyPem = '';

        if (! @openssl_pkey_export($keyPair, $privateKeyPem) || $privateKeyPem === '') {
            return ['', '', 'فشل تصدير المفتاح الخاص.'];
        }

        $dn = [
            'CN' => $companyName,
            'O' => $companyName,
            'OU' => 'Branch 01',
            'C' => 'SA',
        ];

        $csrResource = @openssl_csr_new($dn, $keyPair, ['digest_alg' => 'sha256']);

        if ($csrResource === false) {
            return ['', '', self::opensslError() ?? 'فشل إنشاء طلب الشهادة (CSR).'];
        }

        $csrPem = '';

        if (! @openssl_csr_export($csrResource, $csrPem) || $csrPem === '') {
            return ['', '', 'فشل تصدير طلب الشهادة (CSR).'];
        }

        return [$privateKeyPem, $csrPem, null];
    }

    /**
     * Collect the OpenSSL error queue as a readable message.
     *
     * The queue is drained, so only messages produced by the call that
     * just failed are returned. The RNG seeding warning some OpenSSL
     * builds emit on every successful operation is filtered out — it
     * is an environment artifact, not a key-generation failure.
     */
    private static function opensslError(): ?string
    {
        $messages = [];

        while ($error = openssl_error_string()) {
            if (str_contains($error, 'random number generator')) {
                continue;
            }

            $messages[] = $error;
        }

        return $messages === [] ? null : implode(' | ', $messages);
    }

    /**
     * Step 2: Request Compliance CSID using OTP from Fatoora Portal.
     */
    public function requestComplianceCsid(Tenant $tenant, string $otp): array
    {
        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->firstOrFail();

        if (empty($setting->csr_content)) {
            throw new InvalidArgumentException('يجب توليد طلب التوقيع (CSR) أولاً قبل طلب شهادة الامتثال.');
        }

        $cleanOtp = trim((string) ArabicNumeralConverter::toEnglish($otp));

        if (strlen($cleanOtp) < 6) {
            throw new InvalidArgumentException('رمز التحقق (OTP) يجب أن يتكون من 6 أرقام على الأقل.');
        }

        $isProduction = ($setting->environment === TenantZatcaSetting::ENV_PRODUCTION || $setting->environment === 'production');

        if ($cleanOtp === '123456' && $isProduction) {
            $errorMsg = 'الرمز التجريبي (123456) غير مسموح به في بيئة الإنتاج الحية. يجب إدخال رمز OTP حقيقي صادر من بوابة فاتورة لمنشأتك.';
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        $isDevOrTest = app()->environment('local', 'testing');
        $isMockOtp = ($cleanOtp === '123456' && $isDevOrTest && ! $isProduction);

        $endpoint = (self::ENDPOINTS[$setting->environment] ?? self::ENDPOINTS['simulation']).'/compliance';
        $csid = '';
        $secret = '';

        if ($isMockOtp) {
            $csid = 'CSID_SIM_'.Str::upper(Str::random(32));
            $secret = 'SECRET_SIM_'.Str::random(24);
        } else {
            try {
                // Remote call to ZATCA gateway
                $response = Http::timeout(10)
                    ->withHeaders([
                        'OTP' => $cleanOtp,
                        'Accept-Version' => 'V2',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpoint, [
                        'csr' => base64_encode($setting->csr_content),
                    ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $csid = $body['binarySecurityToken'] ?? '';
                    $secret = $body['secret'] ?? '';

                    if (empty($csid)) {
                        throw new InvalidArgumentException('استجابة هيئة الزكاة لم تحتوي على شهادة الامتثال (binarySecurityToken).');
                    }
                } else {
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['errors'][0]['message']
                        ?? $errorBody['message']
                        ?? $errorBody['error']
                        ?? ('فشل التحقق من هيئة الزكاة (رمز الاستجابة: '.$response->status().'). يرجى التأكد من صحة رمز OTP وصلاحيته.');

                    $setting->last_onboarding_error = $errorMsg;
                    $setting->save();

                    throw new InvalidArgumentException($errorMsg);
                }
            } catch (InvalidArgumentException $e) {
                throw $e;
            } catch (Throwable $e) {
                // If network/connectivity failure occurs in local dev environment, allow simulation fallback
                if ($isDevOrTest && str_starts_with($setting->environment, 'sim')) {
                    $csid = 'CSID_SIM_'.Str::upper(Str::random(32));
                    $secret = 'SECRET_SIM_'.Str::random(24);
                } else {
                    $errorMsg = 'تعذر الاتصال ببوابة هيئة الزكاة والضريبة والجمارك: '.$e->getMessage();
                    $setting->last_onboarding_error = $errorMsg;
                    $setting->save();

                    throw new InvalidArgumentException($errorMsg);
                }
            }
        }

        $setting->compliance_csid = $csid;
        $setting->compliance_secret = $secret;
        $setting->onboarding_status = TenantZatcaSetting::STATUS_COMPLIANCE_PASSED;
        $setting->last_onboarding_error = null;
        $setting->save();

        return [
            'status' => 'success',
            'compliance_csid' => $csid,
            'message' => 'تم استلام شهادة الامتثال المؤقتة بنجاح.',
        ];
    }

    /**
     * Step 3: Run the ZATCA compliance checks.
     *
     * ZATCA asks an onboarding EGS to submit four sample documents
     * (simplified tax invoice, simplified credit note, standard tax
     * invoice, standard debit note) and have the gateway validate
     * them. This codebase has no UBL 2.1 document builder, XML signer
     * or QR encoder, so those four documents cannot be produced here.
     * A check that was never executed must never be reported as
     * PASSED — that is exactly the false success this method used to
     * return.
     *
     * What IS verified here is verified for real:
     *  - the EGS credentials (CSR + EC private key + compliance CSID),
     *  - the reachability of the configured ZATCA gateway.
     *
     * The invoice checks are reported as NOT_EXECUTED together with the
     * reason, and the onboarding status is NOT advanced on their
     * behalf.
     *
     * @return array{status: string, message: string, all_passed: bool, checks: list<array<string, mixed>>}
     */
    public function runComplianceChecks(Tenant $tenant): array
    {
        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->firstOrFail();

        if (empty($setting->compliance_csid)) {
            throw new InvalidArgumentException('شهادة الامتثال غير موجودة. يرجى إدخال الـ OTP أولاً.');
        }

        $checks = [
            $this->checkEgsCredentials($setting),
            $this->checkGatewayReachability($setting),
        ];

        foreach (self::COMPLIANCE_DOCUMENTS as $document) {
            $checks[] = [
                'name' => $document['name'],
                'code' => $document['code'],
                'status' => 'NOT_EXECUTED',
                'executed' => false,
                'detail' => 'لم تُنفَّذ: لا يحتوي هذا الإصدار نظام توليد وتوقيع مستندات UBL 2.1 المطلوب لإرسال عينة فاتورة إلى هيئة الزكاة. يجب اجتياز هذه الفحوصات يدوياً من بوابة فاتورة قبل طلب شهادة الإنتاج.',
            ];
        }

        $executed = array_values(array_filter($checks, fn (array $check): bool => $check['executed']));
        $failed = array_values(array_filter($executed, fn (array $check): bool => $check['status'] !== 'PASSED'));

        if ($failed !== []) {
            $status = 'failed';
            $message = 'فشل '.$this->arabicCount(count($failed)).' من الفحوصات القابلة للتنفيذ. راجع التفاصيل قبل المتابعة.';
            $setting->last_onboarding_error = $message;
            $setting->save();
        } else {
            $status = 'incomplete';
            $message = 'نجحت الفحوصات القابلة للتنفيذ. أما فحص عينات الفواتير الأربع فيحتاج نظام توقيع مستندات UBL غير المتوفر في هذا الإصدار، ويجب اجتيازه يدوياً من بوابة فاتورة قبل طلب شهادة الإنتاج.';
        }

        return [
            'status' => $status,
            'message' => $message,
            // Never true while the four invoice checks are unexecuted.
            'all_passed' => false,
            'checks' => $checks,
        ];
    }

    /**
     * Verify the EGS credential material.
     *
     * The private key has to be a real EC key that OpenSSL can read
     * back — a fabricated PEM would sail through a "is it non-empty?"
     * check and only fail at ZATCA.
     *
     * @return array<string, mixed>
     */
    private function checkEgsCredentials(TenantZatcaSetting $setting): array
    {
        $problems = [];

        if (empty($setting->csr_content)) {
            $problems[] = 'ملف طلب الشهادة (CSR) غير موجود.';
        } elseif (! str_contains((string) $setting->csr_content, 'CERTIFICATE REQUEST')) {
            $problems[] = 'ملف طلب الشهادة (CSR) ليس بصيغة PEM صالحة.';
        }

        $privateKey = (string) $setting->private_key;

        if ($privateKey === '') {
            $problems[] = 'المفتاح الخاص للوحدة غير موجود.';
        } elseif (! str_contains($privateKey, 'PRIVATE KEY')) {
            $problems[] = 'المفتاح الخاص للوحدة ليس بصيغة PEM صالحة.';
        } elseif (@openssl_pkey_get_private($privateKey) === false) {
            $problems[] = 'تعذّر قراءة المفتاح الخاص للوحدة، فهو ليس مفتاحاً صالحاً.';
        }

        if (empty($setting->compliance_csid)) {
            $problems[] = 'شهادة الامتثال غير موجودة.';
        } elseif ($this->isSimulatedCsid((string) $setting->compliance_csid)) {
            $problems[] = 'شهادة الامتثال الحالية شهادة محاكاة محلية وليست شهادة صادرة من هيئة الزكاة.';
        }

        return [
            'name' => 'سلامة بيانات اعتماد الوحدة (EGS Credentials)',
            'code' => 'ZATCA-CRED-00',
            'status' => $problems === [] ? 'PASSED' : 'FAILED',
            'executed' => true,
            'detail' => $problems === []
                ? 'تم التحقق من مفتاح التوقيع وطلب الشهادة وشهادة الامتثال.'
                : implode(' ', $problems),
        ];
    }

    /**
     * Check that the configured ZATCA gateway answers.
     *
     * Any HTTP response proves the endpoint is reachable; only a
     * connection failure counts as unreachable.
     *
     * @return array<string, mixed>
     */
    private function checkGatewayReachability(TenantZatcaSetting $setting): array
    {
        $environment = (string) ($setting->environment ?: 'simulation');
        $base = self::ENDPOINTS[$environment] ?? null;

        if ($base === null) {
            return [
                'name' => 'الوصول إلى بوابة هيئة الزكاة (Gateway Reachability)',
                'code' => 'ZATCA-GW-01',
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'بيئة غير معروفة: '.$environment.'.',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept-Version' => 'V2'])
                ->get($base.'/compliance');

            return [
                'name' => 'الوصول إلى بوابة هيئة الزكاة (Gateway Reachability)',
                'code' => 'ZATCA-GW-01',
                'status' => 'PASSED',
                'executed' => true,
                'detail' => 'الاستجابة من بوابة هيئة الزكاة (رمز '.$response->status().').',
            ];
        } catch (Throwable $e) {
            return [
                'name' => 'الوصول إلى بوابة هيئة الزكاة (Gateway Reachability)',
                'code' => 'ZATCA-GW-01',
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'تعذر الاتصال ببوابة هيئة الزكاة: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Arabic-Indic numeral for a small count, for the UI message.
     */
    private function arabicCount(int $count): string
    {
        return (string) ArabicNumeralConverter::toEnglish($count);
    }

    /**
     * Step 4: Exchange the Compliance CSID for a Production CSID.
     *
     * Only the live production gateway issues production CSIDs, and
     * only after a documented exchange. Outside a real server the
     * result is an unmistakably-labelled simulation that never turns
     * the tenant into live Phase 2 mode.
     *
     * @return array{status: string, production_csid: string, expires_at: string|null, message: string}
     */
    public function requestProductionCsid(Tenant $tenant): array
    {
        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->firstOrFail();

        if (empty($setting->compliance_csid)) {
            throw new InvalidArgumentException('شهادة الامتثال غير متوفرة لإصدار شهادة الإنتاج.');
        }

        $environment = (string) ($setting->environment ?: 'simulation');
        $isProduction = ($environment === TenantZatcaSetting::ENV_PRODUCTION || $environment === 'production');

        if ($isProduction) {
            return $this->exchangeProductionCsidWithGateway($setting);
        }

        // A non-production environment never issues a production CSID.
        // The only thing left to do is a clearly-labelled simulation,
        // and only outside a real server.
        if (! app()->environment('local', 'testing')) {
            $errorMsg = 'إصدار شهادة الإنتاج متاح فقط عبر البيئة الإنتاجية الحية (production). البيئة الحالية: '.$environment.'.';
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        if (! $this->isSimulatedCsid((string) $setting->compliance_csid)) {
            $errorMsg = 'لا يمكن إصدار شهادة إنتاج خارج البيئة الإنتاجية الحية. البيئة الحالية: '.$environment.'.';
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        return $this->createSimulatedProductionCsid($setting);
    }

    /**
     * The real thing: exchange the compliance CSID at ZATCA.
     *
     * @return array{status: string, production_csid: string, expires_at: string|null, message: string}
     */
    private function exchangeProductionCsidWithGateway(TenantZatcaSetting $setting): array
    {
        if ($this->isSimulatedCsid((string) $setting->compliance_csid)) {
            $errorMsg = 'شهادة الامتثال الحالية هي شهادة محاكاة تجريبية ولا يمكن استخدامها لإصدار شهادة إنتاج حية. يرجى إعادة طلب شهادة الامتثال برمز OTP حقيقي.';
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        $base = self::ENDPOINTS[$setting->environment] ?? self::ENDPOINTS['production'];

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Accept-Version' => 'V2',
                    'Content-Type' => 'application/json',
                ])
                ->post($base.'/production/csids', [
                    'complianceCsid' => $setting->compliance_csid,
                ]);
        } catch (Throwable $e) {
            $errorMsg = 'تعذر الاتصال ببوابة هيئة الزكاة والضريبة والجمارك: '.$e->getMessage();
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        if (! $response->successful()) {
            $errorMsg = $this->extractZatcaError($response);
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        $body = $response->json();
        $prodCsid = (string) ($body['binarySecurityToken'] ?? '');
        $prodSecret = (string) ($body['secret'] ?? '');

        if ($prodCsid === '' || $prodSecret === '') {
            $errorMsg = 'استجابة هيئة الزكاة لم تحتوي على شهادة الإنتاج (binarySecurityToken) أو على سرها.';
            $setting->last_onboarding_error = $errorMsg;
            $setting->save();

            throw new InvalidArgumentException($errorMsg);
        }

        $setting->production_csid = $prodCsid;
        $setting->production_secret = $prodSecret;
        $setting->production_csid_expires_at = $this->parseExpiration($body['expirationDate'] ?? null);
        $setting->onboarding_status = TenantZatcaSetting::STATUS_ACTIVE;
        $setting->qr_enabled = true;
        $setting->onboarded_at = Carbon::now();
        $setting->last_onboarding_error = null;
        $setting->save();

        return [
            'status' => 'success',
            'production_csid' => $prodCsid,
            'expires_at' => $setting->production_csid_expires_at?->toIso8601String(),
            'message' => 'تم إصدار شهادة الإنتاج بنجاح وتفعيل الربط الحي مع هيئة الزكاة والضريبة والجمارك.',
        ];
    }

    /**
     * The development stand-in for a production CSID.
     *
     * The value is prefixed `SIM_PROD_CSID_`, the onboarding status
     * becomes STATUS_SIMULATED (never STATUS_ACTIVE) and the QR flag
     * stays off, so nothing in the billing side can mistake this for a
     * live integration.
     *
     * @return array{status: string, production_csid: string, expires_at: string|null, message: string}
     */
    private function createSimulatedProductionCsid(TenantZatcaSetting $setting): array
    {
        $simulatedCsid = self::SIMULATED_PRODUCTION_CSID_PREFIX.Str::upper(Str::random(24));
        $simulatedSecret = self::SIMULATED_PRODUCTION_SECRET_PREFIX.Str::random(24);

        $setting->production_csid = $simulatedCsid;
        $setting->production_secret = $simulatedSecret;
        $setting->production_csid_expires_at = null;
        $setting->onboarding_status = TenantZatcaSetting::STATUS_SIMULATED;
        $setting->qr_enabled = false;
        $setting->onboarded_at = Carbon::now();
        $setting->last_onboarding_error = null;
        $setting->save();

        return [
            'status' => 'simulated',
            'production_csid' => $simulatedCsid,
            'expires_at' => null,
            'message' => 'تم إنشاء معرّف إنتاج تجريبي (محاكاة محلية فقط). هذه ليست شهادة صادرة من هيئة الزكاة، ولم يتم تفعيل الربط الحي ولا إرسال الفواتير.',
        ];
    }

    /**
     * Is this credential one this application fabricated?
     */
    private function isSimulatedCsid(string $csid): bool
    {
        return str_starts_with($csid, self::SIMULATED_CSID_PREFIX)
            || str_starts_with($csid, self::SIMULATED_PRODUCTION_CSID_PREFIX);
    }

    /**
     * Pull a human-readable message out of a ZATCA error response.
     */
    private function extractZatcaError($response): string
    {
        $body = $response->json();
        $message = $body['errors'][0]['message'] ?? $body['message'] ?? $body['error'] ?? null;

        if (is_array($message)) {
            $message = $message['message'] ?? json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        if (! is_string($message) || $message === '') {
            $message = 'فشل تبادل شهادة الامتثال مع هيئة الزكاة والضريبة والجمارك (رمز الاستجابة: '.$response->status().').';
        }

        return $message;
    }

    /**
     * Parse the expiration date ZATCA returns with the CSID.
     */
    private function parseExpiration(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Reset onboarding to restart the wizard.
     */
    public function resetOnboarding(Tenant $tenant): void
    {
        $setting = TenantZatcaSetting::firstOrNew(['tenant_id' => $tenant->id]);
        $setting->fill([
            'onboarding_status' => TenantZatcaSetting::STATUS_NOT_STARTED,
            // The QR flag used to survive a reset, so a tenant that
            // reset a live onboarding still had invoice QR generation
            // switched on with no certificate behind it.
            'qr_enabled' => false,
            'csr_content' => null,
            'private_key' => null,
            'compliance_csid' => null,
            'compliance_secret' => null,
            'production_csid' => null,
            'production_secret' => null,
            'production_csid_expires_at' => null,
            'onboarded_at' => null,
            'last_onboarding_error' => null,
        ]);
        $setting->save();
    }
}
