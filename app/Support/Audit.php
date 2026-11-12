<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pencatat log aktivitas.
 *
 * Contoh:
 *   Audit::catat('tambah', 'jadwal', $id, 'Menambah jadwal X RPL 1', null, $dataBaru);
 *
 * Kegagalan mencatat (misal tabel belum dibuat) TIDAK boleh mengganggu proses utama,
 * sehingga semua error ditelan dan hanya ditulis ke storage/logs/laravel.log.
 */
class Audit
{
    /** Kolom yang tidak pernah disimpan ke log. */
    private const SENSITIF = ['password', 'password_hash', 'new_password', 'password_confirmation', 'photo_base64', '_token'];

    public static function catat(
        string $aksi,
        string $modul,
        $targetId = null,
        ?string $deskripsi = null,
        $dataLama = null,
        $dataBaru = null,
        array $pelaku = []
    ): void {
        try {
            $request = request();
            AuditLog::create([
                'id_user'      => $pelaku['id_user'] ?? session('id_user'),
                'username'     => $pelaku['username'] ?? session('username'),
                'nama_lengkap' => $pelaku['nama_lengkap'] ?? session('nama_lengkap'),
                'id_role'      => $pelaku['id_role'] ?? session('id_role'),
                'aksi'         => $aksi,
                'modul'        => $modul,
                'target_id'    => $targetId !== null ? (string) $targetId : null,
                'deskripsi'    => $deskripsi,
                'data_lama'    => self::bersihkan($dataLama),
                'data_baru'    => self::bersihkan($dataBaru),
                'ip_address'   => $request?->ip(),
                'user_agent'   => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
                'created_at'   => now(), // waktu mengikuti zona waktu aplikasi (Asia/Jakarta)
            ]);
            // Sesekali (sekitar 1 dari 200 pencatatan) hapus log yang sudah lebih dari 180 hari
            if (random_int(1, 200) === 1) {
                AuditLog::where('created_at', '<', now()->subDays(180))->delete();
            }
        } catch (Throwable $e) {
            Log::warning('Audit log gagal dicatat: ' . $e->getMessage());
        }
    }

    private static function bersihkan($data): ?array
    {
        if ($data === null) {
            return null;
        }
        if (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        } elseif (is_object($data)) {
            $data = (array) $data;
        }
        if (!is_array($data)) {
            return ['nilai' => $data];
        }
        foreach (self::SENSITIF as $k) {
            unset($data[$k]);
        }
        return $data;
    }
}
