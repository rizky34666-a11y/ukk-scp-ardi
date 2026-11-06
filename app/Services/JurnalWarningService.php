<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class JurnalWarningService
{
    /**
     * Mendapatkan daftar jadwal mengajar hari ini yang sudah lewat 30 menit dari jam pelajaran dimulai (jam_mulai)
     * namun guru pengampu belum mengisi jurnalnya.
     *
     * @param int|null $guruId
     * @return Collection
     */
    public static function getOverdueJadwal(?int $guruId = null): Collection
    {
        // Pastikan role Guru (id_role == 2) atau Wali Kelas (id_role == 3) atau jika guruId diberikan
        if ($guruId === null) {
            if (!in_array(session('id_role'), [2, 3])) {
                return collect([]);
            }

            $user = Auth::user();
            $username = $user ? $user->username : session('username');

            if (!$username) {
                return collect([]);
            }

            $guru = Guru::where('nip', $username)->first();
            if (!$guru) {
                $guru = Guru::where('nama_guru', session('nama_lengkap'))->first();
            }
            if (!$guru) {
                return collect([]);
            }

            $guruId = $guru->id_guru;
        }

        $now = Carbon::now();
        $tanggalHariIni = $now->toDateString();

        // Pemetaan nama hari bahasa Indonesia sesuai database
        $mapHari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $hariIni = $mapHari[$now->dayOfWeekIso] ?? null;
        if (!$hariIni) {
            return collect([]);
        }

        // Ambil konfigurasi siklus putaran aktif (Putaran 1 / Putaran 2)
        $config = SystemConfiguration::first();
        $activeCycle = $config?->active_cycle;

        $query = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'ruangan', 'guru'])
            ->where('id_guru', $guruId)
            ->where('hari', $hariIni);

        if ($activeCycle) {
            if (str_contains($activeCycle, '1')) {
                $query->where(function ($q) {
                    $q->where('cycle_type', 'like', '%1%')
                      ->orWhere('cycle_type', 'Semua Putaran')
                      ->orWhereNull('cycle_type');
                });
            } elseif (str_contains($activeCycle, '2')) {
                $query->where(function ($q) {
                    $q->where('cycle_type', 'like', '%2%')
                      ->orWhere('cycle_type', 'Semua Putaran')
                      ->orWhereNull('cycle_type');
                });
            }
        }

        $jadwals = $query->orderBy('jam_mulai', 'asc')->get();

        $overdueList = collect([]);

        foreach ($jadwals as $jadwal) {
            if (empty($jadwal->jam_mulai)) {
                continue;
            }

            // Cek apakah jurnal untuk sesi jadwal ini pada hari ini SUDAH diisi
            $sudahIsi = Journal::where('schedule_id', $jadwal->id_jadwal)
                ->whereDate('date', $tanggalHariIni)
                ->exists();

            if ($sudahIsi) {
                continue;
            }

            // Waktu mulai jadwal hari ini
            try {
                $startTime = Carbon::parse($tanggalHariIni . ' ' . $jadwal->jam_mulai);
                // Batas toleransi: 30 menit setelah jam pelajaran dimulai (jam_mulai)
                $threshold = $startTime->copy()->addMinutes(30);

                // Cek apakah waktu sekarang sudah melewati batas 30 menit dari jam pelajaran dimulai
                if ($now->gte($threshold)) {
                    $totalMinutesLate = abs((int) $now->diffInMinutes($startTime));
                    $minutesOverdue = abs((int) $now->diffInMinutes($threshold));

                    $jadwal->jam_mulai_formatted = $startTime->format('H:i');
                    $jadwal->jam_selesai_formatted = $jadwal->jam_selesai ? Carbon::parse($tanggalHariIni . ' ' . $jadwal->jam_selesai)->format('H:i') : '--:--';
                    $jadwal->threshold_formatted = $threshold->format('H:i');
                    $jadwal->minutes_overdue = $minutesOverdue;
                    $jadwal->total_minutes_late = $totalMinutesLate;
                    $jadwal->late_duration_text = self::formatDuration($totalMinutesLate);

                    $overdueList->push($jadwal);
                }
            } catch (\Exception $e) {
                // Abaikan jika format jam tidak valid
                continue;
            }
        }

        return $overdueList;
    }

    /**
     * Memformat menit keterlambatan ke dalam teks yang mudah dipahami.
     */
    private static function formatDuration(int $minutes): string
    {
        $minutes = abs($minutes);

        if ($minutes < 60) {
            return $minutes . ' menit yang lalu';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours . ' jam yang lalu';
        }

        return "{$hours} jam {$remainingMinutes} menit yang lalu";
    }
}
