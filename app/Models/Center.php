<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Center extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'name_ar',
        'name_en',
        'slug',
        'is_active',
        'is_main',
        // Profile
        'manager_name',
        'center_type',
        'license_number',
        'vat_number',
        // Contact
        'phone',
        'email',
        // Branding
        'logo_light_path',
        'logo_dark_path',
        'logo_invoice_path',
        'stamp_path',
        // Document Titles
        'quote_title',
        'work_order_title',
        'invoice_title',
        // Terms & Conditions
        'quote_terms',
        'work_order_terms',
        'invoice_terms',
        // Visual Print Settings
        'print_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'print_settings' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'center_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function address(): HasOne
    {
        return $this->hasOne(CenterAddress::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(CenterWorkingHour::class)->orderBy('day_of_week');
    }

    // Logo URL accessors
    public function getLogoLightUrlAttribute(): ?string
    {
        return $this->logo_light_path ? Storage::url($this->logo_light_path) : null;
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        if ($this->logo_dark_path) {
            return Storage::url($this->logo_dark_path);
        }
        // Fallback to light logo if dark is not set
        return $this->logo_light_path ? Storage::url($this->logo_light_path) : null;
    }

    public function getLogoInvoiceUrlAttribute(): ?string
    {
        return $this->logo_invoice_path ? Storage::url($this->logo_invoice_path) : null;
    }

    public function getStampUrlAttribute(): ?string
    {
        return $this->stamp_path ? Storage::url($this->stamp_path) : null;
    }

    // Get display name based on locale
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        if ($locale === 'en' && $this->name_en) {
            return $this->name_en;
        }
        return $this->name_ar ?? $this->name_en ?? $this->name;
    }
}
