<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #{{ $invoice->number }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }

    .page { width: 100%; max-width: 800px; margin: 0 auto; padding: 40px; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 28px; }
    .brand-logo { width: 48px; height: 48px; background: #4169e1; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 900; color: #fff; }
    .brand-name { font-size: 22px; font-weight: 900; color: #0f172a; margin-top: 6px; }
    .brand-tagline { font-size: 10px; color: #94a3b8; letter-spacing: 1.5px; text-transform: uppercase; }
    .invoice-label { text-align: right; }
    .invoice-label h1 { font-size: 28px; font-weight: 900; color: #4169e1; letter-spacing: -0.5px; }
    .invoice-label .inv-number { font-size: 13px; color: #64748b; margin-top: 4px; }

    /* Status badge */
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 6px; }
    .status-paid     { background: #dcfce7; color: #166534; }
    .status-draft    { background: #f1f5f9; color: #475569; }
    .status-sent     { background: #dbeafe; color: #1e40af; }
    .status-partial  { background: #fef3c7; color: #92400e; }
    .status-overdue  { background: #fee2e2; color: #991b1b; }
    .status-cancelled{ background: #f1f5f9; color: #94a3b8; }

    /* Meta row */
    .meta-row { display: flex; justify-content: space-between; margin-bottom: 28px; gap: 20px; }
    .meta-box { flex: 1; }
    .meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 6px; }
    .meta-value { font-size: 13px; color: #1e293b; line-height: 1.6; }
    .meta-value strong { font-weight: 700; font-size: 14px; }

    /* Items table */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .items-table thead tr { background: #f8fafc; }
    .items-table th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    .items-table th:last-child { text-align: right; }
    .items-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #374151; font-size: 13px; vertical-align: top; }
    .items-table td:last-child { text-align: right; font-weight: 600; white-space: nowrap; }
    .items-table tbody tr:last-child td { border-bottom: none; }

    /* Totals */
    .totals { margin-left: auto; width: 260px; margin-bottom: 28px; }
    .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #64748b; border-bottom: 1px dashed #e2e8f0; }
    .totals-row:last-child { border-bottom: none; padding-top: 10px; }
    .totals-row.total { font-size: 16px; font-weight: 900; color: #0f172a; }
    .totals-row.paid { color: #16a34a; font-weight: 700; }
    .totals-row.balance { color: #dc2626; font-weight: 700; }

    /* Payment details */
    .payment-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px; margin-bottom: 20px; }
    .payment-box .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #92400e; margin-bottom: 6px; }
    .payment-box p { font-size: 12px; color: #713f12; white-space: pre-wrap; }

    /* Notes */
    .notes-box { border-left: 3px solid #4169e1; padding-left: 12px; margin-bottom: 20px; }
    .notes-box .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #4169e1; margin-bottom: 4px; }
    .notes-box p { font-size: 12px; color: #64748b; }

    /* Footer */
    .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 10px; color: #94a3b8; }
  </style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div>
      <div class="brand-logo">
        @if($tenant->logo)
          <img src="{{ $tenant->logo }}" style="width:100%;height:100%;object-fit:contain;border-radius:10px;" />
        @else
          {{ strtoupper(substr($tenant->app_name ?? 'X', 0, 1)) }}
        @endif
      </div>
      <div class="brand-name">{{ $tenant->app_name ?? 'Xpendee' }}</div>
      <div class="brand-tagline">{{ $tenant->company_name ?? '' }}</div>
    </div>
    <div class="invoice-label">
      <h1>INVOICE</h1>
      <div class="inv-number">#{{ $invoice->number }}</div>
      <div>
        @php
          $statusClass = 'status-' . ($invoice->status ?? 'draft');
        @endphp
        <span class="status-badge {{ $statusClass }}">{{ strtoupper($invoice->status ?? 'draft') }}</span>
      </div>
    </div>
  </div>

  {{-- Meta --}}
  <div class="meta-row">
    <div class="meta-box">
      <div class="meta-label">From</div>
      <div class="meta-value">
        <strong>{{ $tenant->company_name ?? $tenant->name }}</strong><br>
        {{ $tenant->email }}<br>
        @if($tenant->address){{ $tenant->address }}<br>@endif
      </div>
    </div>

    <div class="meta-box">
      <div class="meta-label">Bill To</div>
      <div class="meta-value">
        @if($invoice->client)
          <strong>{{ $invoice->client->name }}</strong><br>
          {{ $invoice->client->email }}<br>
          @if($invoice->client->phone){{ $invoice->client->phone }}<br>@endif
          @if($invoice->client->address){{ $invoice->client->address }}@endif
        @else
          <span style="color:#94a3b8;">No client attached</span>
        @endif
      </div>
    </div>

    <div class="meta-box" style="text-align:right;">
      <div class="meta-label">Issue Date</div>
      <div class="meta-value">{{ $invoice->issue_date?->format('d M Y') ?? '—' }}</div>
      <br>
      <div class="meta-label">Due Date</div>
      <div class="meta-value" style="color:{{ $invoice->status === 'overdue' ? '#dc2626' : '#1e293b' }}">
        {{ $invoice->due_date?->format('d M Y') ?? 'On Receipt' }}
      </div>
    </div>
  </div>

  {{-- Items --}}
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:60%;">Description</th>
        <th style="text-align:right;">Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items ?? [] as $item)
      <tr>
        <td>{{ $item['description'] ?? '' }}</td>
        <td>PKR {{ number_format((float)($item['unitPrice'] ?? 0), 0) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Totals --}}
  <div class="totals">
    <div class="totals-row">
      <span>Subtotal</span>
      <span>PKR {{ number_format($invoice->subtotal, 0) }}</span>
    </div>
    @if($invoice->tax > 0)
    <div class="totals-row">
      <span>Tax</span>
      <span>PKR {{ number_format($invoice->tax, 0) }}</span>
    </div>
    @endif
    <div class="totals-row total">
      <span>Total</span>
      <span>PKR {{ number_format($invoice->total, 0) }}</span>
    </div>
    @if($invoice->amount_paid > 0)
    <div class="totals-row paid">
      <span>Amount Paid</span>
      <span>PKR {{ number_format($invoice->amount_paid, 0) }}</span>
    </div>
    <div class="totals-row balance">
      <span>Balance Due</span>
      <span>PKR {{ number_format(max(0, $invoice->total - $invoice->amount_paid), 0) }}</span>
    </div>
    @endif
  </div>

  {{-- Payment details --}}
  @if($tenant->smtp_from_email || false)
  {{-- payment details would come from settings --}}
  @endif

  {{-- Notes --}}
  @if($invoice->notes)
  <div class="notes-box">
    <div class="section-label">Notes</div>
    <p>{{ $invoice->notes }}</p>
  </div>
  @endif

  {{-- Footer --}}
  <div class="footer">
    Generated by {{ $tenant->app_name ?? 'Xpendee' }} &bull; {{ now()->format('d M Y') }}
  </div>

</div>
</body>
</html>
