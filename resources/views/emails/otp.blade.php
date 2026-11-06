<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Kode OTP</title></head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e2e8f0;">
        <h2 style="margin:0 0 8px;font-size:18px;color:#0369a1;">UKK SCP</h2>
        <p style="margin:0 0 16px;font-size:14px;">Halo <strong>{{ $username }}</strong>,</p>
        <p style="margin:0 0 16px;font-size:14px;">Gunakan kode OTP berikut untuk verifikasi <strong>{{ $purpose }}</strong>:</p>
        <div style="text-align:center;margin:24px 0;">
            <span style="display:inline-block;padding:12px 24px;font-size:30px;font-weight:bold;letter-spacing:8px;background:#f0f9ff;border:1px dashed #0369a1;border-radius:8px;font-family:monospace;">{{ $otp }}</span>
        </div>
        <p style="margin:0 0 8px;font-size:13px;color:#475569;">Kode berlaku selama {{ $expiryMinutes }} menit.</p>
        <p style="margin:0;font-size:13px;color:#b91c1c;">Jangan berikan kode ini kepada siapa pun. Abaikan email ini jika Anda tidak meminta kode OTP.</p>
    </div>
</body>
</html>
