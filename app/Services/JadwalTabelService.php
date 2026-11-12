<?php

namespace App\Services;

use App\Models\JadwalPelajaran;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun jadwal pelajaran menjadi tabel (jam ke- x hari) seperti jadwal cetak sekolah.
 * Hanya membaca data; tidak mengubah apa pun.
 */
class JadwalTabelService
{
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    /** Tahun ajaran aktif, atau null bila tidak ada jadwalnya pada putaran ini (maka semua tahun dipakai). */
    public static function tahunUntuk(string $putaran): ?string
    {
        $aktif = DB::table('tahun_ajaran')->where('is_aktif', 1)->value('id_tahun_ajaran');
        if ($aktif && DB::table('jadwal_pelajaran')->where('cycle_type', $putaran)->where('id_tahun_ajaran', $aktif)->exists()) {
            return (string) $aktif;
        }
        return null;
    }

    private static function dasar(string $putaran)
    {
        $q = DB::table('jadwal_pelajaran as j')
            ->leftJoin('mata_pelajaran as m', 'm.id_mapel', '=', 'j.id_mapel')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'j.id_guru')
            ->leftJoin('ruangan as r', 'r.id_ruangan', '=', 'j.id_ruangan_default')
            ->leftJoin('kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
            ->where('j.cycle_type', $putaran);
        if ($tahun = self::tahunUntuk($putaran)) {
            $q->where('j.id_tahun_ajaran', $tahun);
        }
        return $q;
    }

    public static function labelKelas($tingkat, $jurusan, $id): string
    {
        return trim(($tingkat ?? '') . ' ' . ($jurusan ?: $id));
    }

    /** [id_kelas => "X AK 1"] untuk kelas yang punya jadwal pada putaran ini, urut alami. */
    public static function daftarKelas(string $putaran): array
    {
        $rows = self::dasar($putaran)->select('k.id_kelas', 'k.tingkat', 'k.id_jurusan')->distinct()->get();
        $hasil = [];
        foreach ($rows as $r) {
            if ($r->id_kelas !== null) {
                $hasil[$r->id_kelas] = self::labelKelas($r->tingkat, $r->id_jurusan, $r->id_kelas);
            }
        }
        uasort($hasil, 'strnatcasecmp');
        return $hasil;
    }

    /** [id_guru wakil => nama_guru] untuk guru yang punya jadwal; satu entri per nama (data guru bisa dobel). */
    public static function daftarGuru(string $putaran): array
    {
        $rows = self::dasar($putaran)->select('g.id_guru', 'g.nama_guru')->distinct()->get();
        $perNama = [];
        foreach ($rows as $r) {
            $nama = trim((string) $r->nama_guru);
            if ($nama === '') {
                continue;
            }
            $kunci = mb_strtolower($nama);
            if (!isset($perNama[$kunci]) || $r->id_guru < $perNama[$kunci][0]) {
                $perNama[$kunci] = [$r->id_guru, $nama];
            }
        }
        $hasil = [];
        foreach ($perNama as [$id, $nama]) {
            $hasil[$id] = $nama;
        }
        uasort($hasil, 'strnatcasecmp');
        return $hasil;
    }

    /** Semua id_guru yang memakai nama yang sama dengan guru ini (satu orang bisa punya beberapa baris). */
    public static function idGuruSeorang(int $idGuru): array
    {
        $nama = DB::table('guru')->where('id_guru', $idGuru)->value('nama_guru');
        if ($nama === null) {
            return [$idGuru];
        }
        return DB::table('guru')->where('nama_guru', $nama)->pluck('id_guru')->map(fn ($v) => (int) $v)->all();
    }

    private static function menit(string $t): int
    {
        return ((int) substr($t, 0, 2)) * 60 + (int) substr($t, 3, 2);
    }

    /** Nomor jam ke- yang tercakup oleh rentang waktu (sesi dihitung bila terpakai minimal 20 menit). */
    private static function sesiTercakup(string $hari, string $mulai, string $selesai): array
    {
        $a = self::menit($mulai);
        $b = self::menit($selesai);
        $hasil = [];
        foreach (JadwalPelajaran::getDaftarSesi($hari) as $no => $s) {
            if (min($b, self::menit($s['selesai'])) - max($a, self::menit($s['mulai'])) >= 20) {
                $hasil[] = $no;
            }
        }
        return $hasil;
    }

