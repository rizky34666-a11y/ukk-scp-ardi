<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Master data: Siswa dan Mata Pelajaran.
 * Khusus Waka Kurikulum. Penghapusan diperbolehkan; data turunannya (jadwal, jurnal, kehadiran, dsb.)
 * ikut dihapus setelah Waka mengonfirmasi dampaknya.
 */
class MasterDataController extends Controller
{
    private const PER_HALAMAN = 25;

    /** Label pemakaian yang ditampilkan di tabel dan jendela hapus. */
    private const LABEL_PAKAI = [
        'jadwal' => 'jadwal pelajaran',
        'jurnal' => 'jurnal mengajar',
        'kehadiran' => 'catatan kehadiran',
        'presensi' => 'presensi (lama)',
        'izin siswa' => 'pengajuan izin siswa',
        'akun login' => 'akun login siswa',
    ];

    /** Pengaturan tiap entitas: tabel, kolom, validasi, dan data turunan yang terdampak saat dihapus. */
    private function konfigurasi(): array
    {
        return [
            'siswa' => [
                'judul' => 'Siswa', 'ikon' => 'fa-user-graduate', 'tabel' => 'siswa', 'pk' => 'id_siswa',
                'pk_auto' => true, 'modul' => 'master_siswa',
                'kolom' => [
                    ['name' => 'nisn', 'label' => 'NISN / NIS', 'type' => 'text', 'maks' => 20, 'wajib' => true, 'petunjuk' => 'Sama dengan username login siswa. Jika diubah, username akun ikut berubah.'],
                    ['name' => 'nama_siswa', 'label' => 'Nama Siswa', 'type' => 'text', 'maks' => 100, 'wajib' => true],
                    ['name' => 'id_kelas', 'label' => 'Kelas', 'type' => 'select', 'opsi_dinamis' => 'kelas', 'wajib' => false, 'kosong' => '- Belum ada kelas -'],
                    ['name' => 'status_utama', 'label' => 'Status', 'type' => 'select', 'opsi' => ['Aktif' => 'Aktif', 'Lulus' => 'Lulus', 'Keluar' => 'Keluar', 'DO' => 'DO'], 'wajib' => true, 'default' => 'Aktif'],
                ],
                'tampil' => [['nisn', 'NISN / NIS', 'kode'], ['nama_siswa', 'Nama Siswa'], ['nama_kelas', 'Kelas', 'badge'], ['status_utama', 'Status', 'status_siswa'], ['punya_akun', 'Akun Login', 'akun']],
                'cari' => ['siswa.nisn', 'siswa.nama_siswa'],
                'urut' => ['siswa.nama_siswa', 'asc'],
            ],
            'mata-pelajaran' => [
                'judul' => 'Mata Pelajaran', 'ikon' => 'fa-book', 'tabel' => 'mata_pelajaran', 'pk' => 'id_mapel',
                'pk_auto' => false, 'pk_label' => 'Kode Mapel', 'pk_petunjuk' => 'Contoh: BIN atau DDPK-AKL (tidak dapat diubah setelah dibuat)', 'pk_maks' => 20,
                'modul' => 'master_mapel',
                'kolom' => [
                    ['name' => 'nama_mapel', 'label' => 'Nama Mata Pelajaran', 'type' => 'text', 'maks' => 100, 'wajib' => true],
                ],
                'tampil' => [['pk', 'Kode', 'kode'], ['nama_mapel', 'Nama Mata Pelajaran']],
                'cari' => ['id_mapel', 'nama_mapel'],
                'urut' => ['nama_mapel', 'asc'],
            ],
        ];
    }

    private function cekAkses()
    {
        if (!session()->has('id_user') || (int) session('id_role') !== 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Master data hanya dapat dikelola oleh Waka Kurikulum.']);
        }
        return null;
    }

    private function ambilKonfig(string $entitas): array
    {
        $k = $this->konfigurasi();
        abort_unless(isset($k[$entitas]), 404);
        return $k[$entitas] + ['key' => $entitas];
    }

