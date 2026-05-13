<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Contract Signed</title>
<style>body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;color:#374151;}
.container{max-width:600px;margin:0 auto;padding:20px;}
.header{background:#16a34a;color:white;padding:24px;border-radius:8px 8px 0 0;text-align:center;}
.header h1{margin:0;font-size:22px;} .header p{margin:6px 0 0;opacity:.85;font-size:13px;}
.body{background:white;padding:28px;border:1px solid #e5e7eb;border-top:none;}
.info{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:16px 0;font-size:13px;}
.footer{text-align:center;font-size:11px;color:#9ca3af;margin-top:20px;}
</style></head>
<body><div class="container">
  <div class="header">
    <h1>✅ Contract Signed</h1>
    <p>{{ $contract->title }}</p>
  </div>
  <div class="body">
    @if($recipient === 'agency')
      <p style="line-height:1.6;">Great news! <strong>{{ $contract->signer_name }}</strong> has signed the contract. The signed PDF is attached for your records.</p>
    @else
      <p style="line-height:1.6;">Thank you, <strong>{{ $contract->signer_name }}</strong>! You have successfully signed the contract. A signed copy is attached for your records.</p>
    @endif
    <div class="info">
      <strong>{{ $contract->title }}</strong><br>
      Signed by: {{ $contract->signer_name }}<br>
      Email: {{ $contract->signer_email }}<br>
      Date: {{ $contract->signed_at?->format('d M Y, H:i') }}
    </div>
    <p style="font-size:12px;color:#6b7280;">The signed contract PDF is attached to this email.</p>
    <div class="footer">&copy; {{ date('Y') }} {{ $tenant->app_name ?? 'Xpendee' }}</div>
  </div>
</div></body></html>
