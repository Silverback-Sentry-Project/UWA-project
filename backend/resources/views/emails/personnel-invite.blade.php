<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #2b2b1f; background:#f6f5ef; padding:24px;">
  <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:10px; padding:28px; border:1px solid #e4e2d6;">
    <h2 style="color:#4b5d2a; margin-top:0;">Welcome to WildWatch</h2>
    <p>Hi {{ $recipientName }},</p>
    <p>You've been invited to join the <strong>WildWatch UWA Administrative Portal</strong> as a <strong>{{ $roleName }}</strong>{{ $parkName ? ", working at {$parkName}" : '' }}.</p>

    <div style="background:#f6f5ef; border:1px solid #e4e2d6; border-radius:8px; padding:16px; margin:20px 0;">
      <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.06em; color:#8a8770;">Login email</div>
      <div style="font-weight:bold; margin-bottom:10px;">{{ $recipientEmail }}</div>
      <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.06em; color:#8a8770;">Temporary password</div>
      <div style="font-weight:bold; font-family: monospace;">{{ $temporaryPassword }}</div>
      @if($parkName)
      <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.06em; color:#8a8770; margin-top:10px;">Park</div>
      <div style="font-weight:bold;">{{ $parkName }}</div>
      @endif
    </div>

    <p>Please sign in and change your password as soon as possible.</p>
    <p><a href="{{ $portalUrl }}" style="display:inline-block; background:#4b5d2a; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none;">Sign in to the portal</a></p>

    @if($hasMobileAccount)
    <div style="background:#eef2e6; border:1px solid #cfd9be; border-radius:8px; padding:16px; margin:20px 0;">
      <div style="font-weight:bold; margin-bottom:6px;">You can also sign in to the WildWatch mobile app</div>
      <p style="margin:0;">Open the app, enter <strong>{{ $recipientEmail }}</strong> under "Continue with email", and tap the sign-in link we send you — no password needed there.</p>
    </div>
    @endif

    <p style="font-size:12px; color:#8a8770; margin-top:28px;">If you weren't expecting this invitation, you can ignore this email.</p>
  </div>
</body>
</html>