    /** [id_kelas => "X RPL 1"] */
    private function daftarKelas(): array
    {
        $hasil = [];
        foreach (DB::table('kelas')->orderBy('tingkat')->orderBy('id_jurusan')->orderBy('id_kelas')->get() as $k) {
            $hasil[$k->id_kelas] = trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?: $k->id_kelas));
        }
        return $hasil;
    }

    public function index(Request $request, string $entitas)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $cfg = $this->ambilKonfig($entitas);
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', '');
        $kelasFilter = (string) $request->query('kelas', '');

        $query = DB::table($cfg['tabel']);
        if ($entitas === 'siswa') {
            $query->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->select('siswa.*', 'kelas.tingkat as kelas_tingkat', 'kelas.id_jurusan as kelas_jurusan')
                ->selectRaw("(SELECT COUNT(*) FROM users WHERE users.username = siswa.nisn AND users.id_role = 5) as punya_akun");
            if ($kelasFilter === '_tanpa') {
                $query->whereNull('siswa.id_kelas');
            } elseif ($kelasFilter !== '') {
                $query->where('siswa.id_kelas', $kelasFilter);
            }
            if (in_array($filter, ['Aktif', 'Lulus', 'Keluar', 'DO'], true)) {
                $query->where('siswa.status_utama', $filter);
            } elseif ($filter === 'tanpa_akun') {
                $query->whereNotExists(function ($w) {
                    $w->select(DB::raw(1))->from('users')->whereColumn('users.username', 'siswa.nisn')->where('users.id_role', 5);
                });
            }
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($cfg, $like) {
                foreach ($cfg['cari'] as $kol) {
                    $w->orWhere($kol, 'like', $like);
                }
            });
        }
        $query->orderBy($cfg['urut'][0], $cfg['urut'][1]);
        $rows = $query->paginate(self::PER_HALAMAN)->withQueryString();

        $kelasMap = $this->daftarKelas();
        if ($entitas === 'siswa') {
            $rows->getCollection()->transform(function ($r) use ($kelasMap) {
                $r->nama_kelas = $r->id_kelas ? ($kelasMap[$r->id_kelas] ?? ('Kelas ' . $r->id_kelas)) : null;
                return $r;
            });
        }

        $ids = $rows->getCollection()->pluck($cfg['pk'])->all();
        $pakai = $this->hitungPemakaian($cfg, $ids);

        $jumlah = [];
        foreach ($this->konfigurasi() as $kode => $c) {
            $jumlah[$kode] = DB::table($c['tabel'])->count();
        }

        $opsiDinamis = ['kelas' => $kelasMap];

        return view('master.index', [
            'cfg' => $cfg, 'entitas' => $entitas, 'semua' => $this->konfigurasi(), 'rows' => $rows,
            'pakai' => $pakai, 'jumlah' => $jumlah, 'q' => $q, 'filter' => $filter, 'kelasFilter' => $kelasFilter,
            'opsiDinamis' => $opsiDinamis, 'labelPakai' => self::LABEL_PAKAI,
            'jumlahTanpaAkun' => $entitas === 'siswa' ? DB::table('siswa')->whereNotExists(function ($w) {
                $w->select(DB::raw(1))->from('users')->whereColumn('users.username', 'siswa.nisn')->where('users.id_role', 5);
            })->count() : 0,
            'jumlahTanpaKelas' => $entitas === 'siswa' ? DB::table('siswa')->whereNull('id_kelas')->count() : 0,
        ]);
    }

    /** [id => ['jadwal' => n, 'jurnal' => n, ...]] untuk daftar id pada halaman ini. */
    private function hitungPemakaian(array $cfg, array $ids): array
    {
        $hasil = [];
        foreach ($ids as $id) {
            $hasil[$id] = [];
        }
        if (empty($ids)) {
            return $hasil;
        }

        if ($cfg['key'] === 'mata-pelajaran') {
            $jadwal = DB::table('jadwal_pelajaran')->select('id_mapel', DB::raw('COUNT(*) as n'))->whereIn('id_mapel', $ids)->groupBy('id_mapel')->pluck('n', 'id_mapel');
            foreach ($jadwal as $id => $n) {
                $hasil[$id]['jadwal'] = (int) $n;
            }
            if (Schema::hasTable('journals')) {
                $jurnal = DB::table('journals')->join('jadwal_pelajaran', 'journals.schedule_id', '=', 'jadwal_pelajaran.id_jadwal')
                    ->select('jadwal_pelajaran.id_mapel as k', DB::raw('COUNT(*) as n'))
                    ->whereIn('jadwal_pelajaran.id_mapel', $ids)->groupBy('jadwal_pelajaran.id_mapel')->pluck('n', 'k');
                foreach ($jurnal as $id => $n) {
                    $hasil[$id]['jurnal'] = (int) $n;
                }
            }
            return $hasil;
        }

        // Siswa
        $tabelPerJenis = [
            ['kehadiran', 'attendances', 'student_id'],
            ['presensi', 'presensi_detail', 'id_siswa'],
            ['izin siswa', 'leave_requests', 'student_id'],
        ];
        foreach ($tabelPerJenis as [$label, $tabel, $kolom]) {
            if (!Schema::hasTable($tabel)) {
                continue;
            }
            $rows = DB::table($tabel)->select($kolom, DB::raw('COUNT(*) as n'))->whereIn($kolom, $ids)->groupBy($kolom)->pluck('n', $kolom);
            foreach ($rows as $id => $n) {
                $hasil[$id][$label] = (int) $n;
            }
        }
        $akun = DB::table('siswa')->join('users', function ($j) {
            $j->on('users.username', '=', 'siswa.nisn')->where('users.id_role', 5);
        })->whereIn('siswa.id_siswa', $ids)->pluck('siswa.id_siswa');
        foreach ($akun as $id) {
            $hasil[$id]['akun login'] = 1;
        }
        return $hasil;
    }

    private function aturan(array $cfg, ?string $idSaatIni = null, bool $baru = false): array
    {
        $aturan = [];
        if ($baru && !$cfg['pk_auto']) {
            $aturan[$cfg['pk']] = ['required', 'string', 'max:' . $cfg['pk_maks'], 'regex:/^[A-Za-z0-9 _.\-]+$/', Rule::unique($cfg['tabel'], $cfg['pk'])];
        }
        foreach ($cfg['kolom'] as $kol) {
            $r = [$kol['wajib'] ? 'required' : 'nullable'];
            if ($kol['type'] === 'select') {
                $opsi = $kol['opsi'] ?? null;
                if ($opsi !== null) {
                    $r[] = Rule::in(array_map('strval', array_keys($opsi)));
                } else {
                    $r[] = 'string';
                }
            } else {
                $r[] = 'string';
                $r[] = 'max:' . $kol['maks'];
            }
            $aturan[$kol['name']] = $r;
        }
        if ($cfg['key'] === 'siswa') {
            $aturan['nisn'][] = 'regex:/^[A-Za-z0-9._\-]+$/';
            $aturan['nisn'][] = Rule::unique('siswa', 'nisn')->ignore($idSaatIni, 'id_siswa');
            $aturan['id_kelas'][] = Rule::exists('kelas', 'id_kelas');
        }
        return $aturan;
    }

    private function pesan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah dipakai data lain.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'regex' => ':attribute hanya boleh huruf, angka, titik, garis bawah, dan tanda hubung (tanpa spasi).',
            'in' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan.',
        ];
    }

    private function namaAtribut(array $cfg): array
    {
        $a = [$cfg['pk'] => $cfg['pk_label'] ?? 'Kode'];
        foreach ($cfg['kolom'] as $kol) {
            $a[$kol['name']] = $kol['label'];
        }
        return $a;
    }

    private function ambilData(Request $request, array $cfg): array
    {
        $data = [];
        foreach ($cfg['kolom'] as $kol) {
            $v = $request->input($kol['name']);
            $v = is_string($v) ? trim($v) : $v;
            $data[$kol['name']] = ($v === '' || $v === null) ? null : $v;
        }
        return $data;
    }

    private function labelData(array $cfg, $row): string
    {
        $row = (array) $row;
        return match ($cfg['key']) {
            'siswa' => ($row['nama_siswa'] ?? '') . ' (' . ($row['nisn'] ?? '-') . ')',
            'mata-pelajaran' => ($row['nama_mapel'] ?? '') . ' (' . $row['id_mapel'] . ')',
        };
    }

    public function store(Request $request, string $entitas)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $cfg = $this->ambilKonfig($entitas);
        $request->validate($this->aturan($cfg, null, true), $this->pesan(), $this->namaAtribut($cfg));

        $data = $this->ambilData($request, $cfg);
        if (!$cfg['pk_auto']) {
            $data[$cfg['pk']] = trim((string) $request->input($cfg['pk']));
        }

        DB::transaction(function () use ($cfg, $data, &$id) {
            if ($cfg['pk_auto']) {
                $id = DB::table($cfg['tabel'])->insertGetId($data, $cfg['pk']);
            } else {
                DB::table($cfg['tabel'])->insert($data);
                $id = $data[$cfg['pk']];
            }
        });

        Audit::catat('tambah', $cfg['modul'], $id, 'Menambah data ' . strtolower($cfg['judul']) . ': ' . $this->labelData($cfg, $data + [$cfg['pk'] => $id]), null, $data);
        $catatan = $entitas === 'siswa' ? ' Akun login dibuat lewat menu Manajemen Akun (role Siswa, username = NISN).' : '';
        return redirect()->route('master.index', $entitas)->with('success', $cfg['judul'] . ' berhasil ditambahkan.' . $catatan);
    }

    public function update(Request $request, string $entitas, string $id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $cfg = $this->ambilKonfig($entitas);
        $lama = DB::table($cfg['tabel'])->where($cfg['pk'], $id)->first();
        if (!$lama) {
            return back()->withErrors(['error' => 'Data tidak ditemukan (mungkin sudah dihapus).']);
        }
        $request->validate($this->aturan($cfg, $id, false), $this->pesan(), $this->namaAtribut($cfg));
        $data = $this->ambilData($request, $cfg);

        // NISN adalah username akun siswa: pastikan tidak bentrok dengan akun lain.
        $akunSiswa = null;
        if ($entitas === 'siswa' && $data['nisn'] !== $lama->nisn) {
            $akunSiswa = DB::table('users')->where('username', $lama->nisn)->where('id_role', 5)->first();
            $bentrok = DB::table('users')->where('username', $data['nisn']);
            if ($akunSiswa) {
                $bentrok->where('id_user', '!=', $akunSiswa->id_user);
            }
            if ($bentrok->exists()) {
                return back()->withInput()->withErrors(['nisn' => 'NISN / NIS sudah dipakai sebagai username akun lain.']);
            }
        }

        DB::transaction(function () use ($cfg, $id, $data, $akunSiswa, $entitas) {
            DB::table($cfg['tabel'])->where($cfg['pk'], $id)->update($data);
            if ($entitas === 'siswa' && $akunSiswa) {
                DB::table('users')->where('id_user', $akunSiswa->id_user)->update(['username' => $data['nisn'], 'nama_lengkap' => $data['nama_siswa']]);
            } elseif ($entitas === 'siswa') {
                // Nama di akun ikut diselaraskan bila akun ada
                DB::table('users')->where('username', $data['nisn'])->where('id_role', 5)->update(['nama_lengkap' => $data['nama_siswa']]);
            }
        });

        Audit::catat('ubah', $cfg['modul'], $id, 'Mengubah data ' . strtolower($cfg['judul']) . ': ' . $this->labelData($cfg, (array) $lama), (array) $lama, $data);
        return redirect()->back()->with('success', $cfg['judul'] . ' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $entitas, string $id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $cfg = $this->ambilKonfig($entitas);
        $lama = DB::table($cfg['tabel'])->where($cfg['pk'], $id)->first();
        if (!$lama) {
            return back()->withErrors(['error' => 'Data tidak ditemukan (mungkin sudah dihapus).']);
        }

        $pakai = $this->hitungPemakaian($cfg, [$id])[$id] ?? [];
        if (!empty($pakai) && !$request->boolean('konfirmasi')) {
            return back()->withErrors(['error' => 'Penghapusan dibatalkan: centang konfirmasi karena data ini masih dipakai oleh data lain.']);
        }

        try {
            DB::transaction(fn () => $this->hapusBeserta($cfg, $id, $lama));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Gagal menghapus: data ini masih terkait dengan data lain yang tidak dapat dihapus otomatis. Detail teknis tercatat di log server.']);
        }

        $ringkas = collect($pakai)->map(fn ($n, $k) => ($k === 'akun login' ? '' : "$n ") . $k)->implode(', ');
        Audit::catat('hapus', $cfg['modul'], $id,
            'Menghapus data ' . strtolower($cfg['judul']) . ': ' . $this->labelData($cfg, (array) $lama) . ($ringkas ? ' (ikut terhapus: ' . $ringkas . ')' : ''),
            (array) $lama, null);

        return redirect()->route('master.index', $entitas)->with('success', $cfg['judul'] . ' berhasil dihapus' . ($ringkas ? ' (ikut terhapus: ' . $ringkas . ').' : '.'));
    }

    /** Hapus data induk beserta turunannya secara berurutan (tidak bergantung pada ON DELETE CASCADE). */
    private function hapusBeserta(array $cfg, string $id, object $lama): void
    {
        if ($cfg['key'] === 'mata-pelajaran') {
            $jadwalIds = DB::table('jadwal_pelajaran')->where('id_mapel', $id)->pluck('id_jadwal')->all();
            foreach (array_chunk($jadwalIds, 500) as $chunk) {
                if (Schema::hasTable('journals')) {
                    if (Schema::hasTable('attendances')) {
                        $jurnalIds = DB::table('journals')->whereIn('schedule_id', $chunk)->pluck('id')->all();
                        foreach (array_chunk($jurnalIds, 500) as $j) {
                            DB::table('attendances')->whereIn('journal_id', $j)->delete();
                        }
                    }
                    DB::table('journals')->whereIn('schedule_id', $chunk)->delete();
                }
                if (Schema::hasTable('presensi_header')) {
                    $pres = DB::table('presensi_header')->whereIn('id_jadwal', $chunk)->pluck('id_presensi')->all();
                    foreach (array_chunk($pres, 500) as $p) {
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
        } else {
            // Siswa: kehadiran, presensi lama, izin, lalu akun login (username = NISN)
            if (Schema::hasTable('attendances')) {
                DB::table('attendances')->where('student_id', $id)->delete();
            }
            if (Schema::hasTable('presensi_detail')) {
                DB::table('presensi_detail')->where('id_siswa', $id)->delete();
            }
            if (Schema::hasTable('leave_requests')) {
                DB::table('leave_requests')->where('student_id', $id)->delete();
            }
            $akun = DB::table('users')->where('username', $lama->nisn)->where('id_role', 5)->pluck('id_user')->all();
            if ($akun) {
                if (Schema::hasTable('leave_requests')) {
                    DB::table('leave_requests')->whereIn('verified_by', $akun)->update(['verified_by' => null]);
                }
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->whereIn('user_id', $akun)->delete();
                }
                DB::table('users')->whereIn('id_user', $akun)->delete();
            }
        }

        DB::table($cfg['tabel'])->where($cfg['pk'], $id)->delete();
    }
}
