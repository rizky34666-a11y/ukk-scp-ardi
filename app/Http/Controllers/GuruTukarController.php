<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Ruangan;
use App\Models\TukarJadwal;
use App\Services\JadwalHariIni;
use App\Services\TukarJadwalService as Svc;
use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Tukar ruangan / tukar jam pelajaran antar guru (Guru Pengampu, Wali Kelas, Guru BK). */
class GuruTukarController extends Controller
{
    private function cekAkses()
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }
        if (!in_array((int) session('id_role'), [2, 3, 7], true)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk guru.']);
        }
        return null;
    }

    private function namaJadwal(JadwalPelajaran $j): string
    {
        return ($j->mataPelajaran?->nama_mapel ?? $j->id_mapel) . ' - ' . ($j->kelas?->nama_kelas ?? $j->id_kelas)
            . ' (' . substr((string) $j->jam_mulai, 0, 5) . '-' . substr((string) $j->jam_selesai, 0, 5) . ')';
    }

    public function index()
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        Svc::kedaluwarsakan();

        $guru = JadwalHariIni::guruLogin();
        $masuk = collect();
        $saya = collect();
        if ($guru) {
            $with = ['jadwalPemohon.kelas', 'jadwalPemohon.mataPelajaran', 'jadwalPemohon.ruangan',
                     'jadwalTujuan.kelas', 'jadwalTujuan.mataPelajaran', 'jadwalTujuan.ruangan',
                     'guruPemohon', 'guruTujuan', 'ruanganDiminta'];
            $masuk = TukarJadwal::with($with)->where('id_guru_tujuan', $guru->id_guru)
                ->orderByRaw("status = 'Menunggu' desc")->orderByDesc('tanggal')->limit(30)->get();
            $saya = TukarJadwal::with($with)->where('id_guru_pemohon', $guru->id_guru)
                ->orderByDesc('created_at')->limit(30)->get();
        }

        return view('guru.tukar.index', [
            'guru' => $guru,
            'masuk' => $masuk,
            'saya' => $saya,
            'hariMaks' => Svc::HARI_MAKS,
            'menitBatas' => Svc::MENIT_BATAS,
        ]);
    }

    /** Data pilihan untuk form (JSON): jadwal saya, ruangan, atau kandidat tukar jam. */
    public function opsi(Request $request)
    {
        if ($this->cekAkses()) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }
        $guru = JadwalHariIni::guruLogin();
        $tanggal = (string) $request->query('tanggal', '');
        if (!$guru || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !checkdate((int) substr($tanggal, 5, 2), (int) substr($tanggal, 8, 2), (int) substr($tanggal, 0, 4))) {
            return response()->json(['jadwal_saya' => []]);
        }

        Svc::kedaluwarsakan();
        $now = Carbon::now();
        $efektif = Svc::jadwalEfektif($tanggal);
        $terpakai = Svc::jadwalTerpakai($tanggal);
        $izin = Svc::guruIzin($tanggal, $guru->id_guru);

        $out = ['jadwal_saya' => []];
        foreach ($efektif->where('id_guru', $guru->id_guru)->sortBy('jam_mulai') as $j) {
            $alasan = null;
            if ($izin) {
                $alasan = 'Anda sedang izin pada tanggal ini';
            } elseif (Svc::batasWaktu($tanggal, $j->jam_mulai)->lte($now)) {
                $alasan = 'Sudah melewati batas (30 menit sebelum jam mulai)';
            } elseif (in_array((int) $j->id_jadwal, $terpakai, true)) {
                $alasan = 'Sudah ada pengajuan tukar untuk jadwal ini';
            }
            $out['jadwal_saya'][] = [
                'id' => $j->id_jadwal,
                'label' => $this->namaJadwal($j) . ' - ' . ($j->ruangan?->nama_ruangan ?? 'tanpa ruang'),
                'bisa' => $alasan === null,
                'alasan' => $alasan,
            ];
        }

        $idJadwal = (int) $request->query('id_jadwal', 0);
        $jenis = (string) $request->query('jenis', '');
        $saya = $idJadwal ? $efektif->firstWhere('id_jadwal', $idJadwal) : null;
        if ($saya && (int) $saya->id_guru === (int) $guru->id_guru) {
            if ($jenis === 'Ruangan') {
                $out['ruangan'] = [];
                foreach (Ruangan::orderBy('nama_ruangan')->get() as $r) {
                    $item = ['id' => $r->id_ruangan, 'nama' => $r->nama_ruangan . ' (' . $r->jenis . ')', 'status' => 'kosong', 'dapat' => true, 'info' => 'kosong'];
                    if ((string) $r->id_ruangan === (string) $saya->id_ruangan_default) {
                        $item['status'] = 'sama'; $item['dapat'] = false; $item['info'] = 'ruangan Anda saat ini';
                    } else {
                        $pemakai = Svc::pemakaiRuangan($efektif, (string) $r->id_ruangan, $saya);
                        if ($pemakai->isNotEmpty()) {
                            $p = $pemakai->first();
                            $item['status'] = 'dipakai';
                            $item['info'] = 'dipakai ' . ($p->guru?->nama_guru ?? '-') . ' / ' . ($p->kelas?->nama_kelas ?? $p->id_kelas);
                            $item['dapat'] = $pemakai->count() === 1 && $p->jam_mulai === $saya->jam_mulai && $p->jam_selesai === $saya->jam_selesai
                                && (int) $p->id_guru !== (int) $guru->id_guru && !in_array((int) $p->id_jadwal, $terpakai, true);
                            if (!$item['dapat']) {
                                $item['info'] .= ' (tidak dapat ditukar: jamnya tidak sama persis atau sedang ada pengajuan lain)';
                            } else {
                                $item['info'] .= ' - perlu persetujuan guru tersebut';
                            }
                        }
                    }
                    $out['ruangan'][] = $item;
                }
            } elseif ($jenis === 'Jam') {
                $out['kandidat'] = [];
                $durasi = Svc::durasi($saya);
                foreach ($efektif->where('id_kelas', $saya->id_kelas)->sortBy('jam_mulai') as $k) {
                    if ((int) $k->id_jadwal === (int) $saya->id_jadwal || (int) $k->id_guru === (int) $guru->id_guru) {
                        continue;
                    }
                    $info = null;
                    if ($k->jam_mulai === $saya->jam_mulai) {
                        continue;
                    } elseif (Svc::durasi($k) !== $durasi) {
                        $info = 'durasi jam berbeda';
                    } elseif (in_array((int) $k->id_jadwal, $terpakai, true)) {
                        $info = 'sedang ada pengajuan lain';
                    } elseif (Svc::guruIzin($tanggal, (int) $k->id_guru)) {
                        $info = 'guru sedang izin';
                    } else {
                        $info = Svc::cekBentrokJam($efektif, $saya, $k);
                    }
                    $out['kandidat'][] = [
                        'id' => $k->id_jadwal,
                        'label' => $this->namaJadwal($k) . ' - ' . ($k->guru?->nama_guru ?? '-'),
                        'dapat' => $info === null,
                        'info' => $info,
                    ];
                }
            }
        }
        return response()->json($out);
    }

    public function store(Request $request)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $guru = JadwalHariIni::guruLogin();
        if (!$guru) {
            return back()->withErrors(['error' => 'Akun Anda belum terhubung dengan data guru (NIP). Hubungi Waka Kurikulum.']);
        }

        $data = $request->validate([
            'jenis' => 'required|in:Ruangan,Jam',
            'tanggal' => 'required|date|after_or_equal:today|before_or_equal:' . Carbon::now()->addDays(Svc::HARI_MAKS)->toDateString(),
            'id_jadwal' => 'required|integer',
            'id_ruangan' => 'required_if:jenis,Ruangan|nullable|string|max:20',
            'id_jadwal_tujuan' => 'required_if:jenis,Jam|nullable|integer',
            'alasan' => 'nullable|string|max:255',
        ], [
            'tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
            'tanggal.before_or_equal' => 'Pengajuan hanya untuk maksimal ' . Svc::HARI_MAKS . ' hari ke depan.',
            'id_ruangan.required_if' => 'Pilih ruangan tujuan.',
            'id_jadwal_tujuan.required_if' => 'Pilih jadwal guru yang diajak bertukar jam.',
        ]);

        Svc::kedaluwarsakan();
        $tanggal = Carbon::parse($data['tanggal'])->toDateString();
        $now = Carbon::now();
        $efektif = Svc::jadwalEfektif($tanggal);
        $terpakai = Svc::jadwalTerpakai($tanggal);

        $saya = $efektif->firstWhere('id_jadwal', (int) $data['id_jadwal']);
        if (!$saya || (int) $saya->id_guru !== (int) $guru->id_guru) {
            return back()->withErrors(['error' => 'Jadwal tidak ditemukan pada tanggal tersebut (periksa hari dan putaran aktif).'])->withInput();
        }
        if (Svc::guruIzin($tanggal, $guru->id_guru)) {
            return back()->withErrors(['error' => 'Anda sedang izin pada tanggal tersebut.'])->withInput();
        }
        if (in_array((int) $saya->id_jadwal, $terpakai, true)) {
            return back()->withErrors(['error' => 'Jadwal ini sudah memiliki pengajuan tukar pada tanggal tersebut.'])->withInput();
        }

        $dasar = [
            'jenis' => $data['jenis'],
            'tanggal' => $tanggal,
            'id_jadwal_pemohon' => $saya->id_jadwal,
            'id_guru_pemohon' => $guru->id_guru,
            'alasan' => $data['alasan'] ?? null,
        ];

        if ($data['jenis'] === 'Ruangan') {
            $batas = Svc::batasWaktu($tanggal, $saya->jam_mulai);
            if ($batas->lte($now)) {
                return back()->withErrors(['error' => 'Waktu pengajuan sudah lewat: pengajuan ditutup ' . Svc::MENIT_BATAS . ' menit sebelum jam pelajaran dimulai.'])->withInput();
            }
            $ruang = Ruangan::find($data['id_ruangan']);
            if (!$ruang) {
                return back()->withErrors(['error' => 'Ruangan tidak ditemukan.'])->withInput();
            }
            if ((string) $ruang->id_ruangan === (string) $saya->id_ruangan_default) {
                return back()->withErrors(['error' => 'Itu sudah ruangan Anda pada jam tersebut.'])->withInput();
            }

            $pemakai = Svc::pemakaiRuangan($efektif, (string) $ruang->id_ruangan, $saya);
            $dasar += ['id_ruangan_diminta' => $ruang->id_ruangan, 'batas_waktu' => $batas];

            if ($pemakai->isEmpty()) {
                // Ruangan kosong: langsung berlaku
                $t = TukarJadwal::create($dasar + [
                    'status' => 'Disetujui',
                    'diproses_pada' => $now,
                    'perubahan' => [(int) $saya->id_jadwal => ['ruangan' => (string) $ruang->id_ruangan]],
                ]);
                Audit::catat('tambah', 'tukar_jadwal', $t->id,
                    'Pindah ruangan ' . $this->namaJadwal($saya) . ' ke ' . $ruang->nama_ruangan . ' pada ' . $tanggal . ' (ruangan kosong, langsung berlaku)');
                return redirect()->route('guru.tukar.index')->with('success', 'Ruangan kosong: perpindahan ke ' . $ruang->nama_ruangan . ' langsung berlaku pada ' . Carbon::parse($tanggal)->format('d/m/Y') . '.');
            }

            $p = $pemakai->first();
            if ($pemakai->count() !== 1 || $p->jam_mulai !== $saya->jam_mulai || $p->jam_selesai !== $saya->jam_selesai) {
                return back()->withErrors(['error' => 'Ruangan itu dipakai pada jam yang tidak sama persis dengan jadwal Anda, sehingga tidak dapat ditukar. Pilih ruangan lain atau hubungi Waka Kurikulum.'])->withInput();
            }
            if ((int) $p->id_guru === (int) $guru->id_guru) {
                return back()->withErrors(['error' => 'Ruangan itu sudah dipakai jadwal Anda sendiri.'])->withInput();
            }
            if (in_array((int) $p->id_jadwal, $terpakai, true)) {
                return back()->withErrors(['error' => 'Jadwal pemakai ruangan itu sedang ada pengajuan tukar lain.'])->withInput();
            }
            if (Svc::guruIzin($tanggal, (int) $p->id_guru)) {
                return back()->withErrors(['error' => 'Guru pemakai ruangan itu sedang izin pada tanggal tersebut. Hubungi Waka Kurikulum.'])->withInput();
            }

            $t = TukarJadwal::create($dasar + [
                'id_jadwal_tujuan' => $p->id_jadwal,
                'id_guru_tujuan' => $p->id_guru,
                'status' => 'Menunggu',
            ]);
            Audit::catat('tambah', 'tukar_jadwal', $t->id,
                'Mengajukan tukar ruangan ' . $tanggal . ': ' . $this->namaJadwal($saya) . ' dengan ' . $this->namaJadwal($p) . ' (menunggu ' . ($p->guru?->nama_guru ?? '-') . ')');
            return redirect()->route('guru.tukar.index')->with('success', 'Pengajuan dikirim. Menunggu persetujuan ' . ($p->guru?->nama_guru ?? 'guru pemakai ruangan') . '.');
        }

        // ---- Tukar jam ----
        $tujuan = $efektif->firstWhere('id_jadwal', (int) ($data['id_jadwal_tujuan'] ?? 0));
        if (!$tujuan) {
            return back()->withErrors(['error' => 'Jadwal tujuan tidak ditemukan pada tanggal tersebut.'])->withInput();
        }
        if ((string) $tujuan->id_kelas !== (string) $saya->id_kelas) {
            return back()->withErrors(['error' => 'Tukar jam hanya untuk kelas yang sama.'])->withInput();
        }
        if ((int) $tujuan->id_guru === (int) $guru->id_guru) {
            return back()->withErrors(['error' => 'Pilih jadwal milik guru lain.'])->withInput();
        }
        if (Svc::durasi($tujuan) !== Svc::durasi($saya)) {
            return back()->withErrors(['error' => 'Lama jam pelajaran kedua jadwal harus sama.'])->withInput();
        }
        if (in_array((int) $tujuan->id_jadwal, $terpakai, true)) {
            return back()->withErrors(['error' => 'Jadwal tujuan sedang ada pengajuan tukar lain.'])->withInput();
        }
        if (Svc::guruIzin($tanggal, (int) $tujuan->id_guru)) {
            return back()->withErrors(['error' => 'Guru yang diajak bertukar sedang izin pada tanggal tersebut.'])->withInput();
        }
        $batas = Svc::batasWaktu($tanggal, $saya->jam_mulai, $tujuan->jam_mulai);
        if ($batas->lte($now)) {
            return back()->withErrors(['error' => 'Waktu pengajuan sudah lewat: pengajuan ditutup ' . Svc::MENIT_BATAS . ' menit sebelum jam pelajaran yang lebih awal dimulai.'])->withInput();
        }
        if ($salah = Svc::cekBentrokJam($efektif, $saya, $tujuan)) {
            return back()->withErrors(['error' => $salah])->withInput();
        }

        $t = TukarJadwal::create($dasar + [
            'id_jadwal_tujuan' => $tujuan->id_jadwal,
            'id_guru_tujuan' => $tujuan->id_guru,
            'batas_waktu' => $batas,
            'status' => 'Menunggu',
        ]);
        Audit::catat('tambah', 'tukar_jadwal', $t->id,
            'Mengajukan tukar jam ' . $tanggal . ': ' . $this->namaJadwal($saya) . ' dengan ' . $this->namaJadwal($tujuan) . ' (menunggu ' . ($tujuan->guru?->nama_guru ?? '-') . ')');
        return redirect()->route('guru.tukar.index')->with('success', 'Pengajuan tukar jam dikirim. Menunggu persetujuan ' . ($tujuan->guru?->nama_guru ?? 'guru terkait') . '.');
    }

    /** Guru tujuan menyetujui. */
    public function setujui($id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        Svc::kedaluwarsakan();
        $guru = JadwalHariIni::guruLogin();
        $t = $guru ? TukarJadwal::where('id', $id)->where('id_guru_tujuan', $guru->id_guru)->first() : null;
        if (!$t) {
            return back()->withErrors(['error' => 'Pengajuan tidak ditemukan.']);
        }
        if ($t->status !== 'Menunggu') {
            return back()->withErrors(['error' => 'Pengajuan ini sudah ' . strtolower($t->status) . '.']);
        }
        if ($t->batas_waktu->lte(Carbon::now())) {
            $t->update(['status' => 'Kedaluwarsa', 'catatan' => 'Tidak disetujui sebelum batas waktu.']);
            return back()->withErrors(['error' => 'Pengajuan sudah kedaluwarsa (lewat batas waktu).']);
        }

        $tanggal = $t->tanggal->toDateString();
        $efektif = Svc::jadwalEfektif($tanggal);
        $a = $efektif->firstWhere('id_jadwal', $t->id_jadwal_pemohon);
        $b = $efektif->firstWhere('id_jadwal', $t->id_jadwal_tujuan);
        if (!$a || !$b) {
            return back()->withErrors(['error' => 'Jadwal terkait sudah tidak tersedia.']);
        }

        if ($t->jenis === 'Ruangan') {
            if ((string) $b->id_ruangan_default !== (string) $t->id_ruangan_diminta || $a->jam_mulai !== $b->jam_mulai || $a->jam_selesai !== $b->jam_selesai) {
                return back()->withErrors(['error' => 'Kondisi ruangan sudah berubah sejak pengajuan dibuat. Minta pemohon mengajukan ulang.']);
            }
            $perubahan = Svc::perubahanRuangTukar($a, $b);
        } else {
            if ($salah = Svc::cekBentrokJam($efektif, $a, $b)) {
                return back()->withErrors(['error' => 'Tidak dapat disetujui: ' . $salah]);
            }
            $perubahan = Svc::perubahanJam($a, $b);
        }

        $t->update(['status' => 'Disetujui', 'perubahan' => $perubahan, 'diproses_pada' => Carbon::now()]);
        Audit::catat('verifikasi', 'tukar_jadwal', $t->id,
            'Menyetujui tukar ' . strtolower($t->jenis) . ' ' . $tanggal . ': ' . $this->namaJadwal($a) . ' dengan ' . $this->namaJadwal($b));

        return redirect()->route('guru.tukar.index')->with('success', 'Pengajuan disetujui. Jadwal pada tanggal tersebut sudah diperbarui.');
    }

    public function tolak(Request $request, $id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        Svc::kedaluwarsakan();
        $guru = JadwalHariIni::guruLogin();
        $t = $guru ? TukarJadwal::where('id', $id)->where('id_guru_tujuan', $guru->id_guru)->first() : null;
        if (!$t || $t->status !== 'Menunggu') {
            return back()->withErrors(['error' => 'Pengajuan tidak ditemukan atau sudah diproses.']);
        }
        $t->update(['status' => 'Ditolak', 'catatan' => mb_substr((string) $request->input('catatan', ''), 0, 255) ?: null, 'diproses_pada' => Carbon::now()]);
        Audit::catat('verifikasi', 'tukar_jadwal', $t->id, 'Menolak tukar ' . strtolower($t->jenis) . ' tanggal ' . $t->tanggal->toDateString());
        return redirect()->route('guru.tukar.index')->with('success', 'Pengajuan ditolak.');
    }

    /** Pemohon membatalkan selama belum melewati batas waktu. */
    public function batal($id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        Svc::kedaluwarsakan();
        $guru = JadwalHariIni::guruLogin();
        $t = $guru ? TukarJadwal::where('id', $id)->where('id_guru_pemohon', $guru->id_guru)->first() : null;
        if (!$t || !in_array($t->status, ['Menunggu', 'Disetujui'], true)) {
            return back()->withErrors(['error' => 'Pengajuan tidak ditemukan atau tidak dapat dibatalkan.']);
        }
        if ($t->batas_waktu->lte(Carbon::now())) {
            return back()->withErrors(['error' => 'Sudah melewati batas waktu, pengajuan tidak dapat dibatalkan.']);
        }
        $t->update(['status' => 'Dibatalkan', 'diproses_pada' => Carbon::now()]);
        Audit::catat('hapus', 'tukar_jadwal', $t->id, 'Membatalkan tukar ' . strtolower($t->jenis) . ' tanggal ' . $t->tanggal->toDateString());
        return redirect()->route('guru.tukar.index')->with('success', 'Pengajuan dibatalkan.');
    }
}
