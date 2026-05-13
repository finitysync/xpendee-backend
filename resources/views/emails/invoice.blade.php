<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Invoice #{{ $invoice->number }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 14px; color: #374151; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: #4169e1; color: white; padding: 24px; border-radius: 8px 8px 0 0; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; }
    .header p { margin: 6px 0 0; opacity: 0.85; font-size: 13px; }
    .body { background: #f9fafb; padding: 24px; border: 1px solid #e5e7eb; border-top: none; }
    .invoice-box { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e5e7eb; font-size: 13px; }
    .row:last-child { border-bottom: none; font-weight: 700; font-size: 15px; color: #0f172a; }
    .btn { display: inline-block; background: #4169e1; color: white; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; margin: 16px 0; }
    .footer { text-align: center; font-size: 11px; color: #9ca3af; margin-top: 20px; }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Invoice #{{ $invoice->number }}</h1>
    <p>From {{ $tenant->app_name ?? $tenant->company_name ?? 'Xpendee' }}</p>
  </div>
  <div class="body">
    @if($body)
      <p style="margin-bottom:20px; line-height:1.6;">{{ $body }}</p>
    @else
      <p style="margin-bottom:20px; line-height:1.6;">
        Please find your invoice attached to this email. The PDF contains a full breakdown of the services rendered.
      </p>
    @endif

    <div class="invoice-box">
      <div class="row"><span>Invoice #</span><span><strong>{{ $invoice->number }}</strong></span></div>
      <div class="row"><span>Issue Date</span><span>{{ $invoice->issue_date?->format('d M Y') }}</span></div>
      @if($invoice->due_date)
      <div class="row"><span>Due Date</span><span>{{ $invoice->due_date->format('d M Y') }}</span></div>
      @endif
      <div class="row"><span>Status</span><span>{{ strtoupper($invoice->status) }}</span></div>
      <div class="row"><span>Total Amount</span><span>PKR {{ number_format($invoice->total, 0) }}</span></div>
      @if($invoice->amount_paid > 0)
      <div class="row"><span>Amount Paid</span><span>PKR {{ number_format($invoice->amount_paid, 0) }}</span></div>
      <div class="row"><span>Balance Due</span><span>PKR {{ number_format(max(0, $invoice->total - $invoice->amount_paid), 0) }}</span></div>
      @endif
    </div>

    <p style="font-size:12px; color:#6b7280;">The full invoice PDF is attached to this email for your records.</p>

    <div class="footer">
      &copy; {{ date('Y') }} {{ $tenant->app_name ?? 'Xpendee' }}<br>
      {{ $tenant->email }}
    </div>
  </div>
</div>
</body>
</html>
