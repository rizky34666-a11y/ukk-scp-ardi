<?php

namespace App\Services;

use App\Models\IzinGuru;
use App\Models\JadwalPelajaran;
use App\Models\Ruangan;
use App\Models\SystemConfiguration;
use App\Models\TukarJadwal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Tukar ruangan / tukar jam pelajaran antar guru.
 *
 * Jadwal dasar (tabel jadwal_pelajaran) TIDAK diubah. Perubahan hanya berlaku pada satu tanggal
 * dan disimpan di tabel tukar_jadwal (kolom `perubahan`), lalu ditimpa di memori saat jadwal dibaca.
 */
class TukarJadwalService
{
    /** Batas pengajuan/persetujuan: 30 menit sebelum jam pelajaran dimulai. */
    public const MENIT_BATAS = 30;
    /** Pengajuan hanya untuk hari ini sampai sekian hari ke depan. */
    public const HARI_MAKS = 14;

    private const NAMA_HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public static function namaHari(string $tanggal): string
    {
        return self::NAMA_HARI[Carbon::parse($tanggal)->dayOfWeekIso];
    }

    /** Pengajuan "Menunggu" yang sudah melewati batas otomatis menjadi "Kedaluwarsa". */
    public static function kedaluwarsakan(): void
    {
        try {
            TukarJadwal::where('status', 'Menunggu')->where('batas_waktu', '<', Carbon::now())
                ->update(['status' => 'Kedaluwarsa', 'catatan' => 'Tidak disetujui sebelum batas waktu.', 'updated_at' => Carbon::now()]);
        } catch (\Throwable $e) {
            // tabel belum dibuat: abaikan
        }
    }

    /** Batas waktu (Carbon) = jam mulai terawal dikurangi 30 menit. */
    public static function batasWaktu(string $tanggal, string ...$jamMulai): Carbon
    {
        $awal = collect($jamMulai)->filter()->sort()->first();
        return Carbon::parse($tanggal . ' ' . $awal)->subMinutes(self::MENIT_BATAS);
    }

    /** Terapkan perubahan yang SUDAH DISETUJUI pada kumpulan jadwal untuk satu tanggal (di memori). */
    public static function terapkan(Collection $jadwals, ?string $tanggal = null): Collection
    {
        $tanggal = $tanggal ?? Carbon::now()->toDateString();
        $hari = self::namaHari($tanggal);
        $target = $jadwals->filter(fn ($j) => $j->hari === $hari);
        if ($target->isEmpty()) {
            return $jadwals;
        }

        try {
            $ubah = TukarJadwal::where('status', 'Disetujui')->whereDate('tanggal', $tanggal)->get();
        } catch (\Throwable $e) {
            return $jadwals; // tabel belum dibuat
        }
        if ($ubah->isEmpty()) {
            return $jadwals;
        }

        $peta = [];
        foreach ($ubah as $u) {
            foreach (($u->perubahan ?? []) as $idJadwal => $p) {
                $peta[(int) $idJadwal] = $p;
            }
        }

        $idRuang = collect($peta)->pluck('ruangan')->filter()->unique()->values();
        $ruangMap = $idRuang->isNotEmpty() ? Ruangan::whereIn('id_ruangan', $idRuang)->get()->keyBy('id_ruangan') : collect();

        foreach ($target as $j) {
            $p = $peta[(int) $j->id_jadwal] ?? null;
            if (!$p) {
                continue;
            }
            $penanda = [];
            if (!empty($p['jam_mulai'])) {
                $j->jam_mulai_asli = $j->jam_mulai;
                $j->jam_selesai_asli = $j->jam_selesai;
                $j->jam_mulai = $p['jam_mulai'];
                $j->jam_selesai = $p['jam_selesai'];
                $penanda[] = 'jam';
            }
            if (!empty($p['ruangan'])) {
                $j->ruangan_asli = $j->ruangan?->nama_ruangan;
                $j->id_ruangan_default = $p['ruangan'];
                $j->setRelation('ruangan', $ruangMap->get($p['ruangan']));
                $penanda[] = 'ruangan';
            }
            $j->ditukar = $penanda;
        }
        return $jadwals;
    }

    /** Seluruh jadwal (putaran aktif) pada tanggal tertentu, lengkap dengan perubahan yang sudah disetujui. */
    public static function jadwalEfektif(string $tanggal): Collection
    {
        $siklus = SystemConfiguration::first()?->active_cycle;
        $q = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru', 'ruangan'])->where('hari', self::namaHari($tanggal));
        if ($siklus) {
            $siklus = str_starts_with($siklus, 'Putaran') ? $siklus : 'Putaran ' . $siklus;
            $q->where(fn ($w) => $w->where('cycle_type', $siklus)->orWhereNull('cycle_type'));
        }
        return self::terapkan($q->get(), $tanggal);
    }

    public static function tumpang(?string $mulai1, ?string $selesai1, ?string $mulai2, ?string $selesai2): bool
    {
        if (!$mulai1 || !$selesai1 || !$mulai2 || !$selesai2) {
            return false;
        }
        return $mulai1 < $selesai2 && $mulai2 < $selesai1;
    }

