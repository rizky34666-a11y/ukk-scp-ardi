<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Relasi: Kelas memiliki banyak Siswa
     */
    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }

    /**
     * Relasi: Kelas milik satu Jurusan
     */
    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id_jurusan');
    }

    /**
     * Accessor untuk nama kelas lengkap (contoh: "X RPL 1", "X BD", "XII LPS")
     */
    public function getNamaKelasAttribute(): string
    {
        return trim(($this->tingkat ?? '') . ' ' . ($this->id_jurusan ?? $this->id_kelas));
    }

    /**
     * Memetakan teks nama kelas (misal "X RPL 1", "10 RPL 1", "X-RPL-1", "xiirpl2") ke id_kelas di database
     */
    public static function matchKelasId(?string $search, $daftarKelas = null): ?string
    {
        if (empty($search)) {
            return null;
        }

        if ($daftarKelas === null) {
            $daftarKelas = self::leftJoin('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->select('kelas.*', 'jurusan.nama_jurusan')
                ->get();
        }

        $rawSearch = trim($search);

        // 1. Coba pencocokan persis pada id_kelas langsung
        foreach ($daftarKelas as $k) {
            if (strcasecmp((string)$k->id_kelas, $rawSearch) === 0) {
                return $k->id_kelas;
            }
        }

        $cleanSearch = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $rawSearch)));
        // Normalisasi angka desimal ke Romawi: 10 -> X, 11 -> XI, 12 -> XII
        $normSearch = preg_replace('/^10/', 'X', $cleanSearch);
        $normSearch = preg_replace('/^11/', 'XI', $normSearch);
        $normSearch = preg_replace('/^12/', 'XII', $normSearch);

        // 2. Coba pencocokan alfanumerik bersih pada id_kelas
        foreach ($daftarKelas as $k) {
            $kClean = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k->id_kelas)));
            $kNorm = preg_replace('/^10/', 'X', $kClean);
            $kNorm = preg_replace('/^11/', 'XI', $kNorm);
            $kNorm = preg_replace('/^12/', 'XII', $kNorm);

            if ($kClean === $cleanSearch || $kNorm === $normSearch) {
                return $k->id_kelas;
            }
        }

        // 3. Coba pencocokan pada tingkat + id_jurusan (misal "X" + "RPL1" -> "XRPL1")
        foreach ($daftarKelas as $k) {
            $shortName = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', ($k->tingkat ?? '') . ($k->id_jurusan ?? ''))));
            $shortNameNorm = preg_replace('/^10/', 'X', $shortName);
            if ($shortName === $cleanSearch || $shortNameNorm === $normSearch) {
                return $k->id_kelas;
            }
        }

        // 4. Coba pencocokan pada tingkat + nama_jurusan
        foreach ($daftarKelas as $k) {
            $fullName = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', ($k->tingkat ?? '') . ($k->nama_jurusan ?? ''))));
            $fullNameNorm = preg_replace('/^10/', 'X', $fullName);
            if ($fullName === $cleanSearch || $fullNameNorm === $normSearch) {
                return $k->id_kelas;
            }
        }

        // 5. Pencocokan substring (jika nama jurusan ada di dalam teks pencarian)
        foreach ($daftarKelas as $k) {
            $jurusanClean = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $k->nama_jurusan ?? '')));
            if (!empty($jurusanClean) && (str_contains($normSearch, $jurusanClean) || str_contains($jurusanClean, $normSearch))) {
                $tingkatClean = strtoupper(trim($k->tingkat ?? ''));
                if (empty($tingkatClean) || str_starts_with($normSearch, $tingkatClean)) {
                    return $k->id_kelas;
                }
            }
        }

        return null;
    }
}
