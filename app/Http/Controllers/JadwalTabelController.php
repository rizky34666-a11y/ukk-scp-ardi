<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Services\JadwalHariIni;
use App\Services\JadwalTabelService;
use App\Support\TabelJadwalXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Jadwal pelajaran dalam bentuk tabel (jam ke- x hari), seperti jadwal cetak sekolah.
 * Dapat dibuka semua peran yang sudah login. Siswa hanya melihat kelasnya sendiri.
 */
class JadwalTabelController extends Controller
{
    private function cekLogin()
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }
        return null;
    }

    private function putaran(Request $request): string
    {
        $p = (string) $request->query('putaran', '');
        if (in_array($p, ['Putaran 1', 'Putaran 2'], true)) {
            return $p;
        }
        return SystemConfiguration::first()->active_cycle ?? 'Putaran 1';
    }

    /** @return array{mode:string, kunci:?string, putaran:string, kelas:array, guru:array, bolehPilih:bool, guruSaya:?int, kelasSaya:?string} */
    private function tentukan(Request $request): array
    {
        $role = (int) session('id_role');
        $putaran = $this->putaran($request);
        $kelas = JadwalTabelService::daftarKelas($putaran);
        $guru = JadwalTabelService::daftarGuru($putaran);

        $guruSaya = null;
        if (in_array($role, [2, 3, 7], true)) {
            $g = JadwalHariIni::guruByUsername(session('username'));
            $guruSaya = $g ? (int) $g->id_guru : null;
            if ($guruSaya !== null && !isset($guru[$guruSaya])) {
                // Cocokkan dengan entri per-nama (satu orang bisa punya beberapa baris guru)
                foreach (JadwalTabelService::idGuruSeorang($guruSaya) as $id) {
                    if (isset($guru[$id])) {
                        $guruSaya = $id;
                        break;
                    }
                }
            }
        }

        $kelasSaya = null;
        if ($role === 5) {
            $s = Siswa::where('nisn', session('username'))->first();
            $kelasSaya = $s && $s->id_kelas !== null ? (string) $s->id_kelas : null;
        } elseif ($role === 3) {
            $kelasSaya = Kelas::kelasWaliDariSesi();
        }

        $bolehPilih = $role !== 5;
        $mode = (string) $request->query('mode', '');
        if ($role === 5) {
            $mode = 'kelas';
        } elseif (!in_array($mode, ['kelas', 'guru'], true)) {
            $mode = match (true) {
                $role === 3 && $kelasSaya !== null => 'kelas',
                in_array($role, [2, 3, 7], true) && $guruSaya !== null => 'guru',
                default => 'kelas',
            };
        }

        $kunci = null;
        if ($mode === 'kelas') {
            $minta = (string) $request->query('kelas', '');
            if ($role === 5) {
                $kunci = $kelasSaya;
            } elseif ($minta !== '' && isset($kelas[$minta])) {
                $kunci = $minta;
            } elseif ($kelasSaya !== null) {
                $kunci = $kelasSaya;
            } else {
                $kunci = $kelas ? (string) array_key_first($kelas) : null;
            }
        } else {
            $minta = (int) $request->query('guru', 0);
            if ($minta > 0 && isset($guru[$minta])) {
                $kunci = (string) $minta;
            } elseif ($guruSaya !== null) {
                $kunci = (string) $guruSaya;
            } else {
                $kunci = $guru ? (string) array_key_first($guru) : null;
            }
        }

        return compact('mode', 'kunci', 'putaran', 'kelas', 'guru', 'bolehPilih', 'guruSaya', 'kelasSaya');
    }

    public function index(Request $request)
    {
        if ($r = $this->cekLogin()) {
            return $r;
        }
        $t = $this->tentukan($request);
        $matriks = $t['kunci'] !== null ? JadwalTabelService::susun($t['mode'], $t['kunci'], $t['putaran']) : null;

        return view('jadwal.tabel', $t + [
            'm' => $matriks,
            'role' => (int) session('id_role'),
            'hariIni' => ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat'][now('Asia/Jakarta')->format('l')] ?? null,
            'dihasilkan' => now('Asia/Jakarta')->format('d/m/y H.i'),
            'tahunNama' => ($tahun = JadwalTabelService::tahunUntuk($t['putaran'])) ? (DB::table('tahun_ajaran')->where('id_tahun_ajaran', $tahun)->value('nama_semester') ?: $tahun) : null,
        ]);
    }

    private function kirimXlsx(string $isi, string $nama)
    {
        return response($isi, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nama . '"',
        ]);
    }

    /** Unduh tabel yang sedang dilihat. */
    public function unduh(Request $request)
    {
        if ($r = $this->cekLogin()) {
            return $r;
        }
        $t = $this->tentukan($request);
        if ($t['kunci'] === null) {
            return back()->withErrors(['error' => 'Tidak ada jadwal untuk diunduh.']);
        }
        $m = JadwalTabelService::susun($t['mode'], $t['kunci'], $t['putaran']);
        $isi = TabelJadwalXlsx::buat([$m], now('Asia/Jakarta')->format('d/m/y H.i'));
        return $this->kirimXlsx($isi, 'jadwal_' . Str::slug($m['judul'], '_') . '_' . Str::slug($t['putaran'], '_') . '.xlsx');
    }

    /** Waka: semua kelas dalam satu file, satu sheet per kelas. */
    public function unduhSemua(Request $request)
    {
        if ($r = $this->cekLogin()) {
            return $r;
        }
        if ((int) session('id_role') !== 4) {
            return redirect()->route('tabel-jadwal.index')->withErrors(['error' => 'Unduh semua kelas hanya untuk Waka Kurikulum.']);
        }
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
        $putaran = $this->putaran($request);
        $daftar = [];
        foreach (array_keys(JadwalTabelService::daftarKelas($putaran)) as $idKelas) {
            $daftar[] = JadwalTabelService::susun('kelas', (string) $idKelas, $putaran);
        }
        if (!$daftar) {
            return back()->withErrors(['error' => 'Tidak ada jadwal untuk diunduh.']);
        }
        $isi = TabelJadwalXlsx::buat($daftar, now('Asia/Jakarta')->format('d/m/y H.i'));
        return $this->kirimXlsx($isi, 'jadwal_semua_kelas_' . Str::slug($putaran, '_') . '.xlsx');
    }
}