    public static function durasi(JadwalPelajaran $j): int
    {
        return Carbon::parse($j->jam_selesai)->diffInMinutes(Carbon::parse($j->jam_mulai));
    }

    /** id jadwal yang sudah terlibat pengajuan aktif (Menunggu/Disetujui) pada tanggal tersebut. */
    public static function jadwalTerpakai(string $tanggal, ?int $kecualiId = null): array
    {
        $rows = TukarJadwal::whereIn('status', ['Menunggu', 'Disetujui'])->whereDate('tanggal', $tanggal)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->get(['id_jadwal_pemohon', 'id_jadwal_tujuan']);
        return $rows->pluck('id_jadwal_pemohon')->merge($rows->pluck('id_jadwal_tujuan'))->filter()->map(fn ($v) => (int) $v)->all();
    }

    public static function guruIzin(string $tanggal, int $idGuru): bool
    {
        return IzinGuru::disetujuiPadaTanggal($tanggal, [$idGuru])->isNotEmpty();
    }

    /** Pemakai ruangan pada jam jadwal tertentu (selain jadwal itu sendiri). */
    public static function pemakaiRuangan(Collection $efektif, string $idRuangan, JadwalPelajaran $j, array $kecuali = []): Collection
    {
        $kecuali[] = (int) $j->id_jadwal;
        return $efektif->filter(fn ($x) => (string) $x->id_ruangan_default === $idRuangan
            && !in_array((int) $x->id_jadwal, $kecuali, true)
            && self::tumpang($x->jam_mulai, $x->jam_selesai, $j->jam_mulai, $j->jam_selesai))->values();
    }

    /**
     * Periksa bentrok untuk tukar jam. Mengembalikan teks kesalahan atau null bila aman.
     * $efektif = jadwalEfektif(tanggal).
     */
    public static function cekBentrokJam(Collection $efektif, JadwalPelajaran $a, JadwalPelajaran $b): ?string
    {
        // a pindah ke slot b, b pindah ke slot a
        $slotA = [$a->jam_mulai, $a->jam_selesai];
        $slotB = [$b->jam_mulai, $b->jam_selesai];
        $lain = $efektif->filter(fn ($x) => !in_array((int) $x->id_jadwal, [(int) $a->id_jadwal, (int) $b->id_jadwal], true));

        $guruA = $lain->first(fn ($x) => (int) $x->id_guru === (int) $a->id_guru && self::tumpang($x->jam_mulai, $x->jam_selesai, $slotB[0], $slotB[1]));
        if ($guruA) {
            return 'Bentrok: Anda sudah mengajar di ' . ($guruA->kelas?->nama_kelas ?? $guruA->id_kelas) . ' pada jam tersebut.';
        }
        $guruB = $lain->first(fn ($x) => (int) $x->id_guru === (int) $b->id_guru && self::tumpang($x->jam_mulai, $x->jam_selesai, $slotA[0], $slotA[1]));
        if ($guruB) {
            return 'Bentrok: guru yang diajak bertukar sudah mengajar di kelas lain pada jam Anda.';
        }
        $ruangA = $lain->first(fn ($x) => (string) $x->id_ruangan_default === (string) $a->id_ruangan_default && self::tumpang($x->jam_mulai, $x->jam_selesai, $slotB[0], $slotB[1]));
        if ($ruangA) {
            return 'Bentrok ruangan: ruang ' . ($a->ruangan?->nama_ruangan ?? $a->id_ruangan_default) . ' dipakai kelas lain pada jam tujuan. Tukar ruangan terlebih dahulu.';
        }
        $ruangB = $lain->first(fn ($x) => (string) $x->id_ruangan_default === (string) $b->id_ruangan_default && self::tumpang($x->jam_mulai, $x->jam_selesai, $slotA[0], $slotA[1]));
        if ($ruangB) {
            return 'Bentrok ruangan: ruang ' . ($b->ruangan?->nama_ruangan ?? $b->id_ruangan_default) . ' dipakai kelas lain pada jam Anda. Tukar ruangan terlebih dahulu.';
        }
        return null;
    }

    /** Perubahan (untuk kolom `perubahan`) pada tukar jam. */
    public static function perubahanJam(JadwalPelajaran $a, JadwalPelajaran $b): array
    {
        return [
            (int) $a->id_jadwal => ['jam_mulai' => $b->jam_mulai, 'jam_selesai' => $b->jam_selesai],
            (int) $b->id_jadwal => ['jam_mulai' => $a->jam_mulai, 'jam_selesai' => $a->jam_selesai],
        ];
    }

    /** Perubahan pada tukar ruangan antar dua jadwal (a mendapat ruang b, b mendapat ruang a). */
    public static function perubahanRuangTukar(JadwalPelajaran $a, JadwalPelajaran $b): array
    {
        return [
            (int) $a->id_jadwal => ['ruangan' => (string) $b->id_ruangan_default],
            (int) $b->id_jadwal => ['ruangan' => (string) $a->id_ruangan_default],
        ];
    }
}
