<?php

namespace App\Services;

use App\Models\JadwalPelajaran;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

/**
 * Impor jadwal pelajaran dari Excel (.xlsx) / CSV.
 *
 * Format kolom: Kelas | Hari | Jam Ke Mulai | Jam Ke Selesai | Kode Mapel | NIP Guru | Ruangan
 * Jam diisi dengan "jam ke-" (sesuai struktur jam pelajaran sekolah), bukan jam dinding.
 *
 * Alur: bacaBaris() -> periksa() (tanpa mengubah database) -> simpan() (satu transaksi).
 */
class JadwalImportService
{
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    public const KOLOM = ['Kelas', 'Hari', 'Jam Ke Mulai', 'Jam Ke Selesai', 'Kode Mapel', 'NIP Guru', 'Ruangan'];
    private const BATAS_PESAN = 500;

    // ------------------------------------------------------------------
    // Pembacaan file
    // ------------------------------------------------------------------

    /** @return array<int, array{no:int, kelas:string, hari:string, mulai:string, selesai:string, mapel:string, guru:string, ruangan:string}> */
    public static function bacaBaris(string $path, string $ext): array
    {
        $sheets = ExcelImportService::parseAllSheets($path, $ext);

        $urut = [];
        foreach ($sheets as $s) {
            if (strcasecmp(trim((string) $s['sheet_name']), 'Jadwal') === 0) {
                array_unshift($urut, $s);
            } else {
                $urut[] = $s;
            }
        }

        foreach ($urut as $s) {
            $hasil = self::petakan($s['rows'], $s['row_numbers'] ?? []);
            if ($hasil !== null) {
                return $hasil;
            }
        }
        throw new Exception('Baris judul kolom tidak ditemukan. Gunakan format dari tombol "Unduh format Excel" (kolom: ' . implode(', ', self::KOLOM) . ').');
    }

    private static function petakan(array $rows, array $nomor): ?array
    {
        $batas = min(count($rows), 15);
        for ($i = 0; $i < $batas; $i++) {
            $peta = [];
            foreach ($rows[$i] as $c => $teks) {
                $n = strtolower(preg_replace('/[^a-z]/i', '', (string) $teks));
                if ($n === '') {
                    continue;
                }
                $kunci = match (true) {
                    str_contains($n, 'mulai') => 'mulai',
                    str_contains($n, 'selesai') => 'selesai',
                    str_contains($n, 'mapel') || str_contains($n, 'pelajaran') => 'mapel',
                    str_contains($n, 'nip') || str_contains($n, 'guru') => 'guru',
                    str_contains($n, 'ruang') => 'ruangan',
                    str_contains($n, 'kelas') => 'kelas',
                    str_contains($n, 'hari') => 'hari',
                    default => null,
                };
                if ($kunci !== null && !isset($peta[$kunci])) {
                    $peta[$kunci] = $c;
                }
            }
            if (count($peta) === 7) {
                $hasil = [];
                for ($j = $i + 1; $j < count($rows); $j++) {
                    $r = $rows[$j];
                    $b = ['no' => (int) ($nomor[$j] ?? ($j + 1))];
                    foreach ($peta as $k => $c) {
                        $b[$k] = trim((string) ($r[$c] ?? ''));
                    }
                    $hasil[] = $b;
                }
                return $hasil;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Pemeriksaan (tidak mengubah database)
    // ------------------------------------------------------------------

    private static function norm(string $s): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $s));
    }

    private static function normKelas(string $s): string
    {
        $c = self::norm($s);
        $c = preg_replace('/^10/', 'X', $c);
        $c = preg_replace('/^11/', 'XI', $c);
        return preg_replace('/^12/', 'XII', $c);
    }

