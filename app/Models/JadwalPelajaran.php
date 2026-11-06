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
    public static function getDaftarSesi(): array
    {
        return [
            1  => ['nama' => 'Jam 1',  'mulai' => '07:15', 'selesai' => '07:55', 'kbm_menit' => 40],
            2  => ['nama' => 'Jam 2',  'mulai' => '07:55', 'selesai' => '08:35', 'kbm_menit' => 40],
            3  => ['nama' => 'Jam 3',  'mulai' => '08:35', 'selesai' => '09:15', 'kbm_menit' => 40],
            // Istirahat 1: 09:15 - 09:30
            4  => ['nama' => 'Jam 4',  'mulai' => '09:30', 'selesai' => '10:10', 'kbm_menit' => 40],
            5  => ['nama' => 'Jam 5',  'mulai' => '10:10', 'selesai' => '10:50', 'kbm_menit' => 40],
            6  => ['nama' => 'Jam 6',  'mulai' => '10:50', 'selesai' => '11:30', 'kbm_menit' => 40],
            // Ishoma: 11:30 - 12:30
            7  => ['nama' => 'Jam 7',  'mulai' => '12:30', 'selesai' => '13:10', 'kbm_menit' => 40],
            8  => ['nama' => 'Jam 8',  'mulai' => '13:10', 'selesai' => '13:50', 'kbm_menit' => 40],
            9  => ['nama' => 'Jam 9',  'mulai' => '13:50', 'selesai' => '14:30', 'kbm_menit' => 40],
            10 => ['nama' => 'Jam 10', 'mulai' => '14:30', 'selesai' => '15:10', 'kbm_menit' => 40],
        ];
    }

    /**
     * Accessor untuk mendapatkan label teks sesi (misal: "Jam ke-1 - 3" atau "Jam ke-4")
     */
    public function getLabelSesiAttribute(): ?string
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return null;
        }

        $mulai = substr($this->jam_mulai, 0, 5);
        $selesai = substr($this->jam_selesai, 0, 5);

        $sesiList = self::getDaftarSesi();

        $startSesi = null;
        $endSesi = null;

        foreach ($sesiList as $idx => $s) {
            if ($s['mulai'] === $mulai) {
                $startSesi = $idx;
            }
            if ($s['selesai'] === $selesai) {
                $endSesi = $idx;
            }
        }

        if ($startSesi && $endSesi) {
            return ($startSesi === $endSesi) ? "Jam ke-$startSesi" : "Jam ke-$startSesi - $endSesi";
        } elseif ($startSesi) {
            return "Jam ke-$startSesi";
        }

        return null;
    }
}