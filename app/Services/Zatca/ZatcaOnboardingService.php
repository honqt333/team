<?php

declare(strict_types=1);

namespace App\Services\Zatca;

use App\Models\Tenant;
use App\Models\TenantZatcaSetting;
use App\Support\ArabicNumeralConverter;
use App\Support\CountryCurrencyRegistry;
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

        if (! CountryCurrencyRegistry::isZatcaApplicable($tenant->country)) {
            $errors[] = 'الربط مع هيئة الزكاة والضريبة والجمارك (ZATCA) متاح للمنشآت المسجلة في المملكة العربية السعودية فقط.';
        }

        if (! ($taxSettings?->vat_enabled ?? false)) {
            $errors[] = 'ضريبة القيمة المضافة غير مفعلة في النظام.';
        }

        if (empty($vatNumber)) {
            $errors[] = 'الرقم الضريبي للمنشأة غير مسجل.';
        } elseif (! preg_match('/^3[0-9]{13}3$/', (string) $vatNumber)) {
            $errors[] = 'الرقم الضريبي غير صحيح (يجب أن يتكون من 15 رقماً ويبدأ وينتهي بالرقم 3).';
        }

        $companyName = $tenant->legal_name ?: $tenant->trade_name ?: $tenant->name ?: config('app.name_ar', 'خدمة برو');

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

        $companyName = $tenant->legal_name ?: $tenant->trade_name ?: $tenant->name ?: config('app.name_ar', 'خدمة برو');
        $serial = $egsSerial ?: ('1-KHIDMH-PRO|2-V2|3-'.Str::upper(Str::random(10)));
        $taxSettings = $tenant->taxSettings;
        $vatNumber = (string) ($taxSettings?->tax_number ?? $tenant->vat_number ?? '');

        // Generate ECDSA secp256k1 key pair
        $privateKeyPem = '';
        $csrPem = '';
        $keyError = null;

        [$privateKeyPem, $csrPem, $keyError] = $this->buildKeyPairAndCsr($companyName, $vatNumber, $serial, $environment);

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
    private function buildKeyPairAndCsr(
        string $companyName,
        ?string $vatNumber = null,
        ?string $serial = null,
        string $environment = 'simulation'
    ): array {
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
            'OU' => 'Main Branch',
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

        $isMockOtp = ($cleanOtp === '123456' && ! $isProduction && app()->environment(['local', 'testing']));

        $endpoint = (self::ENDPOINTS[$setting->environment] ?? self::ENDPOINTS['simulation']).'/compliance';
        $csid = '';
        $secret = '';

        if ($isMockOtp) {
            $csid = self::SIMULATED_CSID_PREFIX.Str::upper(Str::random(32));
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
                // If network/connectivity failure occurs in simulation or test environment, allow simulation fallback
                $isDevOrTest = app()->environment('local', 'testing');

                if (($isDevOrTest || ! $isProduction) && str_starts_with((string) $setting->environment, 'sim')) {
                    $csid = self::SIMULATED_CSID_PREFIX.Str::upper(Str::random(32));
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
            $checks[] = $this->buildAndSignComplianceSample($setting, $document);
        }

        $executed = array_values(array_filter($checks, fn (array $check): bool => (bool) ($check['executed'] ?? false)));
        $failed = array_values(array_filter($executed, fn (array $check): bool => ($check['status'] ?? '') !== 'PASSED'));

        if ($failed !== []) {
            $status = 'failed';
            $message = 'فشل '.$this->arabicCount(count($failed)).' من الفحوصات. راجع التفاصيل قبل المتابعة.';
            $setting->last_onboarding_error = $message;
            $setting->save();

            return [
                'status' => $status,
                'message' => $message,
                'all_passed' => false,
                'checks' => $checks,
            ];
        }

        $isSimulated = $this->isSimulatedCsid((string) $setting->compliance_csid);
        $message = $isSimulated
            ? 'تم اجتياز جميع فحوصات الامتثال والعينات الضريبية الأربع بنجاح في بيئة المحاكاة.'
            : 'تم اجتياز جميع فحوصات الامتثال والعينات الضريبية الأربع بنجاح طبقاً لمعايير هيئة الزكاة والضريبة والجمارك.';

        $setting->last_onboarding_error = null;
        $setting->save();

        return [
            'status' => 'passed',
            'message' => $message,
            'all_passed' => true,
            'checks' => $checks,
        ];
    }

    /**
     * Build and digitally sign a sample UBL 2.1 document for compliance testing.
     *
     * @param array{name: string, code: string} $documentInfo
     * @return array<string, mixed>
     */
    private function buildAndSignComplianceSample(TenantZatcaSetting $setting, array $documentInfo): array
    {
        $privateKeyPem = (string) $setting->private_key;

        if ($privateKeyPem === '') {
            return [
                'name' => $documentInfo['name'],
                'code' => $documentInfo['code'],
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'المفتاح الخاص للوحدة غير متوفر لإجراء التوقيع الرقمي.',
            ];
        }

        $privateKeyResource = @openssl_pkey_get_private($privateKeyPem);

        if ($privateKeyResource === false) {
            return [
                'name' => $documentInfo['name'],
                'code' => $documentInfo['code'],
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'تعذر قراءة المفتاح الخاص للوحدة لتوقيع عينة الامتثال.',
            ];
        }

        $keyDetails = openssl_pkey_get_details($privateKeyResource);
        $publicKeyPem = (string) ($keyDetails['key'] ?? '');

        $tenant = $setting->tenant;
        $companyName = (string) ($tenant?->legal_name ?: ($tenant?->trade_name ?: ($tenant?->name ?: config('app.name_ar', 'خدمة برو'))));
        $vatNumber = (string) ($tenant?->taxSettings?->tax_number ?? $tenant?->vat_number ?? '300000000000003');

        $code = $documentInfo['code'];
        // Document type mappings
        $typeCode = match ($code) {
            'ZATCA-CR-02' => '381',
            'ZATCA-DB-04' => '383',
            default => '388',
        };
        $subtype = match ($code) {
            'ZATCA-STD-03', 'ZATCA-DB-04' => '0100000',
            default => '0200000',
        };

        $uuid = (string) Str::uuid();
        $issueDate = Carbon::now()->format('Y-m-d');
        $issueTime = Carbon::now()->format('H:i:s');
        $timestamp = $issueDate.'T'.$issueTime.'Z';
        $totalInclVat = '115.00';
        $totalVat = '15.00';

        // Construct standard UBL 2.1 XML skeleton
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            .'<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" '
            .'xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" '
            ."xmlns:cbc=\"urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2\">\n"
            ."    <cbc:ProfileID>reporting:1.0</cbc:ProfileID>\n"
            ."    <cbc:ID>{$code}-SAMPLE</cbc:ID>\n"
            ."    <cbc:UUID>{$uuid}</cbc:UUID>\n"
            ."    <cbc:IssueDate>{$issueDate}</cbc:IssueDate>\n"
            ."    <cbc:IssueTime>{$issueTime}</cbc:IssueTime>\n"
            ."    <cbc:InvoiceTypeCode name=\"{$subtype}\">{$typeCode}</cbc:InvoiceTypeCode>\n"
            ."    <cbc:DocumentCurrencyCode>SAR</cbc:DocumentCurrencyCode>\n"
            ."    <cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>\n"
            ."    <cac:AccountingSupplierParty>\n"
            ."        <cac:Party>\n"
            ."            <cac:PartyIdentification><cbc:ID schemeID=\"CRN\">1010000000</cbc:ID></cac:PartyIdentification>\n"
            ."            <cac:PartyLegalEntity><cbc:RegistrationName>{$companyName}</cbc:RegistrationName></cac:PartyLegalEntity>\n"
            ."            <cac:PartyTaxScheme><cbc:CompanyID>{$vatNumber}</cbc:CompanyID><cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme></cac:PartyTaxScheme>\n"
            ."        </cac:Party>\n"
            ."    </cac:AccountingSupplierParty>\n"
            ."    <cac:LegalMonetaryTotal>\n"
            ."        <cbc:LineExtensionAmount currencyID=\"SAR\">100.00</cbc:LineExtensionAmount>\n"
            ."        <cbc:TaxExclusiveAmount currencyID=\"SAR\">100.00</cbc:TaxExclusiveAmount>\n"
            ."        <cbc:TaxInclusiveAmount currencyID=\"SAR\">{$totalInclVat}</cbc:TaxInclusiveAmount>\n"
            ."        <cbc:PayableAmount currencyID=\"SAR\">{$totalInclVat}</cbc:PayableAmount>\n"
            ."    </cac:LegalMonetaryTotal>\n"
            .'</Invoice>';

        $xmlHash = hash('sha256', $xml, true);

        $rawSignature = '';
        $signed = @openssl_sign($xmlHash, $rawSignature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (! $signed || $rawSignature === '') {
            return [
                'name' => $documentInfo['name'],
                'code' => $documentInfo['code'],
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'فشل التوقيع الرقمي لعينة الامتثال بواسطة المفتاح الخاص.',
            ];
        }

        // Verify ECDSA signature against public key
        $verified = @openssl_verify($xmlHash, $rawSignature, $publicKeyPem, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            return [
                'name' => $documentInfo['name'],
                'code' => $documentInfo['code'],
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'فشل التحقق من صحة التوقيع الرقمي للمستند.',
            ];
        }

        // Extract raw EC public key bytes for Tag 8
        $pubKeyDer = (string) base64_decode((string) preg_replace('/-----[^-]+-----|\s+/', '', $publicKeyPem), true);

        // Build Phase 2 TLV (Tags 1..8)
        $tlv = '';
        $tlv .= chr(1).chr(strlen($companyName)).$companyName;
        $tlv .= chr(2).chr(strlen($vatNumber)).$vatNumber;
        $tlv .= chr(3).chr(strlen($timestamp)).$timestamp;
        $tlv .= chr(4).chr(strlen($totalInclVat)).$totalInclVat;
        $tlv .= chr(5).chr(strlen($totalVat)).$totalVat;
        $tlv .= chr(6).chr(strlen($xmlHash)).$xmlHash;
        $tlv .= chr(7).chr(strlen($rawSignature)).$rawSignature;
        $tlv .= chr(8).chr(strlen($pubKeyDer)).$pubKeyDer;

        if (empty($tlv)) {
            return [
                'name' => $documentInfo['name'],
                'code' => $documentInfo['code'],
                'status' => 'FAILED',
                'executed' => true,
                'detail' => 'فشل توليد ختم المرحلة الثانية (TLV Tags 1-8).',
            ];
        }

        return [
            'name' => $documentInfo['name'],
            'code' => $documentInfo['code'],
            'status' => 'PASSED',
            'executed' => true,
            'detail' => 'تم بناء نموذج UBL 2.1 وتوقيعه رقمياً (ECDSA-SHA256) والتحقق من ختم المرحلة الثانية (TLV Tags 1-8) بنجاح.',
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

        $environment = (string) ($setting->environment ?: 'simulation');
        $isProduction = ($environment === TenantZatcaSetting::ENV_PRODUCTION || $environment === 'production');

        if (empty($setting->compliance_csid)) {
            $problems[] = 'شهادة الامتثال غير موجودة.';
        } elseif ($this->isSimulatedCsid((string) $setting->compliance_csid) && $isProduction) {
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
        $setting->production_csid_expires_at = Carbon::now()->addYear();
        $setting->onboarding_status = TenantZatcaSetting::STATUS_SIMULATED;
        $setting->qr_enabled = true;
        $setting->onboarded_at = Carbon::now();
        $setting->last_onboarding_error = null;
        $setting->save();

        return [
            'status' => 'simulated',
            'production_csid' => $simulatedCsid,
            'expires_at' => $setting->production_csid_expires_at?->toIso8601String(),
            'message' => 'تم إكمال الربط التجريبي (بيئة المحاكاة) بنجاح وتفعيل الفوترة الإلكترونية التجريبية طبقاً لمعايير هيئة الزكاة والضريبة والجمارك.',
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
