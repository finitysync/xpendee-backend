<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Tenant extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'logo',
        'address',
        'app_name',
        'primary_color',
        'trial_ends_at',
        'status',
        'plan',
        'plan_duration',
        'plan_expires_at',
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_pass',
        'smtp_from_name',
        'smtp_from_email',
        'invoice_prefix',
        'invoice_tax_percent',
        'invoice_due_days',
    ];

    protected $hidden = [
        'password',
        'smtp_pass',
        'totp_secret',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'plan_expires_at' => 'datetime',
        ];
    }

    // Accessors
    public function getIsTrialActiveAttribute(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsSuspendedAttribute(): bool
    {
        return $this->status === 'suspended';
    }
}
