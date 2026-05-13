<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'title',
        'body',
        'status',
        'share_token',
        'signed_at',
        'signer_name',
        'signer_email',
        'signature_data',
        'otp',
        'otp_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at'      => 'datetime',
            'otp_expires_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate a unique share token for the public signing URL.
     */
    public static function generateShareToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Generate a 6-digit OTP and set expiry to 10 minutes.
     */
    public function generateOtp(): string
    {
        $otp = (string) random_int(100000, 999999);
        $this->update([
            'otp'            => bcrypt($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);
        return $otp;
    }

    /**
     * Verify if the given plain-text OTP is correct and not expired.
     */
    public function verifyOtp(string $plainOtp): bool
    {
        if (!$this->otp || !$this->otp_expires_at) {
            return false;
        }

        if (now()->isAfter($this->otp_expires_at)) {
            return false;
        }

        return password_verify($plainOtp, $this->otp);
    }

    /**
     * Check if this contract is signable (sent + not already signed).
     */
    public function isSignable(): bool
    {
        return in_array($this->status, ['sent']);
    }
}
