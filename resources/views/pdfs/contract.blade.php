<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contract — {{ $contract->title }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }
    .page { width: 100%; max-width: 800px; margin: 0 auto; padding: 48px; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 32px; }
    .brand { font-size: 20px; font-weight: 900; color: #4169e1; }
    .brand-sub { font-size: 11px; color: #94a3b8; margin-top: 3px; }
    .doc-label h1 { font-size: 26px; font-weight: 900; color: #0f172a; text-align: right; letter-spacing: -0.5px; }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 6px; float: right; }
    .status-signed    { background: #dcfce7; color: #166534; }
    .status-draft     { background: #f1f5f9; color: #475569; }
    .status-sent      { background: #dbeafe; color: #1e40af; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }

    /* Parties */
    .parties { display: flex; justify-content: space-between; gap: 32px; margin-bottom: 32px; }
    .party { flex: 1; }
    .party-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 6px; }
    .party-name { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
    .party-sub { font-size: 12px; color: #64748b; }

    /* Body */
    .contract-title { font-size: 20px; font-weight: 900; color: #0f172a; margin-bottom: 8px; }
    .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #4169e1; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
    .contract-body { font-size: 12px; line-height: 1.8; color: #374151; margin-bottom: 40px; white-space: pre-wrap; }

    /* Signature section */
    .signature-section { border-top: 2px solid #f1f5f9; padding-top: 24px; margin-top: 40px; }
    .sig-row { display: flex; justify-content: space-between; gap: 32px; }
    .sig-box { flex: 1; }
    .sig-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
    .sig-image { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; background: #fafafa; height: 80px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .sig-image img { max-height: 64px; max-width: 100%; }
    .sig-line { border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 11px; color: #374151; }
    .sig-date { font-size: 10px; color: #94a3b8; margin-top: 4px; }

    /* Footer */
    .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 10px; color: #94a3b8; }
  </style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div>
      <div class="brand">{{ $tenant->app_name ?? 'Xpendee' }}</div>
      <div class="brand-sub">{{ $tenant->company_name ?? $tenant->email }}</div>
    </div>
    <div class="doc-label">
      <h1>CONTRACT</h1>
      <div>
        @php $statusClass = 'status-' . ($contract->status ?? 'draft'); @endphp
        <span class="status-badge {{ $statusClass }}">{{ strtoupper($contract->status ?? 'draft') }}</span>
      </div>
    </div>
  </div>

  {{-- Parties --}}
  <div class="parties">
    <div class="party">
      <div class="party-label">Service Provider</div>
      <div class="party-name">{{ $tenant->company_name ?? $tenant->app_name }}</div>
      <div class="party-sub">{{ $tenant->email }}</div>
      @if($tenant->address)<div class="party-sub">{{ $tenant->address }}</div>@endif
    </div>
    <div class="party">
      <div class="party-label">Client / Signer</div>
      <div class="party-name">{{ $contract->signer_name ?? $contract->client?->name ?? '—' }}</div>
      <div class="party-sub">{{ $contract->signer_email ?? $contract->client?->email ?? '' }}</div>
    </div>
  </div>

  {{-- Title + Body --}}
  <div class="contract-title">{{ $contract->title }}</div>
  <div class="section-label" style="margin-top:16px;">Terms & Conditions</div>
  <div class="contract-body">{{ strip_tags($contract->body) }}</div>

  {{-- Signature --}}
  <div class="signature-section">
    <div class="section-label">Signatures</div>
    <div class="sig-row">
      <div class="sig-box">
        <div class="sig-label">Service Provider</div>
        <div class="sig-image">
          <span style="color:#94a3b8; font-size:11px;">{{ $tenant->app_name }}</span>
        </div>
        <div class="sig-line">{{ $tenant->company_name ?? $tenant->name }}</div>
        <div class="sig-date">Issued: {{ now()->format('d M Y') }}</div>
      </div>
      <div class="sig-box">
        <div class="sig-label">Client Signature</div>
        <div class="sig-image">
          @if($contract->signature_data)
            <img src="{{ $contract->signature_data }}" alt="Signature" />
          @else
            <span style="color:#94a3b8; font-size:11px;">Not yet signed</span>
          @endif
        </div>
        <div class="sig-line">{{ $contract->signer_name ?? '—' }}</div>
        <div class="sig-date">
          @if($contract->signed_at)
            Signed: {{ $contract->signed_at->format('d M Y, H:i') }}
          @else
            Awaiting signature
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    Generated by {{ $tenant->app_name ?? 'Xpendee' }} &bull; {{ now()->format('d M Y') }}
  </div>

</div>
</body>
</html>
