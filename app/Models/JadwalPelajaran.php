<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pelajaran';
    protected $primaryKey = 'id_jadwal'; // <-- Paling penting agar Laravel membaca primary key yang benar
    public $timestamps = false; 

    protected $guarded = [];

    // Relasi-relasi ke tabel terkait
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'id_mapel', 'id_mapel');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_default', 'id_ruangan');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'schedule_id', 'id_jadwal');
    }

    /**
     * Master data sesi jam pelajaran sesuai struktur KBM sekolah
     */
    /** Jam pelajaran per hari (sumber: JADWAL RUANG Putaran 1 & 2, tahun ajaran 2026/2027). */
    private const SESI_HARI = [
        'Senin' => [
            1 => ['nama' => 'Jam 1', 'mulai' => '08:00', 'selesai' => '08:35'],
            2 => ['nama' => 'Jam 2', 'mulai' => '08:35', 'selesai' => '09:10'],
            3 => ['nama' => 'Jam 3', 'mulai' => '09:10', 'selesai' => '09:45'],
            4 => ['nama' => 'Jam 4', 'mulai' => '10:00', 'selesai' => '10:35'],
            5 => ['nama' => 'Jam 5', 'mulai' => '10:35', 'selesai' => '11:10'],
            6 => ['nama' => 'Jam 6', 'mulai' => '11:10', 'selesai' => '11:45'],
            7 => ['nama' => 'Jam 7', 'mulai' => '12:45', 'selesai' => '13:25'],
            8 => ['nama' => 'Jam 8', 'mulai' => '13:25', 'selesai' => '14:00'],
            9 => ['nama' => 'Jam 9', 'mulai' => '14:00', 'selesai' => '14:35'],
            10 => ['nama' => 'Jam 10', 'mulai' => '14:35', 'selesai' => '15:10'],
        ],
        'Selasa' => [
            1 => ['nama' => 'Jam 1', 'mulai' => '07:30', 'selesai' => '08:10'],
            2 => ['nama' => 'Jam 2', 'mulai' => '08:10', 'selesai' => '08:50'],
            3 => ['nama' => 'Jam 3', 'mulai' => '08:50', 'selesai' => '09:30'],
            4 => ['nama' => 'Jam 4', 'mulai' => '09:45', 'selesai' => '10:25'],
            5 => ['nama' => 'Jam 5', 'mulai' => '10:25', 'selesai' => '11:05'],
            6 => ['nama' => 'Jam 6', 'mulai' => '11:05', 'selesai' => '11:45'],
            7 => ['nama' => 'Jam 7', 'mulai' => '12:45', 'selesai' => '13:25'],
            8 => ['nama' => 'Jam 8', 'mulai' => '13:25', 'selesai' => '14:00'],
            9 => ['nama' => 'Jam 9', 'mulai' => '14:00', 'selesai' => '14:35'],
            10 => ['nama' => 'Jam 10', 'mulai' => '14:35', 'selesai' => '15:10'],
        ],
        'Rabu' => [
            1 => ['nama' => 'Jam 1', 'mulai' => '07:15', 'selesai' => '07:55'],
            2 => ['nama' => 'Jam 2', 'mulai' => '07:55', 'selesai' => '08:35'],
            3 => ['nama' => 'Jam 3', 'mulai' => '08:35', 'selesai' => '09:15'],
            4 => ['nama' => 'Jam 4', 'mulai' => '09:30', 'selesai' => '10:10'],
            5 => ['nama' => 'Jam 5', 'mulai' => '10:10', 'selesai' => '10:50'],
            6 => ['nama' => 'Jam 6', 'mulai' => '10:50', 'selesai' => '11:30'],
            7 => ['nama' => 'Jam 7', 'mulai' => '12:30', 'selesai' => '13:10'],
            8 => ['nama' => 'Jam 8', 'mulai' => '13:10', 'selesai' => '13:50'],
            9 => ['nama' => 'Jam 9', 'mulai' => '13:50', 'selesai' => '14:30'],
            10 => ['nama' => 'Jam 10', 'mulai' => '14:30', 'selesai' => '15:10'],
        ],
        'Kamis' => [
            1 => ['nama' => 'Jam 1', 'mulai' => '07:15', 'selesai' => '07:55'],
            2 => ['nama' => 'Jam 2', 'mulai' => '07:55', 'selesai' => '08:35'],
            3 => ['nama' => 'Jam 3', 'mulai' => '08:35', 'selesai' => '09:15'],
            4 => ['nama' => 'Jam 4', 'mulai' => '09:30', 'selesai' => '10:10'],
            5 => ['nama' => 'Jam 5', 'mulai' => '10:10', 'selesai' => '10:50'],
            6 => ['nama' => 'Jam 6', 'mulai' => '10:50', 'selesai' => '11:30'],
            7 => ['nama' => 'Jam 7', 'mulai' => '12:30', 'selesai' => '13:10'],
            8 => ['nama' => 'Jam 8', 'mulai' => '13:10', 'selesai' => '13:50'],
            9 => ['nama' => 'Jam 9', 'mulai' => '13:50', 'selesai' => '14:30'],
            10 => ['nama' => 'Jam 10', 'mulai' => '14:30', 'selesai' => '15:10'],
        ],
        'Jumat' => [
            1 => ['nama' => 'Jam 1', 'mulai' => '07:15', 'selesai' => '07:55'],
            2 => ['nama' => 'Jam 2', 'mulai' => '07:55', 'selesai' => '08:35'],
            3 => ['nama' => 'Jam 3', 'mulai' => '08:35', 'selesai' => '09:15'],
            4 => ['nama' => 'Jam 4', 'mulai' => '09:30', 'selesai' => '10:10'],
            5 => ['nama' => 'Jam 5', 'mulai' => '10:10', 'selesai' => '10:50'],
            6 => ['nama' => 'Jam 6', 'mulai' => '10:50', 'selesai' => '11:25'],
            7 => ['nama' => 'Jam 7', 'mulai' => '12:40', 'selesai' => '13:15'],
            8 => ['nama' => 'Jam 8', 'mulai' => '13:15', 'selesai' => '13:50'],
        ],
    ];

    /** Kegiatan non-KBM sebelum jam ke-1 (bukan jam pelajaran). */
    private const KEGIATAN_HARI = [
        'Senin' => ['nama' => 'Upacara Bendera', 'mulai' => '07:00', 'selesai' => '08:00'],
        'Selasa' => ['nama' => 'Literasi', 'mulai' => '07:00', 'selesai' => '07:30'],
    ];

    /**
     * Daftar jam pelajaran. Tanpa argumen = jam hari biasa (Rabu/Kamis).
     * Jam berbeda untuk Senin (upacara), Selasa (literasi), dan Jumat (8 jam).
     */
    public static function getDaftarSesi(?string $hari = null): array
    {
        $sesi = self::SESI_HARI[$hari] ?? self::SESI_HARI['Rabu'];
        foreach ($sesi as $i => $s) {
            $sesi[$i]['kbm_menit'] = (int) ((strtotime($s['selesai']) - strtotime($s['mulai'])) / 60);
        }
        return $sesi;
    }

    /** Semua hari sekaligus (untuk form & tabel struktur jam). */
    public static function getSesiPerHari(): array
    {
        $semua = [];
        foreach (array_keys(self::SESI_HARI) as $h) {
            $semua[$h] = self::getDaftarSesi($h);
        }
        return $semua;
    }

    public static function getKegiatanHari(): array
    {
        return self::KEGIATAN_HARI;
    }

    /**
     * Accessor untuk mendapatkan label teks sesi (misal: "Jam ke-1 - 3" atau "Jam ke-4")
     */
    /**
     * Label jam ke- berdasarkan SEMUA sesi 40 menit yang tercakup oleh rentang waktu jadwal
     * (sesi dihitung bila terpakai minimal 20 menit). Contoh: 07:15-10:30 => "Jam ke-1 - 5".
     */
    public function getLabelSesiAttribute(): ?string
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return null;
        }

        $menit = fn (string $t) => ((int) substr($t, 0, 2)) * 60 + (int) substr($t, 3, 2);
        $mulai = $menit($this->jam_mulai);
        $selesai = $menit($this->jam_selesai);

        $terpakai = [];
        foreach (self::getDaftarSesi($this->hari) as $idx => $s) {
            $irisan = min($selesai, $menit($s['selesai'])) - max($mulai, $menit($s['mulai']));
            if ($irisan >= 20) {
                $terpakai[] = $idx;
            }
        }

        if (empty($terpakai)) {
            return null;
        }

        $awal = min($terpakai);
        $akhir = max($terpakai);

        return $awal === $akhir ? "Jam ke-$awal" : "Jam ke-$awal - $akhir";
    }
}