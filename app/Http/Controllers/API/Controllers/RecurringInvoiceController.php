<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecurringInvoice;
use App\Models\Invoice;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    /**
     * GET /api/recurring
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->id;

        $recurring = RecurringInvoice::forTenant($tenantId)
            ->with('client')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => $this->format($r));

        return response()->json([
            'success' => true,
            'data'    => $recurring,
        ]);
    }

    /**
     * POST /api/recurring
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->id;

        $validated = $request->validate([
            'client_id'   => 'nullable|integer|exists:clients,id',
            'frequency'   => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'next_run_at' => 'required|date',
            'status'      => 'nullable|in:active,paused',
            'template'    => 'required|array',
            'template.items'       => 'required|array|min:1',
            'template.items.*.description' => 'required|string',
            'template.items.*.unitPrice'   => 'required|numeric|min:0',
            'template.notes'       => 'nullable|string',
            'template.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $recurring = RecurringInvoice::create([
            'tenant_id'   => $tenantId,
            'client_id'   => $validated['client_id'] ?? null,
            'frequency'   => $validated['frequency'],
            'next_run_at' => $validated['next_run_at'],
            'status'      => $validated['status'] ?? 'active',
            'template'    => $validated['template'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recurring invoice created.',
            'data'    => $this->format($recurring->load('client')),
        ], 201);
    }

    /**
     * GET /api/recurring/{id}
     */
    public function show(Request $request, int $id)
    {
        $recurring = RecurringInvoice::forTenant($request->user()->id)
            ->with('client')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->format($recurring),
        ]);
    }

    /**
     * PUT /api/recurring/{id}
     */
    public function update(Request $request, int $id)
    {
        $tenantId  = $request->user()->id;
        $recurring = RecurringInvoice::forTenant($tenantId)->findOrFail($id);

        $validated = $request->validate([
            'client_id'   => 'nullable|integer|exists:clients,id',
            'frequency'   => 'sometimes|required|in:daily,weekly,monthly,quarterly,yearly',
            'next_run_at' => 'sometimes|required|date',
            'status'      => 'nullable|in:active,paused',
            'template'    => 'sometimes|required|array',
        ]);

        $recurring->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Recurring invoice updated.',
            'data'    => $this->format($recurring->fresh()->load('client')),
        ]);
    }

    /**
     * DELETE /api/recurring/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $recurring = RecurringInvoice::forTenant($request->user()->id)->findOrFail($id);
        $recurring->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recurring invoice deleted.',
        ]);
    }

    /**
     * POST /api/recurring/{id}/toggle
     * Pause ↔ Resume
     */
    public function toggle(Request $request, int $id)
    {
        $recurring = RecurringInvoice::forTenant($request->user()->id)->findOrFail($id);

        $newStatus = $recurring->status === 'active' ? 'paused' : 'active';
        $recurring->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Recurring invoice {$newStatus}.",
            'data'    => $this->format($recurring->fresh()),
        ]);
    }

    /**
     * POST /api/recurring/{id}/run-now
     * Manually trigger invoice generation for this recurring item.
     */
    public function runNow(Request $request, int $id)
    {
        $tenant    = $request->user();
        $recurring = RecurringInvoice::forTenant($tenant->id)
            ->with('client')
            ->findOrFail($id);

        $invoice = $this->generateInvoice($recurring, $tenant);

        // Advance next_run_at
        $recurring->update([
            'last_run_at' => now(),
            'next_run_at' => $recurring->calculateNextRunAt(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Invoice #{$invoice->number} generated.",
            'data'    => $invoice,
        ]);
    }

    // ─── Internal: generate invoice from recurring template ──────────────────

    public function generateInvoice(RecurringInvoice $recurring, $tenant): Invoice
    {
        $template   = $recurring->template ?? [];
        $taxPercent = (float) ($template['tax_percent'] ?? $tenant->invoice_tax_percent ?? 0);
        $items      = $template['items'] ?? [];
        $subtotal   = collect($items)->sum(fn ($i) => (float) ($i['unitPrice'] ?? 0));
        $tax        = ($subtotal * $taxPercent) / 100;
        $total      = $subtotal + $tax;
        $prefix     = $tenant->invoice_prefix ?? 'INV';

        return Invoice::create([
            'tenant_id'   => $recurring->tenant_id,
            'client_id'   => $recurring->client_id,
            'number'      => Invoice::generateNumber($recurring->tenant_id, $prefix),
            'status'      => 'sent',
            'issue_date'  => now()->toDateString(),
            'due_date'    => now()->addDays($tenant->invoice_due_days ?? 30)->toDateString(),
            'items'       => $items,
            'payments'    => [],
            'notes'       => $template['notes'] ?? null,
            'subtotal'    => $subtotal,
            'tax'         => $tax,
            'total'       => $total,
            'amount_paid' => 0,
            'sent_at'     => now(),
        ]);
    }

    // ─── Format helper ────────────────────────────────────────────────────────

    private function format(RecurringInvoice $r): array
    {
        $template = $r->template ?? [];
        $items    = $template['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($i) => (float) ($i['unitPrice'] ?? 0));

        return [
            'id'          => (string) $r->id,
            'tenant_id'   => $r->tenant_id,
            'client_id'   => $r->client_id ? (string) $r->client_id : null,
            'frequency'   => $r->frequency,
            'next_run_at' => $r->next_run_at?->toIso8601String(),
            'last_run_at' => $r->last_run_at?->toIso8601String(),
            'status'      => $r->status,
            'template'    => $template,
            'created_at'  => $r->created_at?->toIso8601String(),
            // Frontend-compat aliases
            'clientName'  => $r->client?->name ?? '',
            'clientEmail' => $r->client?->email ?? '',
            'lineItems'   => array_map(fn ($i) => [
                'id'          => $i['id'] ?? uniqid(),
                'description' => $i['description'] ?? '',
                'amount'      => (float) ($i['unitPrice'] ?? 0),
            ], $items),
            'amount'      => $subtotal,
        ];
    }
}