    /**
     * @param string $mode  'kelas' | 'guru'
     * @param string|int $kunci id_kelas, atau id_guru
     */
    public static function susun(string $mode, $kunci, string $putaran): array
    {
        $q = self::dasar($putaran)->select(
            'j.id_jadwal', 'j.id_kelas', 'j.hari', 'j.jam_mulai', 'j.jam_selesai',
            'j.id_mapel', 'm.nama_mapel', 'g.nama_guru', 'r.nama_ruangan', 'j.id_ruangan_default',
            'k.tingkat', 'k.id_jurusan'
        );
        $judul = '';
        if ($mode === 'guru') {
            $ids = self::idGuruSeorang((int) $kunci);
            $q->whereIn('j.id_guru', $ids);
            $judul = (string) DB::table('guru')->where('id_guru', (int) $kunci)->value('nama_guru');
        } else {
            $q->where('j.id_kelas', (string) $kunci);
            $k = DB::table('kelas')->where('id_kelas', (string) $kunci)->first();
            $judul = $k ? self::labelKelas($k->tingkat, $k->id_jurusan, $k->id_kelas) : (string) $kunci;
        }
        $rows = $q->get();

        // Kumpulkan per hari: tiap jadwal -> rentang jam ke-
        $perHari = [];
        $lainnya = [];
        $jp = 0;
        foreach ($rows as $r) {
            if (!in_array($r->hari, self::HARI, true) || !$r->jam_mulai || !$r->jam_selesai) {
                $lainnya[] = $r;
                continue;
            }
            $tercakup = self::sesiTercakup($r->hari, substr($r->jam_mulai, 0, 5), substr($r->jam_selesai, 0, 5));
            if (!$tercakup) {
                $lainnya[] = $r;
                continue;
            }
            $item = [
                'id' => $r->id_jadwal,
                'mapel' => $r->nama_mapel ?: $r->id_mapel,
                'guru' => $r->nama_guru,
                'ruangan' => $r->nama_ruangan ?: $r->id_ruangan_default,
                'kelas' => self::labelKelas($r->tingkat, $r->id_jurusan, $r->id_kelas),
                'mulai' => substr($r->jam_mulai, 0, 5),
                'selesai' => substr($r->jam_selesai, 0, 5),
            ];
            $perHari[$r->hari][] = ['awal' => min($tercakup), 'akhir' => max($tercakup), 'item' => $item];
            $jp += max($tercakup) - min($tercakup) + 1;
        }

        $maksBaris = 0;
        $sesiHari = [];
        foreach (self::HARI as $h) {
            $sesiHari[$h] = JadwalPelajaran::getDaftarSesi($h);
            $maksBaris = max($maksBaris, count($sesiHari[$h]));
        }

        // Susun blok per hari; jadwal yang saling tumpang tindih digabung dalam satu kotak
        $grid = [];
        foreach (self::HARI as $h) {
            $blok = [];
            $daftar = $perHari[$h] ?? [];
            usort($daftar, fn ($x, $y) => [$x['awal'], $x['akhir']] <=> [$y['awal'], $y['akhir']]);
            foreach ($daftar as $d) {
                $n = count($blok);
                if ($n > 0 && $d['awal'] <= $blok[$n - 1]['akhir']) {
                    $blok[$n - 1]['akhir'] = max($blok[$n - 1]['akhir'], $d['akhir']);
                    $blok[$n - 1]['items'][] = $d['item'];
                } else {
                    $blok[] = ['awal' => $d['awal'], 'akhir' => $d['akhir'], 'items' => [$d['item']]];
                }
            }

            $jumlahSesi = count($sesiHari[$h]);
            $kolom = [];
            for ($baris = 1; $baris <= $maksBaris; $baris++) {
                $kolom[$baris] = ['tipe' => $baris > $jumlahSesi ? 'libur' : 'kosong'];
            }
            foreach ($blok as $b) {
                $kolom[$b['awal']] = ['tipe' => 'mulai', 'rowspan' => $b['akhir'] - $b['awal'] + 1, 'items' => $b['items'], 'bentrok' => count($b['items']) > 1];
                for ($x = $b['awal'] + 1; $x <= $b['akhir']; $x++) {
                    $kolom[$x] = ['tipe' => 'tutup'];
                }
            }
            $grid[$h] = $kolom;
        }

        return [
            'mode' => $mode,
            'judul' => $judul,
            'putaran' => $putaran,
            'hari' => self::HARI,
            'baris' => $maksBaris,
            'sesi' => $sesiHari,
            'grid' => $grid,
            'jp' => $jp,
            'total' => count($rows),
            'lainnya' => $lainnya,
            'kegiatan' => JadwalPelajaran::getKegiatanHari(),
        ];
    }
}
