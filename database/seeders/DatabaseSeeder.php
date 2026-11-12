<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder UKK SCP - satu akun untuk setiap role.
 *
 * Jalankan:  php artisan db:seed
 * Aman dijalankan berulang kali (pakai updateOrInsert, tidak membuat data dobel).
 *
 * Role (sesuai AuthController):
 *   2 = Guru Pengampu | 3 = Wali Kelas | 4 = Waka Kurikulum | 5 = Siswa | 7 = Guru BK
 */
class DatabaseSeeder extends Seeder
{
    /** Password awal semua akun contoh (silakan diganti setelah login). */
    private const PASSWORD = 'password123';

    public function run(): void
    {
        $this->seedRole();
        $this->seedMasterData();
        $this->seedAkun();
    }

    private function seedRole(): void
    {
        $roles = [
            2 => 'Guru Pengampu',
            3 => 'Wali Kelas',
            4 => 'Waka Kurikulum',
            5 => 'Siswa',
            7 => 'Guru BK',
        ];

        foreach ($roles as $id => $nama) {
            DB::table('role')->updateOrInsert(['id_role' => $id], ['nama_role' => $nama]);
        }
    }

    private function seedMasterData(): void
    {
        DB::table('jurusan')->updateOrInsert(
            ['id_jurusan' => 'RPL'],
            ['nama_jurusan' => 'Rekayasa Perangkat Lunak']
        );

        // id_kelas "X RPL 1" -> cocok dengan username wali_XRPL1 (Kelas::matchKelasId)
        DB::table('kelas')->updateOrInsert(
            ['id_kelas' => 'X RPL 1'],
            ['tingkat' => 'X', 'id_jurusan' => 'RPL']
        );

        DB::table('tahun_ajaran')->updateOrInsert(
            ['id_tahun_ajaran' => '2026-2027-1'],
            ['nama_semester' => 'Ganjil 2026/2027', 'is_aktif' => 1]
        );

        DB::table('mata_pelajaran')->updateOrInsert(
            ['id_mapel' => 'PWPB'],
            ['nama_mapel' => 'Pemrograman Web dan Perangkat Bergerak']
        );

        DB::table('ruangan')->updateOrInsert(
            ['id_ruangan' => 'LAB-RPL1'],
            ['nama_ruangan' => 'Lab RPL 1', 'jenis' => 'Laboratorium']
        );
    }

    private function seedAkun(): void
    {
        $akun = [
            // [id_role, username, nama_lengkap, email, no_hp]
            [4, 'waka',        'Waka Kurikulum',      'waka@example.com',  '081200000004'],
            [2, '198001012005011001', 'Budi Santoso, S.Pd.', 'guru@example.com',  '081200000002'],
            [3, 'wali_XRPL1',  'Siti Aminah, S.Kom.', 'wali@example.com',  '081200000003'],
            [7, '198505052010012002', 'Dewi Lestari, S.Pd.', 'bk@example.com',    '081200000007'],
            [5, '0012345678',  'Ardi Nugroho',        'siswa@example.com', '081200000005'],
        ];

        foreach ($akun as [$idRole, $username, $nama, $email, $noHp]) {
            DB::table('users')->updateOrInsert(
                ['username' => $username],
                [
                    'password_hash' => Hash::make(self::PASSWORD),
                    'nama_lengkap'  => $nama,
                    'email'         => $email,
                    'no_hp'         => $noHp,
                    'id_role'       => $idRole,
                    'is_aktif'      => 1,
                ]
            );

            // Sama seperti storeAkun(): Guru (2) & Wali Kelas (3) otomatis masuk tabel guru
            if (in_array($idRole, [2, 3], true)) {
                DB::table('guru')->updateOrInsert(
                    ['nip' => $username],
                    ['nama_guru' => $nama]
                );
            }

            // Sama seperti storeAkun(): Siswa (5) otomatis masuk tabel siswa (username = NISN)
            if ($idRole === 5) {
                DB::table('siswa')->updateOrInsert(
                    ['nisn' => $username],
                    ['nama_siswa' => $nama, 'id_kelas' => 'X RPL 1', 'status_utama' => 'Aktif']
                );
            }
        }
    }
}
