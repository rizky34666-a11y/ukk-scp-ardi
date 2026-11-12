<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\IzinGuru;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Jadwal pelajaran untuk HARI INI (sesuai putaran aktif), urut jam, dengan status waktu.
 */
class JadwalHariIni
{
    private const NAMA_HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    /** Nama hari ini dalam Bahasa Indonesia (sesuai kolom `hari` di database). */
    public static function namaHari(?Carbon $sekarang = null): string
    {
        return self::NAMA_HARI[($sekarang ?? Carbon::now())->dayOfWeekIso];
    }

    /**
     * Guru yang sedang login (NIP = username, cadangan: nama lengkap).
     * Untuk Guru BK yang juga mengajar: dipilih baris guru (NIP asli / bk_ / guru_) yang memiliki jadwal.
     */
    public static function guruLogin(): ?Guru
    {
        $username = session('username');
        if (!$username) {
            return null;
        }

        if ((int) session('id_role') === 7) {
            $kandidat = Guru::where('nip', $username)->orWhere('nama_guru', session('nama_lengkap'))->get();
            if ($kandidat->count() > 1) {
                $punyaJadwal = JadwalPelajaran::whereIn('id_guru', $kandidat->pluck('id_guru'))->distinct()->pluck('id_guru')->all();
                $dipilih = $kandidat->first(fn ($g) => $g->nip === $username && in_array($g->id_guru, $punyaJadwal, true))
                    ?? $kandidat->first(fn ($g) => in_array($g->id_guru, $punyaJadwal, true));
                if ($dipilih) {
                    return $dipilih;
                }
            }
        }

        return Guru::where('nip', $username)->first()
            ?? Guru::where('nama_guru', session('nama_lengkap'))->first();
    }

    /** Cari guru berdasarkan username/NIP; untuk Guru BK memilih baris yang memiliki jadwal. */
    public static function guruByUsername(?string $username): ?Guru
    {
        if ((int) session('id_role') === 7 && $username === session('username')) {
            return self::guruLogin();
        }
        return Guru::where('nip', $username)->first();
    }

    /** Guru BK yang juga memiliki jadwal mengajar (peran ganda), ditentukan saat login. */
    public static function bkMengajar(): bool
    {
        return (int) session('id_role') === 7 && (bool) session('bk_mengajar');
    }

    /** Apakah akun ini (username / nama) memiliki jadwal mengajar? Dipakai saat login Guru BK. */
    public static function punyaJadwalMengajar(?string $username, ?string $nama): bool
    {
        $ids = Guru::where('nip', $username)->orWhere('nama_guru', $nama)->pluck('id_guru');
        return $ids->isNotEmpty() && JadwalPelajaran::whereIn('id_guru', $ids)->exists();
    }

    public static function untukGuru(?Guru $guru): Collection
    {
        if (!$guru) {
            return collect();
        }
        $jadwals = self::ambil(fn ($q) => $q->where('id_guru', $guru->id_guru), true);
        $izin = IzinGuru::disetujuiPadaTanggal(Carbon::now()->toDateString(), [$guru->id_guru])->get($guru->id_guru);
        return $jadwals->each(function ($j) use ($izin) {
            $j->izin_disetujui = (bool) $izin;
            $j->tugas_pengganti = $izin?->tugas_pengganti;
        });
    }

    public static function untukKelas(?string $idKelas): Collection
    {
        if (!$idKelas) {
            return collect();
        }
        $jadwals = self::ambil(fn ($q) => $q->where('id_kelas', $idKelas), false);
        $izin = IzinGuru::disetujuiPadaTanggal(Carbon::now()->toDateString(), $jadwals->pluck('id_guru')->unique()->values()->all());
        return $jadwals->each(function ($j) use ($izin) {
            $i = $izin->get($j->id_guru);
            $j->izin_disetujui = (bool) $i;           // siswa hanya melihat "guru berhalangan", bukan alasannya
            $j->tugas_pengganti = $i?->tugas_pengganti;
        });
    }

    private static function ambil(callable $filter, bool $cekJurnal): Collection
    {
        $sekarang = Carbon::now();
        $hari = self::namaHari($sekarang);
        if ($hari === 'Minggu') {
            return collect();
        }

        $siklus = SystemConfiguration::first()?->active_cycle;

        $query = JadwalPelajaran::with(['kelas.jurusan', 'mataPelajaran', 'guru', 'ruangan'])
            ->where('hari', $hari);
        $filter($query);

        if ($siklus) {
            $siklus = str_starts_with($siklus, 'Putaran') ? $siklus : 'Putaran ' . $siklus;
            $query->where(function ($q) use ($siklus) {
                $q->where('cycle_type', $siklus)->orWhereNull('cycle_type');
            });
        }

        $jadwals = $query->orderBy('jam_mulai')->get();

        // Tukar ruangan / tukar jam yang sudah disetujui untuk hari ini
        $jadwals = TukarJadwalService::terapkan($jadwals, $sekarang->toDateString())->sortBy('jam_mulai')->values();

        $terisi = [];
        if ($cekJurnal && $jadwals->isNotEmpty()) {
            $terisi = Journal::whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
                ->whereDate('date', $sekarang->toDateString())
                ->pluck('schedule_id')->all();
        }

        return $jadwals->map(function ($j) use ($sekarang, $terisi, $cekJurnal) {
            $mulai = $j->jam_mulai ? Carbon::parse($sekarang->toDateString() . ' ' . $j->jam_mulai) : null;
            $selesai = $j->jam_selesai ? Carbon::parse($sekarang->toDateString() . ' ' . $j->jam_selesai) : null;

            if ($mulai && $selesai && $sekarang->between($mulai, $selesai)) {
                $j->status_waktu = 'berlangsung';
            } elseif ($selesai && $sekarang->greaterThan($selesai)) {
                $j->status_waktu = 'selesai';
            } else {
                $j->status_waktu = 'akan';
            }
            $j->jurnal_terisi = $cekJurnal ? in_array($j->id_jadwal, $terisi) : null;
            return $j;
        })->values();
    }
}