    private static function labelKelas($k): string
    {
        return trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?: $k->id_kelas));
    }

    private static function bentukKunci(array $r): string
    {
        return implode('|', [$r['id_kelas'], $r['hari'], $r['jam_mulai'], $r['jam_selesai'], $r['id_mapel'], $r['id_guru'], $r['id_ruangan']]);
    }

    /**
     * @return array{
     *   total:int, error:array, peringatan:array, tambah:array, tetap:int, hapus_ids:array,
     *   hapus_jurnal:int, per_kelas:array, jumlah_error:int, jumlah_peringatan:int
     * }
     */
    public static function periksa(array $baris, string $tahunAjaran, string $putaran, string $mode): array
    {
        $kelasMaster = DB::table('kelas')->get();
        $labelKelas = [];
        $petaKelas = [];
        $petaKelasId = [];
        foreach ($kelasMaster as $k) {
            $label = self::labelKelas($k);
            $labelKelas[$k->id_kelas] = $label;
            $petaKelasId[(string) $k->id_kelas] = $k->id_kelas;
            foreach ([self::normKelas($label), self::normKelas($k->tingkat . ($k->id_jurusan ?? ''))] as $kunci) {
                if ($kunci !== '') {
                    $petaKelas[$kunci][$k->id_kelas] = true;
                }
            }
        }

        $namaMapel = DB::table('mata_pelajaran')->pluck('nama_mapel', 'id_mapel')->all();
        $petaMapel = [];
        foreach ($namaMapel as $id => $nama) {
            $petaMapel[self::norm((string) $id)][$id] = true;
        }
        $petaMapelNama = [];
        foreach ($namaMapel as $id => $nama) {
            $petaMapelNama[self::norm($nama)][$id] = true;
        }

        $guruRows = DB::table('guru')->get(['id_guru', 'nip', 'nama_guru']);
        $namaGuru = [];
        $petaGuru = [];
        foreach ($guruRows as $g) {
            $namaGuru[$g->id_guru] = $g->nama_guru;
            if ($g->nip !== null && trim($g->nip) !== '') {
                $petaGuru[strtolower(trim($g->nip))][$g->id_guru] = true;
            }
        }

        $ruanganRows = DB::table('ruangan')->get();
        $namaRuangan = [];
        $petaRuangan = [];
        foreach ($ruanganRows as $r) {
            $namaRuangan[$r->id_ruangan] = $r->nama_ruangan;
            $petaRuangan[self::norm($r->nama_ruangan)][$r->id_ruangan] = true;
            $pendek = preg_replace('/\s*\(.*\)\s*$/', '', $r->nama_ruangan);
            if ($pendek !== $r->nama_ruangan) {
                $petaRuangan[self::norm($pendek)][$r->id_ruangan] = true;
            }
        }
        $petaRuanganId = [];
        foreach ($ruanganRows as $r) {
            $petaRuanganId[strtolower((string) $r->id_ruangan)] = $r->id_ruangan;
        }

        $sesiHari = [];
        foreach (self::HARI as $h) {
            $sesiHari[$h] = JadwalPelajaran::getDaftarSesi($h);
        }

        $error = [];
        $recs = [];
        $kunciDilihat = [];
        $tambahError = function (int $no, string $pesan) use (&$error) {
            if (count($error) < self::BATAS_PESAN * 4) {
                $error[] = ['no' => $no, 'pesan' => $pesan];
            }
        };
        $jumlahError = 0;
        $total = 0;

        foreach ($baris as $b) {
            if ($b['kelas'] === '' && $b['hari'] === '' && $b['mulai'] === '' && $b['selesai'] === '' && $b['mapel'] === '' && $b['guru'] === '' && $b['ruangan'] === '') {
                continue;
            }
            $total++;
            $masalah = [];

            // Kelas
            $idKelas = null;
            if ($b['kelas'] === '') {
                $masalah[] = 'Kelas kosong';
            } else {
                if (ctype_digit($b['kelas']) && isset($petaKelasId[$b['kelas']])) {
                    $idKelas = $petaKelasId[$b['kelas']];
                } else {
                    $cocok = array_keys($petaKelas[self::normKelas($b['kelas'])] ?? []);
                    if (count($cocok) === 1) {
                        $idKelas = $cocok[0];
                    } elseif (count($cocok) > 1) {
                        $masalah[] = "Kelas '{$b['kelas']}' cocok dengan lebih dari satu kelas";
                    } else {
                        $masalah[] = "Kelas '{$b['kelas']}' tidak ditemukan (lihat sheet Kelas)";
                    }
                }
            }

            // Hari
            $hari = ucfirst(strtolower(str_replace(["'", '’', '`'], '', $b['hari'])));
            if ($b['hari'] === '') {
                $masalah[] = 'Hari kosong';
                $hari = null;
            } elseif ($hari === 'Sabtu' || $hari === 'Minggu') {
                $masalah[] = "Hari {$hari} tidak memiliki jam pelajaran";
                $hari = null;
            } elseif (!in_array($hari, self::HARI, true)) {
                $masalah[] = "Hari '{$b['hari']}' tidak dikenal (Senin–Jumat)";
                $hari = null;
            }

            // Jam ke
            $mulaiKe = $selesaiKe = null;
            foreach (['mulai' => 'Jam ke mulai', 'selesai' => 'Jam ke selesai'] as $k => $lbl) {
                if ($b[$k] === '') {
                    $masalah[] = $lbl . ' kosong';
                } elseif (!preg_match('/\d+/', $b[$k], $m)) {
                    $masalah[] = "{$lbl} '{$b[$k]}' bukan angka";
                } elseif ($k === 'mulai') {
                    $mulaiKe = (int) $m[0];
                } else {
                    $selesaiKe = (int) $m[0];
                }
            }
            $jamMulai = $jamSelesai = null;
            if ($hari !== null && $mulaiKe !== null && $selesaiKe !== null) {
                $sesi = $sesiHari[$hari];
                if (!isset($sesi[$mulaiKe]) || !isset($sesi[$selesaiKe])) {
                    $masalah[] = 'Jam ke-' . (!isset($sesi[$mulaiKe]) ? $mulaiKe : $selesaiKe) . " tidak ada pada hari {$hari} (tersedia 1–" . max(array_keys($sesi)) . ')';
                } elseif ($selesaiKe < $mulaiKe) {
                    $masalah[] = 'Jam ke selesai tidak boleh lebih kecil dari jam ke mulai';
                } else {
                    $jamMulai = $sesi[$mulaiKe]['mulai'] . ':00';
                    $jamSelesai = $sesi[$selesaiKe]['selesai'] . ':00';
                }
            }

            // Mapel
            $idMapel = null;
            if ($b['mapel'] === '') {
                $masalah[] = 'Kode mapel kosong';
            } else {
                $cocok = array_keys($petaMapel[self::norm($b['mapel'])] ?? []);
                if (!$cocok) {
                    $cocok = array_keys($petaMapelNama[self::norm($b['mapel'])] ?? []);
                }
                if (count($cocok) === 1) {
                    $idMapel = (string) $cocok[0];
                } elseif (count($cocok) > 1) {
                    $masalah[] = "Mapel '{$b['mapel']}' cocok dengan lebih dari satu mata pelajaran";
                } else {
                    $masalah[] = "Kode mapel '{$b['mapel']}' tidak ditemukan (lihat sheet Mapel)";
                }
            }

            // Guru (NIP)
            $idGuru = null;
            if ($b['guru'] === '') {
                $masalah[] = 'NIP guru kosong';
            } elseif (preg_match('/^\d(\.\d+)?E\+?\d+$/i', $b['guru'])) {
                $masalah[] = "NIP '{$b['guru']}' berubah menjadi angka ilmiah oleh Excel. Format kolom NIP sebagai Teks lalu ketik ulang";
            } elseif (preg_match('/^ID\s*[:\-]?\s*(\d+)$/i', $b['guru'], $mg)) {
                // Guru yang belum punya NIP dirujuk dengan ID (lihat sheet Guru)
                if (isset($namaGuru[(int) $mg[1]])) {
                    $idGuru = (int) $mg[1];
                } else {
                    $masalah[] = "Guru '{$b['guru']}' tidak ditemukan (lihat sheet Guru)";
                }
            } else {
                $cocok = array_keys($petaGuru[strtolower($b['guru'])] ?? []);
                if (count($cocok) === 1) {
                    $idGuru = $cocok[0];
                } elseif (count($cocok) > 1) {
                    $masalah[] = "NIP '{$b['guru']}' terdaftar pada lebih dari satu data guru";
                } else {
                    $masalah[] = "NIP '{$b['guru']}' tidak ditemukan (lihat sheet Guru)";
                }
            }

            // Ruangan
            $idRuangan = null;
            if ($b['ruangan'] === '') {
                $masalah[] = 'Ruangan kosong';
            } else {
                $cocok = array_keys($petaRuangan[self::norm($b['ruangan'])] ?? []);
                if (count($cocok) === 1) {
                    $idRuangan = (string) $cocok[0];
                } elseif (count($cocok) > 1) {
                    $masalah[] = "Ruangan '{$b['ruangan']}' cocok dengan lebih dari satu ruangan";
                } elseif (isset($petaRuanganId[strtolower($b['ruangan'])])) {
                    $idRuangan = (string) $petaRuanganId[strtolower($b['ruangan'])];
                } else {
                    $masalah[] = "Ruangan '{$b['ruangan']}' tidak ditemukan (lihat sheet Ruangan)";
                }
            }

            if ($masalah) {
                $jumlahError++;
                $tambahError($b['no'], implode('; ', $masalah));
                continue;
            }

            $rec = [
                'no' => $b['no'], 'id_kelas' => (string) $idKelas, 'hari' => $hari, 'jam_mulai' => $jamMulai, 'jam_selesai' => $jamSelesai,
                'jam_ke_mulai' => $mulaiKe, 'jam_ke_selesai' => $selesaiKe,
                'id_mapel' => $idMapel, 'id_guru' => (int) $idGuru, 'id_ruangan' => $idRuangan,
            ];
            $kunci = self::bentukKunci($rec);
            if (isset($kunciDilihat[$kunci])) {
                $jumlahError++;
                $tambahError($b['no'], "Baris ini sama persis dengan baris {$kunciDilihat[$kunci]}");
                continue;
            }
            $kunciDilihat[$kunci] = $b['no'];
            $rec['kunci'] = $kunci;
            $recs[] = $rec;
        }

        // Data yang sudah ada pada putaran ini
        $existing = [];
        foreach (DB::table('jadwal_pelajaran')->where('cycle_type', $putaran)->get() as $e) {
            $existing[$e->id_jadwal] = [
                'id' => $e->id_jadwal, 'tahun' => $e->id_tahun_ajaran, 'id_kelas' => (string) $e->id_kelas, 'hari' => $e->hari,
                'jam_mulai' => substr($e->jam_mulai, 0, 5) . ':00', 'jam_selesai' => substr($e->jam_selesai, 0, 5) . ':00',
                'id_mapel' => (string) $e->id_mapel, 'id_guru' => (int) $e->id_guru, 'id_ruangan' => (string) $e->id_ruangan_default,
            ];
            $existing[$e->id_jadwal]['kunci'] = self::bentukKunci($existing[$e->id_jadwal]);
        }

        $kelasDiFile = [];
        foreach ($recs as $r) {
            $kelasDiFile[$r['id_kelas']] = true;
        }

        $hapusIds = [];
        $kunciTetap = [];
        if ($mode === 'timpa') {
            $kunciBaru = array_flip(array_column($recs, 'kunci'));
            foreach ($existing as $id => $e) {
                if ($e['tahun'] === $tahunAjaran && isset($kelasDiFile[$e['id_kelas']])) {
                    if (isset($kunciBaru[$e['kunci']])) {
                        $kunciTetap[$e['kunci']] = true;
                    } else {
                        $hapusIds[] = $id;
                    }
                }
            }
        }
        $hapusSet = array_flip($hapusIds);

        $tambah = [];
        $tetap = 0;
        foreach ($recs as $r) {
            if (isset($kunciTetap[$r['kunci']])) {
                $tetap++;
            } else {
                $tambah[] = $r;
            }
        }

        // Bentrok: jadwal baru terhadap jadwal yang tersisa dan terhadap jadwal baru lainnya
        $sisaPerHari = [];
        foreach ($existing as $id => $e) {
            if (!isset($hapusSet[$id])) {
                $sisaPerHari[$e['hari']][] = $e;
            }
        }
        $peringatan = [];
        $jumlahPeringatan = 0;
        $deskripsi = fn (array $x) => ($labelKelas[$x['id_kelas']] ?? $x['id_kelas']) . ' / ' . ($namaMapel[$x['id_mapel']] ?? $x['id_mapel'])
            . ' ' . $x['hari'] . ' ' . substr($x['jam_mulai'], 0, 5) . '–' . substr($x['jam_selesai'], 0, 5);
        $baruPerHari = [];
        foreach ($tambah as $r) {
            $lawan = array_merge($sisaPerHari[$r['hari']] ?? [], $baruPerHari[$r['hari']] ?? []);
            $adaGuru = $adaKelas = false;
            foreach ($lawan as $e) {
                if (!($r['jam_mulai'] < $e['jam_selesai'] && $r['jam_selesai'] > $e['jam_mulai'])) {
                    continue;
                }
                $sumber = isset($e['no']) ? "baris {$e['no']} di file" : 'jadwal yang sudah ada';
                if ($e['id_kelas'] === $r['id_kelas'] && !$adaKelas) {
                    $adaKelas = true;
                    $jumlahError++;
                    $tambahError($r['no'], 'Kelas ' . ($labelKelas[$r['id_kelas']] ?? $r['id_kelas']) . " bentrok dengan {$sumber} (" . $deskripsi($e) . ')');
                }
                if ($e['id_guru'] === $r['id_guru'] && !$adaGuru) {
                    $adaGuru = true;
                    $jumlahError++;
                    $tambahError($r['no'], 'Guru ' . ($namaGuru[$r['id_guru']] ?? $r['id_guru']) . " bentrok dengan {$sumber} (" . $deskripsi($e) . ')');
                }
                if ($e['id_ruangan'] === $r['id_ruangan']) {
                    $jumlahPeringatan++;
                    if (count($peringatan) < self::BATAS_PESAN) {
                        $peringatan[] = ['no' => $r['no'], 'pesan' => 'Ruangan ' . ($namaRuangan[$r['id_ruangan']] ?? $r['id_ruangan']) . " dipakai bersamaan dengan {$sumber} (" . $deskripsi($e) . ')'];
                    }
                }
            }
            $baruPerHari[$r['hari']][] = $r;
        }

        // Jurnal yang ikut hilang
        $hapusJurnal = 0;
        if ($hapusIds && Schema::hasTable('journals')) {
            foreach (array_chunk($hapusIds, 500) as $chunk) {
                $hapusJurnal += DB::table('journals')->whereIn('schedule_id', $chunk)->count();
            }
        }

        // Ringkasan per kelas
        $perKelas = [];
        foreach ($recs as $r) {
            $perKelas[$r['id_kelas']]['label'] = $labelKelas[$r['id_kelas']] ?? $r['id_kelas'];
            $perKelas[$r['id_kelas']]['baru'] = ($perKelas[$r['id_kelas']]['baru'] ?? 0) + (isset($kunciTetap[$r['kunci']]) ? 0 : 1);
            $perKelas[$r['id_kelas']]['tetap'] = ($perKelas[$r['id_kelas']]['tetap'] ?? 0) + (isset($kunciTetap[$r['kunci']]) ? 1 : 0);
            $perKelas[$r['id_kelas']]['hapus'] = $perKelas[$r['id_kelas']]['hapus'] ?? 0;
        }
        foreach ($hapusIds as $id) {
            $k = $existing[$id]['id_kelas'];
            $perKelas[$k]['label'] = $labelKelas[$k] ?? $k;
            $perKelas[$k]['baru'] = $perKelas[$k]['baru'] ?? 0;
            $perKelas[$k]['tetap'] = $perKelas[$k]['tetap'] ?? 0;
            $perKelas[$k]['hapus'] = ($perKelas[$k]['hapus'] ?? 0) + 1;
        }
        uasort($perKelas, fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));

        // Isi pratinjau dengan nama yang mudah dibaca
        foreach ($tambah as &$t) {
            $t['kelas_label'] = $labelKelas[$t['id_kelas']] ?? $t['id_kelas'];
            $t['mapel_nama'] = $namaMapel[$t['id_mapel']] ?? $t['id_mapel'];
            $t['guru_nama'] = $namaGuru[$t['id_guru']] ?? $t['id_guru'];
            $t['ruangan_nama'] = $namaRuangan[$t['id_ruangan']] ?? $t['id_ruangan'];
        }
        unset($t);

        usort($error, fn ($a, $b) => $a['no'] <=> $b['no']);

        return [
            'total' => $total, 'error' => $error, 'jumlah_error' => $jumlahError,
            'peringatan' => $peringatan, 'jumlah_peringatan' => $jumlahPeringatan,
            'tambah' => $tambah, 'tetap' => $tetap, 'hapus_ids' => $hapusIds, 'hapus_jurnal' => $hapusJurnal,
            'per_kelas' => $perKelas,
        ];
    }

    // ------------------------------------------------------------------
    // Penyimpanan
    // ------------------------------------------------------------------

    /** @return array{tambah:int, hapus:int} */
    public static function simpan(array $hasil, string $tahunAjaran, string $putaran): array
    {
        DB::transaction(function () use ($hasil, $tahunAjaran, $putaran) {
            self::hapusJadwal($hasil['hapus_ids']);
            $isi = [];
            foreach ($hasil['tambah'] as $r) {
                $isi[] = [
                    'id_tahun_ajaran' => $tahunAjaran, 'id_kelas' => $r['id_kelas'], 'id_mapel' => $r['id_mapel'], 'id_guru' => $r['id_guru'],
                    'id_ruangan_default' => $r['id_ruangan'], 'hari' => $r['hari'], 'jam_mulai' => $r['jam_mulai'], 'jam_selesai' => $r['jam_selesai'],
                    'cycle_type' => $putaran,
                ];
            }
            foreach (array_chunk($isi, 200) as $chunk) {
                DB::table('jadwal_pelajaran')->insert($chunk);
            }
        });
        return ['tambah' => count($hasil['tambah']), 'hapus' => count($hasil['hapus_ids'])];
    }

    /** Hapus jadwal beserta jurnal, kehadiran, presensi, dan pengajuan tukar yang menempel padanya. */
    private static function hapusJadwal(array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            if (Schema::hasTable('journals')) {
                if (Schema::hasTable('attendances')) {
                    foreach (array_chunk(DB::table('journals')->whereIn('schedule_id', $chunk)->pluck('id')->all(), 500) as $j) {
                        DB::table('attendances')->whereIn('journal_id', $j)->delete();
                    }
                }
                DB::table('journals')->whereIn('schedule_id', $chunk)->delete();
            }
            if (Schema::hasTable('presensi_header')) {
                foreach (array_chunk(DB::table('presensi_header')->whereIn('id_jadwal', $chunk)->pluck('id_presensi')->all(), 500) as $p) {
                    if (Schema::hasTable('presensi_detail')) {
                        DB::table('presensi_detail')->whereIn('id_presensi', $p)->delete();
                    }
                }
                DB::table('presensi_header')->whereIn('id_jadwal', $chunk)->delete();
            }
            if (Schema::hasTable('tukar_jadwal')) {
                DB::table('tukar_jadwal')->where(fn ($w) => $w->whereIn('id_jadwal_pemohon', $chunk)->orWhereIn('id_jadwal_tujuan', $chunk))->delete();
            }
            DB::table('jadwal_pelajaran')->whereIn('id_jadwal', $chunk)->delete();
        }
    }

    // ------------------------------------------------------------------
    // Template Excel
    // ------------------------------------------------------------------

    /** Template .xlsx: sheet Jadwal (diisi jadwal saat ini bila $putaran diberikan) + sheet rujukan. */
    public static function buatTemplate(?string $putaran = null, ?string $tahunAjaran = null): string
    {
        $kelasMaster = DB::table('kelas')->orderBy('tingkat')->orderBy('id_jurusan')->orderBy('id_kelas')->get();
        $labelKelas = [];
        foreach ($kelasMaster as $k) {
            $labelKelas[$k->id_kelas] = self::labelKelas($k);
        }
        $namaRuangan = DB::table('ruangan')->pluck('nama_ruangan', 'id_ruangan')->all();
        $nipGuru = [];
        foreach (DB::table('guru')->get(['id_guru', 'nip']) as $g) {
            $nipGuru[$g->id_guru] = trim((string) $g->nip) !== '' ? trim((string) $g->nip) : 'ID:' . $g->id_guru;
        }

        $sesiPerHari = [];
        foreach (self::HARI as $h) {
            $sesiPerHari[$h] = JadwalPelajaran::getDaftarSesi($h);
        }

        $isi = [self::KOLOM];
        if ($putaran !== null) {
            $q = DB::table('jadwal_pelajaran')->where('cycle_type', $putaran);
            if ($tahunAjaran) {
                $q->where('id_tahun_ajaran', $tahunAjaran);
            }
            $urutHari = array_flip(self::HARI);
            $semua = $q->get()->all();
            usort($semua, function ($a, $b) use ($labelKelas, $urutHari) {
                return strnatcasecmp($labelKelas[$a->id_kelas] ?? (string) $a->id_kelas, $labelKelas[$b->id_kelas] ?? (string) $b->id_kelas)
                    ?: (($urutHari[$a->hari] ?? 9) <=> ($urutHari[$b->hari] ?? 9))
                    ?: strcmp((string) $a->jam_mulai, (string) $b->jam_mulai);
            });
            foreach ($semua as $j) {
                $mulaiKe = $selesaiKe = '';
                foreach ($sesiPerHari[$j->hari] ?? [] as $no => $s) {
                    if ($s['mulai'] === substr($j->jam_mulai, 0, 5)) {
                        $mulaiKe = (string) $no;
                    }
                    if ($s['selesai'] === substr($j->jam_selesai, 0, 5)) {
                        $selesaiKe = (string) $no;
                    }
                }
                $isi[] = [
                    $labelKelas[$j->id_kelas] ?? (string) $j->id_kelas, $j->hari, $mulaiKe, $selesaiKe,
                    (string) $j->id_mapel, (string) ($nipGuru[$j->id_guru] ?? ''), $namaRuangan[$j->id_ruangan_default] ?? (string) $j->id_ruangan_default,
                ];
            }
        }

        $petunjuk = [
            ['PETUNJUK PENGISIAN JADWAL'],
            [''],
            ['1. Isi sheet "Jadwal": satu baris = satu sesi pelajaran (satu kelas, satu hari, satu guru).'],
            ['2. Kelas: tulis seperti di sheet "Kelas", contoh: X RPL 1.'],
            ['3. Hari: Senin, Selasa, Rabu, Kamis, atau Jumat.'],
            ['4. Jam Ke Mulai dan Jam Ke Selesai: nomor jam pelajaran (lihat sheet "Jam"). Pelajaran 2 jam di jam ke-1 sampai ke-2 ditulis mulai 1, selesai 2.'],
            ['5. Kode Mapel: kode seperti di sheet "Mapel", contoh: BIN.'],
            ['6. NIP Guru: NIP persis seperti di sheet "Guru". Guru yang belum punya NIP ditulis dengan kode ID seperti di sheet "Guru", contoh: ID:358. Kolom NIP sudah berformat Teks; jangan diubah ke Angka karena Excel akan merusak NIP panjang.'],
            ['7. Ruangan: nama ruangan seperti di sheet "Ruangan", contoh: R 01 atau L 16.'],
            ['8. Jangan mengubah nama kolom di baris pertama dan jangan mengubah urutan kolom.'],
            ['9. Setelah selesai, unggah file ini dari Kelola Jadwal > Import Jadwal. Sistem memeriksa dulu dan menampilkan pratinjau; belum ada data yang berubah sampai Anda menekan Simpan.'],
            ['10. Jika ada satu saja baris yang salah, tidak ada yang disimpan. Perbaiki di Excel lalu unggah ulang.'],
        ];

        $jam = [['Hari', 'Jam Ke', 'Mulai', 'Selesai']];
        foreach ($sesiPerHari as $h => $sesi) {
            foreach ($sesi as $no => $s) {
                $jam[] = [$h, (string) $no, $s['mulai'], $s['selesai']];
            }
        }

        $kelas = [['Kelas (tulis seperti ini)']];
        foreach ($labelKelas as $label) {
            $kelas[] = [$label];
        }

        $mapel = [['Kode Mapel', 'Nama Mata Pelajaran']];
        foreach (DB::table('mata_pelajaran')->orderBy('id_mapel')->get() as $m) {
            $mapel[] = [(string) $m->id_mapel, $m->nama_mapel];
        }

        $guru = [['NIP Guru', 'Nama Guru']];
        foreach (DB::table('guru')->orderBy('nama_guru')->get() as $g) {
            $nip = trim((string) $g->nip);
            if ($nip === '') {
                $guru[] = ['ID:' . $g->id_guru, $g->nama_guru . ' (belum punya NIP, tulis kode ini)'];
            } elseif (!preg_match('/^(wali|bk|guru)_/i', $nip)) {
                $guru[] = [$nip, $g->nama_guru];
            }
        }

        $ruangan = [['Ruangan (tulis seperti ini)']];
        foreach (DB::table('ruangan')->orderBy('nama_ruangan')->get() as $r) {
            $ruangan[] = [$r->nama_ruangan];
        }

        return self::tulisXlsx([
            ['nama' => 'Jadwal', 'rows' => $isi, 'lebar' => [16, 11, 14, 16, 14, 24, 22], 'teks' => true, 'dropdownHari' => true],
            ['nama' => 'Petunjuk', 'rows' => $petunjuk, 'lebar' => [120], 'judul' => false],
            ['nama' => 'Jam', 'rows' => $jam, 'lebar' => [12, 10, 10, 10]],
            ['nama' => 'Kelas', 'rows' => $kelas, 'lebar' => [28]],
            ['nama' => 'Mapel', 'rows' => $mapel, 'lebar' => [18, 50], 'teks' => true],
            ['nama' => 'Guru', 'rows' => $guru, 'lebar' => [24, 60], 'teks' => true],
            ['nama' => 'Ruangan', 'rows' => $ruangan, 'lebar' => [30]],
        ]);
    }

    private static function huruf(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private static function xml(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Penulis .xlsx minimal (tanpa library): teks inline, baris judul tebal, kolom bisa berformat Teks. */
    private static function tulisXlsx(array $sheets): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        foreach ($sheets as $i => $sh) {
            $n = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wb .= '<sheet name="' . self::xml($sh['nama']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';

            $teks = !empty($sh['teks']);
            $judul = $sh['judul'] ?? true;
            $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
            if ($judul) {
                $x .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
            }
            $x .= '<cols>';
            foreach ($sh['lebar'] as $c => $w) {
                $x .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"' . ($teks ? ' style="1"' : '') . '/>';
            }
            $x .= '</cols><sheetData>';
            foreach ($sh['rows'] as $ri => $row) {
                $x .= '<row r="' . ($ri + 1) . '">';
                foreach ($row as $ci => $val) {
                    $val = (string) $val;
                    $style = ($judul && $ri === 0) ? ' s="2"' : ($teks ? ' s="1"' : '');
                    if ($val === '') {
                        if ($style !== '') {
                            $x .= '<c r="' . self::huruf($ci) . ($ri + 1) . '"' . $style . '/>';
                        }
                        continue;
                    }
                    $x .= '<c r="' . self::huruf($ci) . ($ri + 1) . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . self::xml($val) . '</t></is></c>';
                }
                $x .= '</row>';
            }
            $x .= '</sheetData>';
            if (!empty($sh['dropdownHari'])) {
                $x .= '<dataValidations count="1"><dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Hari tidak valid" error="Pilih Senin, Selasa, Rabu, Kamis, atau Jumat." sqref="B2:B3000"><formula1>"Senin,Selasa,Rabu,Kamis,Jumat"</formula1></dataValidation></dataValidations>';
            }
            $x .= '</worksheet>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', $x);
        }

        $ct .= '</Types>';
        $wb .= '</sheets></workbook>';
        $rels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F3864"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>';

        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->close();

        $isi = file_get_contents($tmp);
        @unlink($tmp);
        return $isi;
    }
}
