<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use App\Services\JadwalImportService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Impor jadwal pelajaran dari Excel — khusus Waka Kurikulum.
 * Alur: unggah -> pratinjau (tanpa mengubah data) -> simpan.
 */
class JadwalImportController extends Controller
{
    private const FOLDER = 'import-jadwal';

    private function cekAkses()
    {
        if (!session()->has('id_user') || (int) session('id_role') !== 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Impor jadwal hanya dapat dilakukan oleh Waka Kurikulum.']);
        }
        return null;
    }

    private function terkunci(): bool
    {
        $config = SystemConfiguration::first();
        return (bool) ($config && $config->is_schedule_locked);
    }

    public function template(Request $request)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $putaran = $request->query('putaran');
        if (!in_array($putaran, ['Putaran 1', 'Putaran 2'], true)) {
            $putaran = null;
        }
        $tahun = $putaran ? (string) $request->query('tahun', '') : null;
        $isi = JadwalImportService::buatTemplate($putaran, $tahun ?: null);
        $nama = $putaran ? 'jadwal_' . Str::slug($putaran, '_') . '.xlsx' : 'format_import_jadwal.xlsx';

        return response($isi, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nama . '"',
        ]);
    }

    public function periksa(Request $request)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $request->validate([
            'file_excel' => 'required|file|max:10240',
            'id_tahun_ajaran' => 'required|exists:tahun_ajaran,id_tahun_ajaran',
            'putaran' => 'required|in:Putaran 1,Putaran 2',
            'mode' => 'required|in:tambah,timpa',
        ], [
            'file_excel.required' => 'File Excel / CSV wajib diunggah.',
            'file_excel.max' => 'Ukuran file maksimal 10MB.',
            'id_tahun_ajaran.exists' => 'Tahun ajaran tidak valid.',
        ]);

        if ($this->terkunci()) {
            return back()->withErrors(['error' => 'Jadwal sedang dikunci. Buka kunci jadwal terlebih dahulu sebelum mengimpor.']);
        }

        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            return back()->withErrors(['file_excel' => 'Format file harus .xlsx atau .csv.']);
        }

        // Rapikan berkas impor lama (> 1 hari) dan berkas milik sesi ini sebelumnya
        $this->bersihkanBerkas();
        $this->hapusSesiLama();

        $token = Str::random(40);
        $file->storeAs(self::FOLDER, $token . '.' . $ext, 'local');

        // Pastikan file terbaca dan formatnya sesuai sebelum lanjut
        try {
            JadwalImportService::bacaBaris(Storage::disk('local')->path(self::FOLDER . '/' . $token . '.' . $ext), $ext);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(self::FOLDER . '/' . $token . '.' . $ext);
            return back()->withErrors(['file_excel' => $e->getMessage()]);
        }

        session(['import_jadwal' => [
            'token' => $token, 'ext' => $ext, 'nama_file' => $file->getClientOriginalName(),
            'tahun' => $request->id_tahun_ajaran, 'putaran' => $request->putaran, 'mode' => $request->mode,
            'id_user' => session('id_user'),
        ]]);

        return redirect()->route('jadwal.import.pratinjau');
    }

    private function meta(): ?array
    {
        $m = session('import_jadwal');
        if (!is_array($m) || ($m['id_user'] ?? null) !== session('id_user') || !preg_match('/^[A-Za-z0-9]{40}$/', $m['token'] ?? '')) {
            return null;
        }
        return Storage::disk('local')->exists(self::FOLDER . '/' . $m['token'] . '.' . $m['ext']) ? $m : null;
    }

    private function jalankanPemeriksaan(array $m): array
    {
        $baris = JadwalImportService::bacaBaris(Storage::disk('local')->path(self::FOLDER . '/' . $m['token'] . '.' . $m['ext']), $m['ext']);
        return JadwalImportService::periksa($baris, $m['tahun'], $m['putaran'], $m['mode']);
    }

    public function pratinjau()
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $m = $this->meta();
        if (!$m) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Berkas impor tidak ditemukan atau sesi sudah berakhir. Silakan unggah ulang.']);
        }
        try {
            $hasil = $this->jalankanPemeriksaan($m);
        } catch (\Throwable $e) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        return view('jadwal.import_preview', [
            'm' => $m, 'h' => $hasil, 'terkunci' => $this->terkunci(),
            'tahunNama' => DB::table('tahun_ajaran')->where('id_tahun_ajaran', $m['tahun'])->value('nama_semester') ?? $m['tahun'],
        ]);
    }

    public function simpan(Request $request)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $m = $this->meta();
        if (!$m || !hash_equals($m['token'], (string) $request->input('token'))) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Berkas impor tidak ditemukan atau sesi sudah berakhir. Silakan unggah ulang.']);
        }
        if ($this->terkunci()) {
            return back()->withErrors(['error' => 'Jadwal sedang dikunci. Buka kunci jadwal terlebih dahulu.']);
        }

        try {
            $hasil = $this->jalankanPemeriksaan($m);
        } catch (\Throwable $e) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        if ($hasil['jumlah_error'] > 0) {
            return redirect()->route('jadwal.import.pratinjau')->withErrors(['error' => 'Masih ada baris bermasalah (data berubah sejak pratinjau). Tidak ada yang disimpan.']);
        }
        if (count($hasil['tambah']) === 0 && count($hasil['hapus_ids']) === 0) {
            return redirect()->route('jadwal.import.pratinjau')->withErrors(['error' => 'Tidak ada perubahan untuk disimpan.']);
        }
        if ((count($hasil['hapus_ids']) > 0 || $hasil['jumlah_peringatan'] > 0) && !$request->boolean('konfirmasi')) {
            return redirect()->route('jadwal.import.pratinjau')->withErrors(['error' => 'Centang kotak konfirmasi untuk melanjutkan.']);
        }

        try {
            $jumlah = JadwalImportService::simpan($hasil, $m['tahun'], $m['putaran']);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('jadwal.import.pratinjau')->withErrors(['error' => 'Gagal menyimpan jadwal. Tidak ada data yang berubah. Detail teknis tercatat di log server.']);
        }

        Audit::catat('impor', 'jadwal', null,
            sprintf('Impor jadwal dari Excel "%s" (%s, %s, mode %s): %d ditambahkan, %d tetap, %d dihapus (%d jurnal ikut terhapus)',
                $m['nama_file'], $m['tahun'], $m['putaran'], $m['mode'], $jumlah['tambah'], $hasil['tetap'], $jumlah['hapus'], $hasil['hapus_jurnal']),
            null, ['tambah' => $jumlah['tambah'], 'tetap' => $hasil['tetap'], 'hapus' => $jumlah['hapus'], 'jurnal_terhapus' => $hasil['hapus_jurnal']]);

        $this->hapusSesiLama();

        $pesan = "Impor jadwal berhasil: {$jumlah['tambah']} jadwal ditambahkan";
        if ($hasil['tetap'] > 0) {
            $pesan .= ", {$hasil['tetap']} tetap";
        }
        if ($jumlah['hapus'] > 0) {
            $pesan .= ", {$jumlah['hapus']} diganti";
        }
        return redirect()->route('jadwal.index')->with('success', $pesan . '.');
    }

    public function batal()
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }
        $this->hapusSesiLama();
        return redirect()->route('jadwal.index')->with('success', 'Impor dibatalkan. Tidak ada data yang berubah.');
    }

    private function hapusSesiLama(): void
    {
        $m = session('import_jadwal');
        if (is_array($m) && preg_match('/^[A-Za-z0-9]{40}$/', $m['token'] ?? '') && in_array($m['ext'] ?? '', ['xlsx', 'csv'], true)) {
            Storage::disk('local')->delete(self::FOLDER . '/' . $m['token'] . '.' . $m['ext']);
        }
        session()->forget('import_jadwal');
    }

    private function bersihkanBerkas(): void
    {
        try {
            $disk = Storage::disk('local');
            foreach ($disk->files(self::FOLDER) as $f) {
                if ($disk->lastModified($f) < now()->subDay()->getTimestamp()) {
                    $disk->delete($f);
                }
            }
        } catch (\Throwable $e) {
            // abaikan: pembersihan hanya pelengkap
        }
    }
}
