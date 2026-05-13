<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'to_email',
        'subject',
        'type',
        'related_id',
        'status',
        'error_message',
    ];

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    /**
     * Scope a query to only include emails for a specific tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Create a log entry for a sent email.
     */
    public static function log(int $tenantId, string $to, string $subject, string $type, ?int $relatedId = null): self
    {
        return self::create([
            'tenant_id'  => $tenantId,
            'to_email'   => $to,
            'subject'    => $subject,
            'type'       => $type,
            'related_id' => $relatedId,
            'status'     => 'sent',
        ]);
    }
}
