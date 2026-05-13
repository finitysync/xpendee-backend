<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Sign: {{ $contract->title }}</title>
<style>body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:0;color:#374151;}
.container{max-width:600px;margin:0 auto;padding:20px;}
.header{background:#4169e1;color:white;padding:24px;border-radius:8px 8px 0 0;text-align:center;}
.header h1{margin:0;font-size:22px;} .header p{margin:6px 0 0;opacity:.85;font-size:13px;}
.body{background:white;padding:28px;border:1px solid #e5e7eb;border-top:none;}
.btn{display:inline-block;background:#4169e1;color:white;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;margin:20px 0;}
.info{background:#f1f5f9;border-radius:8px;padding:16px;margin:16px 0;font-size:13px;}
.footer{text-align:center;font-size:11px;color:#9ca3af;margin-top:20px;}
</style></head>
<body><div class="container">
  <div class="header">
    <h1>Contract Signing Request</h1>
    <p>From {{ $tenant->app_name ?? 'Xpendee' }}</p>
  </div>
  <div class="body">
    @if($message)<p style="margin-bottom:16px;line-height:1.6;">{{ $message }}</p>@endif
    <p style="line-height:1.6;margin-bottom:16px;">You have been requested to review and sign the following contract:</p>
    <div class="info">
      <strong>{{ $contract->title }}</strong><br>
      <span style="color:#6b7280;">From {{ $tenant->app_name ?? $tenant->company_name }}</span>
    </div>
    <p style="text-align:center;">
      <a href="{{ $signingUrl }}" class="btn">Review &amp; Sign Contract →</a>
    </p>
    <p style="font-size:12px;color:#6b7280;margin-top:16px;">
      Or copy this link:<br>
      <a href="{{ $signingUrl }}" style="color:#4169e1;word-break:break-all;">{{ $signingUrl }}</a>
    </p>
    <p style="font-size:11px;color:#9ca3af;margin-top:16px;">
      If you did not expect this, please ignore this email.
    </p>
    <div class="footer">&copy; {{ date('Y') }} {{ $tenant->app_name ?? 'Xpendee' }}</div>
  </div>
</div></body></html>
