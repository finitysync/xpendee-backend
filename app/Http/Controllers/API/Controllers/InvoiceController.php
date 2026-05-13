<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\EmailHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * GET /api/invoices
     * List invoices with optional filters.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->id;

        $query = Invoice::forTenant($tenantId)
            ->with('client')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('issue_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('issue_date', '<=', $request->to);
        }

        $invoices = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $invoices,
        ]);
    }

    /**
     * POST /api/invoices
     * Create a new invoice.
     */
    public function store(Request $request)
    {
        $tenant   = $request->user();
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'client_id'   => 'nullable|integer|exists:clients,id',
            'number'      => 'nullable|string|max:50',
            'status'      => 'nullable|in:draft,sent,paid,partial,overdue,cancelled',
            'issue_date'  => 'required|date',
            'due_date'    => 'nullable|date',
            'items'       => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.unitPrice'   => 'required|numeric|min:0',
            'payments'    => 'nullable|array',
            'notes'       => 'nullable|string',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Auto-generate number if not provided
        $prefix = $tenant->invoice_prefix ?? 'INV';
        $number = $validated['number'] ?? Invoice::generateNumber($tenantId, $prefix);

        // Compute totals
        $taxPercent = $validated['tax_percent'] ?? ($tenant->invoice_tax_percent ?? 0);
        $subtotal   = collect($validated['items'])->sum(fn($i) => (float)($i['unitPrice'] ?? 0));
        $tax        = ($subtotal * $taxPercent) / 100;
        $total      = $subtotal + $tax;
        $amountPaid = collect($validated['payments'] ?? [])->sum(fn($p) => (float)($p['amount'] ?? 0));

        $invoice = Invoice::create([
            'tenant_id'   => $tenantId,
            'client_id'   => $validated['client_id'] ?? null,
            'number'      => $number,
            'status'      => $validated['status'] ?? 'draft',
            'issue_date'  => $validated['issue_date'],
            'due_date'    => $validated['due_date'] ?? null,
            'items'       => $validated['items'],
            'payments'    => $validated['payments'] ?? [],
            'notes'       => $validated['notes'] ?? null,
            'subtotal'    => $subtotal,
            'tax'         => $tax,
            'total'       => $total,
            'amount_paid' => $amountPaid,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created.',
            'data'    => $invoice->load('client'),
        ], 201);
    }

    /**
     * GET /api/invoices/{id}
     */
    public function show(Request $request, int $id)
    {
        $invoice = Invoice::forTenant($request->user()->id)
            ->with('client')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $invoice,
        ]);
    }

    /**
     * PUT /api/invoices/{id}
     */
    public function update(Request $request, int $id)
    {
        $tenant   = $request->user();
        $tenantId = $tenant->id;

        $invoice = Invoice::forTenant($tenantId)->findOrFail($id);

        $validated = $request->validate([
            'client_id'   => 'nullable|integer|exists:clients,id',
            'number'      => 'nullable|string|max:50',
            'status'      => 'nullable|in:draft,sent,paid,partial,overdue,cancelled',
            'issue_date'  => 'sometimes|required|date',
            'due_date'    => 'nullable|date',
            'items'       => 'sometimes|required|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.unitPrice'   => 'required_with:items|numeric|min:0',
            'payments'    => 'nullable|array',
            'notes'       => 'nullable|string',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Recompute totals if items changed
        if (isset($validated['items'])) {
            $taxPercent = $validated['tax_percent'] ?? ($tenant->invoice_tax_percent ?? 0);
            $subtotal   = collect($validated['items'])->sum(fn($i) => (float)($i['unitPrice'] ?? 0));
            $tax        = ($subtotal * $taxPercent) / 100;
            $total      = $subtotal + $tax;
            $validated['subtotal'] = $subtotal;
            $validated['tax']      = $tax;
            $validated['total']    = $total;
        }

        if (isset($validated['payments'])) {
            $validated['amount_paid'] = collect($validated['payments'])->sum(fn($p) => (float)($p['amount'] ?? 0));
        }

        unset($validated['tax_percent']);
        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated.',
            'data'    => $invoice->fresh()->load('client'),
        ]);
    }

    /**
     * DELETE /api/invoices/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $invoice = Invoice::forTenant($request->user()->id)->findOrFail($id);
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted.',
        ]);
    }

    /**
     * POST /api/invoices/{id}/send
     * Generate PDF and email the invoice to the client.
     */
    public function send(Request $request, int $id)
    {
        $tenant  = $request->user();
        $invoice = Invoice::forTenant($tenant->id)->with('client')->findOrFail($id);

        $validated = $request->validate([
            'to'      => 'nullable|email',
            'subject' => 'nullable|string',
            'body'    => 'nullable|string',
        ]);

        $toEmail = $validated['to'] ?? $invoice->client?->email;

        if (!$toEmail) {
            return response()->json([
                'success' => false,
                'message' => 'No recipient email address found.',
            ], 422);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenant'  => $tenant,
        ]);

        // Send mail
        Mail::to($toEmail)->send(new InvoiceMail(
            invoice:  $invoice,
            tenant:   $tenant,
            pdf:      $pdf->output(),
            subject:  $validated['subject'] ?? "Invoice #{$invoice->number}",
            body:     $validated['body'] ?? '',
        ));

        // Update status to sent
        $invoice->update([
            'status'  => $invoice->status === 'draft' ? 'sent' : $invoice->status,
            'sent_at' => now(),
        ]);

        // Log to history
        EmailHistory::log(
            tenantId:  $tenant->id,
            to:        $toEmail,
            subject:   $validated['subject'] ?? "Invoice #{$invoice->number}",
            type:      'invoice',
            relatedId: $invoice->id
        );

        return response()->json([
            'success' => true,
            'message' => "Invoice sent to {$toEmail}.",
            'data'    => $invoice->fresh(),
        ]);
    }

    /**
     * POST /api/invoices/{id}/payment
     * Record a payment against an invoice.
     */
    public function payment(Request $request, int $id)
    {
        $invoice = Invoice::forTenant($request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date'   => 'required|date',
            'note'   => 'nullable|string',
        ]);

        $payments = $invoice->payments ?? [];
        $payments[] = [
            'id'     => uniqid('pmt_'),
            'amount' => (float) $validated['amount'],
            'date'   => $validated['date'],
            'note'   => $validated['note'] ?? '',
        ];

        $amountPaid = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
        $newStatus  = $amountPaid >= $invoice->total ? 'paid'
                    : ($amountPaid > 0 ? 'partial' : $invoice->status);

        $invoice->update([
            'payments'    => $payments,
            'amount_paid' => $amountPaid,
            'status'      => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded.',
            'data'    => $invoice->fresh(),
        ]);
    }
}
