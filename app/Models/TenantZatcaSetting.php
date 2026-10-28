<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * @property string|null $csr_content
 * @property string|null $private_key
 * @property string|null $compliance_secret
 * @property string|null $production_secret
 * @property bool $secrets_encrypted
 */
class TenantZatcaSetting extends Model
{
    use TenantScoped;

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_CSR_READY = 'csr_ready';

    public const STATUS_COMPLIANCE_PASSED = 'compliance_passed';

    public const STATUS_ACTIVE = 'active';

    /**
     * The onboarding completed against a locally fabricated credential.
     *
     * This state is only reachable outside a real server. It exists so
     * a simulated credential can never be mistaken for a live ZATCA
     * certificate: `isPhase2Active()` only answers true for
     * STATUS_ACTIVE, and the QR flag is never switched on here.
     */
    public const STATUS_SIMULATED = 'simulated';

    public const STATUS_FAILED = 'failed';

    public const ENV_SANDBOX = 'sandbox';

    public const ENV_SIMULATION = 'simulation';

    public const ENV_PRODUCTION = 'production';

    /**
     * The secret columns that are encrypted at rest.
     *
     * @var list<string>
     */
    public const SECRET_COLUMNS = [
        'csr_content',
        'private_key',
        'compliance_secret',
        'production_secret',
    ];

    protected $fillable = [
        'tenant_id',
        'qr_enabled',
        'environment',
        'onboarding_status',
        'egs_serial_number',
        'csr_content',
        'private_key',
        'compliance_csid',
        'compliance_secret',
        'production_csid',
        'production_secret',
        'production_csid_expires_at',
        'last_onboarding_error',
        'onboarded_at',
    ];

    protected $casts = [
        'qr_enabled' => 'boolean',
        'production_csid_expires_at' => 'datetime',
        'onboarded_at' => 'datetime',
        'secrets_encrypted' => 'boolean',
    ];

    /**
     * The secret columns are never included in the model's array form.
     *
     * The accessors decrypt on read, so a plain toArray() would hand
     * out the plaintext private key and CSID secrets. The controller
     * currently picks the safe fields explicitly, but relying on every
     * future call site to remember that is exactly how a secret ends
     * up in a browser payload. Hiding the columns makes the safe
     * behaviour the default.
     *
     * @var list<string>
     */
    protected $hidden = [
        'csr_content',
        'private_key',
        'compliance_secret',
        'production_secret',
    ];

    /**
     * Are this row's secret columns encrypted at rest?
     */
    public function hasEncryptedSecrets(): bool
    {
        return (bool) ($this->attributes['secrets_encrypted'] ?? false);
    }

    /**
     * Decrypt a secret column when the row holds ciphertext.
     *
     * Rows that were never converted still hold plaintext and are
     * returned untouched. The `secrets_encrypted` flag is what makes
     * the data conversion independent of the code deploy: whichever
     * state a row is in, the application reads usable values.
     */
    protected function decryptSecret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! $this->hasEncryptedSecrets()) {
            return $value;
        }

        try {
            return decrypt($value, false);
        } catch (Throwable) {
            // A value that cannot be decrypted (e.g. it was written
            // after the flag was flipped but before the encryption was
            // applied) is returned as-is rather than blowing up the
            // whole record.
            return $value;
        }
    }

    /**
     * Encrypt a secret column when the row is flagged as encrypted.
     */
    protected function encryptSecret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! $this->hasEncryptedSecrets()) {
            return $value;
        }

        try {
            return encrypt($value, false);
        } catch (Throwable) {
            return $value;
        }
    }

    /**
     * Decrypt the CSR content on read.
     */
    protected function csrContent(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->decryptSecret($value),
            set: fn (?string $value) => $this->encryptSecret($value),
        );
    }

    /**
     * Decrypt the EGS private key on read.
     */
    protected function privateKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->decryptSecret($value),
            set: fn (?string $value) => $this->encryptSecret($value),
        );
    }

    /**
     * Decrypt the compliance CSID secret on read.
     */
    protected function complianceSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->decryptSecret($value),
            set: fn (?string $value) => $this->encryptSecret($value),
        );
    }

    /**
     * Decrypt the production CSID secret on read.
     */
    protected function productionSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->decryptSecret($value),
            set: fn (?string $value) => $this->encryptSecret($value),
        );
    }

    /**
     * Encrypt this row's secret columns and persist them.
     *
     * Called when a setting is created or updated through the
     * application, so new secrets are never written in plaintext.
     */
    public function markSecretsEncrypted(): void
    {
        // Set the flag first, so the mutators encrypt on assignment.
        $this->attributes['secrets_encrypted'] = true;

        foreach (self::SECRET_COLUMNS as $column) {
            $value = $this->getAttribute($column);

            if ($value === null || $value === '') {
                continue;
            }

            $this->attributes[$column] = encrypt((string) $value, false);
        }
    }

    /**
     * Never write a secret in plaintext.
     *
     * Every write path (the onboarding service, the onboarding
     * controller, a future admin action) goes through save(), so the
     * encryption is applied here rather than at each call site — there
     * is no way to add a new write path and forget it.
     *
     * The values are read through the accessors first (which decrypt
     * an already-encrypted row), and written back encrypted.
     */
    protected static function booted(): void
    {
        static::saving(function (self $setting) {
            if ($setting->hasEncryptedSecrets()) {
                // The row is already flagged as encrypted; the mutators
                // encrypt on assignment, so nothing to do.
                return;
            }

            $setting->markSecretsEncrypted();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPhase2Active(): bool
    {
        return $this->onboarding_status === self::STATUS_ACTIVE && ! empty($this->production_csid);
    }

    /**
     * Is this row holding a locally fabricated (simulated) credential?
     *
     * A simulated row is a development convenience, never a live ZATCA
     * certificate. The UI and the billing code use this to refuse to
     * treat it as a production integration.
     */
    public function isSimulated(): bool
    {
        return $this->onboarding_status === self::STATUS_SIMULATED;
    }
}
