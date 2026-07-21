<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// @bypass-tenancy-scanner - Public contact form, pre-signup (no tenant yet)
class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'subject', 'message'];
}
