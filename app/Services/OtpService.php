<?php

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /** Masa berlaku kode OTP dalam detik (5 menit) */
    const OTP_EXPIRY_SECONDS = 300;

    /** Jeda minimal antar pengiriman OTP ke akun yang sama (detik) */
    const RESEND_COOLDOWN_SECONDS = 60;

    /** Batas salah input OTP sebelum kode dihanguskan */
    const MAX_ATTEMPTS = 5;

    /**
     * Sensor email untuk tampilan UI (contoh: ar***@gmail.com)
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return '-';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, max(1, strlen($local) - 1)));

        return $visible . str_repeat('*', max(3, strlen($local) - strlen($visible))) . '@' . $domain;
    }

    /**
     * Buat dan simpan kode OTP baru untuk identifier (username)
     */
    public static function generateOtp(string $identifier): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $key = md5($identifier);

        Cache::put('otp_' . $key, [
            'code'       => $otp,
            'identifier' => $identifier,
        ], self::OTP_EXPIRY_SECONDS);
        Cache::forget('otp_attempts_' . $key);

        return $otp;
    }

    /**
     * Kirim kode OTP ke email pengguna
     */
    public static function sendOtp(string $email, string $username, string $purpose = 'Ubah Password'): array
    {
        $maskedEmail = self::maskEmail($email);
        $key = md5($username);

        // Cegah spam kirim OTP
        if (!Cache::add('otp_cooldown_' . $key, 1, self::RESEND_COOLDOWN_SECONDS)) {
            return [
                'success'         => false,
                'masked_email'    => $maskedEmail,
                'simulation_code' => null,
                'message'         => 'Mohon tunggu ' . self::RESEND_COOLDOWN_SECONDS . ' detik sebelum meminta kode OTP lagi.',
            ];
        }

        $otp = self::generateOtp($username);

        try {
            Mail::to($email)->send(new OtpMail($username, $otp, $purpose, self::OTP_EXPIRY_SECONDS / 60));
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim OTP email [{$purpose}] untuk {$username}: " . $e->getMessage());
            Cache::forget('otp_' . $key);
            Cache::forget('otp_cooldown_' . $key);

            return [
                'success'         => false,
                'masked_email'    => $maskedEmail,
                'simulation_code' => null,
                'message'         => 'Gagal mengirim email OTP. Periksa pengaturan email server atau coba lagi nanti.',
            ];
        }

        // Kode hanya ditampilkan di layar saat mode lokal (MAIL_MAILER=log + APP_DEBUG=true)
        $showCode = config('app.debug') && config('mail.default') === 'log';

        return [
            'success'         => true,
            'masked_email'    => $maskedEmail,
            'simulation_code' => $showCode ? $otp : null,
            'message'         => "Kode OTP 6 digit telah dikirim ke email {$maskedEmail}. Periksa kotak masuk atau folder spam.",
        ];
    }

    /**
     * Verifikasi kode OTP yang diinputkan pengguna
     */
    public static function verifyOtp(string $identifier, string $inputCode): bool
    {
        $key  = md5($identifier);
        $data = Cache::get('otp_' . $key);

        if (!$data || !isset($data['code'])) {
            return false;
        }

        $attempts = (int) Cache::get('otp_attempts_' . $key, 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget('otp_' . $key);
            return false;
        }

        if (!hash_equals((string) $data['code'], trim($inputCode))) {
            Cache::put('otp_attempts_' . $key, $attempts + 1, self::OTP_EXPIRY_SECONDS);
            return false;
        }

        // OTP sekali pakai
        Cache::forget('otp_' . $key);
        Cache::forget('otp_attempts_' . $key);

        // Status terverifikasi disimpan di session browser yang memverifikasi saja (15 menit)
        session([
            'otp_verified_' . $key => now()->addMinutes(15)->timestamp,
        ]);

        return true;
    }

    /**
     * Cek apakah identifier sudah terverifikasi OTP pada sesi ini
     */
    public static function isVerified(string $identifier): bool
    {
        $expires = session('otp_verified_' . md5($identifier));

        return is_int($expires) && $expires >= now()->timestamp;
    }

    /**
     * Bersihkan status verifikasi setelah password berhasil diubah
     */
    public static function clearVerification(string $identifier): void
    {
        session()->forget('otp_verified_' . md5($identifier));
    }
}
