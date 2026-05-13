<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Your OTP Code</title>
<style>body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;color:#374151;}
.container{max-width:520px;margin:0 auto;padding:20px;}
.header{background:#4169e1;color:white;padding:24px;border-radius:8px 8px 0 0;text-align:center;}
.header h1{margin:0;font-size:20px;}
.body{background:white;padding:32px;border:1px solid #e5e7eb;border-top:none;text-align:center;}
.otp-box{background:#f1f5f9;border:2px dashed #4169e1;border-radius:12px;padding:20px;margin:20px 0;}
.otp-code{font-size:42px;font-weight:900;letter-spacing:12px;color:#4169e1;font-family:monospace;}
.otp-label{font-size:12px;color:#64748b;margin-top:6px;}
.footer{text-align:center;font-size:11px;color:#9ca3af;margin-top:20px;}
</style></head>
<body><div class="container">
  <div class="header"><h1>🔐 Signing Verification Code</h1></div>
  <div class="body">
    <p style="font-size:14px;line-height:1.6;margin-bottom:16px;">
      Use this code to verify your identity and sign:<br>
      <strong>{{ $contractTitle }}</strong>
    </p>
    <div class="otp-box">
      <div class="otp-code">{{ $otp }}</div>
      <div class="otp-label">This code expires in <strong>10 minutes</strong></div>
    </div>
    <p style="font-size:12px;color:#6b7280;">If you did not request this, please ignore this email.</p>
    <div class="footer">&copy; {{ date('Y') }} {{ $tenant->app_name ?? 'Xpendee' }}</div>
  </div>
</div></body></html>
