<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'number',
        'status',
        'issue_date',
        'due_date',
        'items',
        'payments',
        'notes',
        'subtotal',
        'tax',
        'total',
        'amount_paid',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'items'      => 'array',
            'payments'   => 'array',
            'issue_date' => 'date',
            'due_date'   => 'date',
            'sent_at'    => 'datetime',
            'subtotal'   => 'decimal:2',
            'tax'        => 'decimal:2',
            'total'      => 'decimal:2',
            'amount_paid'=> 'decimal:2',
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
     * Generate next invoice number for a tenant.
     * Format: PREFIX-YYYY-NNN (e.g. INV-2026-001)
     */
    public static function generateNumber(int $tenantId, string $prefix = 'INV'): string
    {
        $year = now()->year;

        $lastInvoice = self::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($lastInvoice) {
            // Extract sequence from last invoice number
            $parts = explode('-', $lastInvoice->number);
            $lastSeq = (int) end($parts);
            $seq = $lastSeq + 1;
        }

        return sprintf('%s-%d-%03d', strtoupper($prefix), $year, $seq);
    }

    /**
     * Compute totals from items array.
     */
    public function computeTotals(float $taxPercent = 0): array
    {
        $items    = $this->items ?? [];
        $subtotal = collect($items)->sum(fn($i) => (float)($i['unitPrice'] ?? 0));
        $tax      = ($subtotal * $taxPercent) / 100;
        $total    = $subtotal + $tax;

        return compact('subtotal', 'tax', 'total');
    }
}
