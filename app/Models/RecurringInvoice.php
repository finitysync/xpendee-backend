<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class RecurringInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recurring_invoices';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'frequency',
        'next_run_at',
        'last_run_at',
        'status',
        'template',
    ];

    protected function casts(): array
    {
        return [
            'template'    => 'array',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDue($query)
    {
        return $query->active()->where('next_run_at', '<=', now());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Calculate the next run date based on frequency.
     */
    public function calculateNextRunAt(): Carbon
    {
        $base = $this->next_run_at ?? now();

        return match ($this->frequency) {
            'daily'     => $base->copy()->addDay(),
            'weekly'    => $base->copy()->addWeek(),
            'monthly'   => $base->copy()->addMonth(),
            'quarterly' => $base->copy()->addMonths(3),
            'yearly'    => $base->copy()->addYear(),
            default     => $base->copy()->addMonth(),
        };
    }
}
