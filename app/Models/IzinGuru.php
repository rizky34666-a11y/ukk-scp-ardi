<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class IzinGuru extends Model
{
    protected $table = 'izin_guru';

    protected $guarded = [];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'diproses_pada' => 'datetime',
    ];

    /** Pilihan alasan baku. "Lainnya" wajib dijelaskan sendiri di kolom keterangan. */
    public const JENIS = [
        'Sakit',
        'Dinas Luar',
        'Pelatihan / Diklat',
        'Keperluan Keluarga',
        'Keperluan Mendesak',
        'Lainnya',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    /** Izin yang sudah DISETUJUI dan mencakup tanggal tertentu, dikunci per id_guru. */
    public static function disetujuiPadaTanggal(string $tanggal, ?array $idGuru = null): Collection
    {
        $q = static::where('status', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal);
        if ($idGuru !== null) {
            $q->whereIn('id_guru', $idGuru);
        }
        return $q->get()->keyBy('id_guru');
    }

    /**
     * Jadwal mengajar guru yang jatuh pada rentang izin (sesuai putaran aktif).
     * Hasil: Collection berisi ['tanggal' => Carbon, 'jadwal' => JadwalPelajaran].
     */
    public function jadwalTerdampak(): Collection
    {
        $siklus = SystemConfiguration::first()?->active_cycle;
        $jadwal = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where('id_guru', $this->id_guru)
            ->when($siklus, fn ($q) => $q->where(fn ($w) => $w->where('cycle_type', $siklus)->orWhereNull('cycle_type')))
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy('hari');

        $hasil = collect();
        $nama = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $tgl = Carbon::parse($this->tanggal_mulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggal_selesai)->startOfDay();
        $batas = 0;
        while ($tgl->lte($akhir) && $batas++ < 62) {
            $hari = $nama[$tgl->dayOfWeekIso] ?? null;
            if ($hari && isset($jadwal[$hari])) {
                foreach ($jadwal[$hari] as $j) {
                    $hasil->push(['tanggal' => $tgl->copy(), 'jadwal' => $j]);
                }
            }
            $tgl->addDay();
        }
        return $hasil;
    }
}
