<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    protected $fillable = [
        'type',
        'provider',
        'name',
        'name_ar',
        'description',
        'config',
        'is_active',
        'is_default',
        'purpose',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function tenantIntegrations(): HasMany
    {
        return $this->hasMany(TenantIntegration::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    /**
     * Get config value (decrypted if sensitive).
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set config value.
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
        $this->save();
    }

    /**
     * Check if integration is configured.
     */
    public function isConfigured(): bool
    {
        return match ($this->provider) {
            'unifonic' => !empty($this->config['app_sid']),
            'twilio' => !empty($this->config['account_sid']) && !empty($this->config['auth_token']),
            '360dialog' => !empty($this->config['api_key']),
            'whatsapp_cloud' => !empty($this->config['access_token']) && !empty($this->config['phone_number_id']),
            'mailgun' => !empty($this->config['api_key']) && !empty($this->config['domain']),
            'smtp' => !empty($this->config['host']) && !empty($this->config['username']),
            'authentica' => !empty($this->config['api_key']),
            default => false,
        };
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'sms' => 'رسائل SMS',
            'whatsapp' => 'واتساب',
            'email' => 'البريد الإلكتروني',
            'storage' => 'التخزين',
            default => $this->type,
        };
    }

    /**
     * Scope by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope active.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get default integration for type.
     */
    public static function getDefault(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get integration for a specific purpose.
     * Falls back to default or any active integration if no specific purpose found.
     * 
     * @param string $type The integration type (sms, whatsapp, email)
     * @param string $purpose The purpose (otp, notifications)
     */
    public static function getForPurpose(string $type, string $purpose): ?self
    {
        // First try to find integration with specific purpose
        $integration = self::where('type', $type)
            ->where('is_active', true)
            ->where('purpose', $purpose)
            ->first();
        
        if ($integration) {
            return $integration;
        }
        
        // Fall back to "all" purpose
        $integration = self::where('type', $type)
            ->where('is_active', true)
            ->where('purpose', 'all')
            ->first();
        
        if ($integration) {
            return $integration;
        }
        
        // Fall back to default
        return self::getDefault($type);
    }

    /**
     * Get available purposes.
     */
    public static function getPurposes(): array
    {
        return [
            'all' => ['label' => 'الكل', 'description' => 'لجميع الرسائل'],
            'otp' => ['label' => 'رموز التحقق', 'description' => 'للتحقق الثنائي فقط'],
            'notifications' => ['label' => 'الإشعارات', 'description' => 'للرسائل الإشعارية فقط'],
        ];
    }

    /**
     * Get available providers for type.
     */
    public static function getProviders(string $type): array
    {
        return match ($type) {
            'sms' => [
                'unifonic' => ['name' => 'Unifonic', 'name_ar' => 'يونيفونك', 'icon' => '📱'],
                'twilio' => ['name' => 'Twilio', 'name_ar' => 'تويليو', 'icon' => '📲'],
                'msegat' => ['name' => 'Msegat', 'name_ar' => 'مسجات', 'icon' => '💬'],
                'authentica' => ['name' => 'Authentica', 'name_ar' => 'أوثينتيكا', 'icon' => '🔐'],
            ],
            'whatsapp' => [
                'whatsapp_cloud' => ['name' => 'WhatsApp Cloud API', 'name_ar' => 'واتساب كلاود', 'icon' => '☁️'],
                '360dialog' => ['name' => '360dialog', 'name_ar' => '360 دايلوق', 'icon' => '🔄'],
                'twilio_whatsapp' => ['name' => 'Twilio WhatsApp', 'name_ar' => 'تويليو واتساب', 'icon' => '📱'],
            ],
            'email' => [
                'smtp' => ['name' => 'SMTP', 'name_ar' => 'SMTP', 'icon' => '📧'],
                'mailgun' => ['name' => 'Mailgun', 'name_ar' => 'ميلغن', 'icon' => '🔫'],
                'sendgrid' => ['name' => 'SendGrid', 'name_ar' => 'سيندغريد', 'icon' => '📨'],
                'ses' => ['name' => 'Amazon SES', 'name_ar' => 'أمازون SES', 'icon' => '☁️'],
            ],
            default => [],
        };
    }

    /**
     * Get config fields for provider.
     */
    public static function getConfigFields(string $provider): array
    {
        return match ($provider) {
            'unifonic' => [
                ['key' => 'app_sid', 'label' => 'App SID', 'type' => 'text', 'required' => true],
                ['key' => 'sender_id', 'label' => 'Sender ID', 'type' => 'text', 'required' => true],
            ],
            'twilio' => [
                ['key' => 'account_sid', 'label' => 'Account SID', 'type' => 'text', 'required' => true],
                ['key' => 'auth_token', 'label' => 'Auth Token', 'type' => 'password', 'required' => true],
                ['key' => 'from_number', 'label' => 'From Number', 'type' => 'text', 'required' => true],
            ],
            'msegat' => [
                ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'sender_name', 'label' => 'Sender Name', 'type' => 'text', 'required' => true],
            ],
            'authentica' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
            ],
            'whatsapp_cloud' => [
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'phone_number_id', 'label' => 'Phone Number ID', 'type' => 'text', 'required' => true],
                ['key' => 'business_account_id', 'label' => 'Business Account ID', 'type' => 'text', 'required' => false],
            ],
            '360dialog' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'phone_number', 'label' => 'Phone Number', 'type' => 'text', 'required' => true],
            ],
            'smtp' => [
                ['key' => 'host', 'label' => 'SMTP Host', 'type' => 'text', 'required' => true],
                ['key' => 'port', 'label' => 'Port', 'type' => 'number', 'required' => true],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                ['key' => 'encryption', 'label' => 'Encryption', 'type' => 'select', 'options' => ['tls', 'ssl', 'none'], 'required' => false],
                ['key' => 'from_address', 'label' => 'From Address', 'type' => 'email', 'required' => true],
                ['key' => 'from_name', 'label' => 'From Name', 'type' => 'text', 'required' => true],
            ],
            'mailgun' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'domain', 'label' => 'Domain', 'type' => 'text', 'required' => true],
                ['key' => 'from_address', 'label' => 'From Address', 'type' => 'email', 'required' => true],
                ['key' => 'from_name', 'label' => 'From Name', 'type' => 'text', 'required' => true],
            ],
            'sendgrid' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'from_address', 'label' => 'From Address', 'type' => 'email', 'required' => true],
                ['key' => 'from_name', 'label' => 'From Name', 'type' => 'text', 'required' => true],
            ],
            default => [],
        };
    }
}
