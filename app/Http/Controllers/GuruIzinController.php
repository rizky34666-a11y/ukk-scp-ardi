<?php

namespace App\Http\Controllers;

use App\Models\IzinGuru;
use App\Services\JadwalHariIni;
use App\Support\Audit;
use Illuminate\Http\Request;

/** Pengajuan izin mengajar oleh guru (Guru Pengampu, Wali Kelas, Guru BK). */
class GuruIzinController extends Controller
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

    public function index()
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }

        $guru = JadwalHariIni::guruLogin();
        $riwayat = $guru
            ? IzinGuru::where('id_guru', $guru->id_guru)->orderByDesc('created_at')->limit(50)->get()
            : collect();

        return view('guru.izin.index', [
            'guru' => $guru,
            'riwayat' => $riwayat,
            'jenisList' => IzinGuru::JENIS,
        ]);
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
            'jenis' => 'required|in:' . implode(',', IzinGuru::JENIS),
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:5|max:1000',
            'tugas_pengganti' => 'nullable|string|max:1000',
            'lampiran' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'alasan.required' => 'Keterangan alasan wajib diisi.',
            'alasan.min' => 'Keterangan alasan terlalu singkat (minimal 5 karakter).',
            'lampiran.max' => 'Ukuran lampiran maksimal 2 MB.',
            'lampiran.mimes' => 'Lampiran harus berformat PDF, JPG, atau PNG.',
        ]);

        // Cegah pengajuan ganda pada tanggal yang beririsan
        $bentrok = IzinGuru::where('id_guru', $guru->id_guru)
            ->whereIn('status', ['Menunggu', 'Disetujui'])
            ->whereDate('tanggal_mulai', '<=', $data['tanggal_selesai'])
            ->whereDate('tanggal_selesai', '>=', $data['tanggal_mulai'])
            ->exists();
        if ($bentrok) {
            return back()->withErrors(['error' => 'Sudah ada pengajuan izin (menunggu/disetujui) yang beririsan dengan tanggal tersebut.'])->withInput();
        }

        $path = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $nama = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads/izin-guru'), $nama);
            $path = 'uploads/izin-guru/' . $nama;
        }

        $izin = IzinGuru::create([
            'id_guru' => $guru->id_guru,
            'id_user' => session('id_user'),
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'jenis' => $data['jenis'],
            'alasan' => $data['alasan'],
            'tugas_pengganti' => $data['tugas_pengganti'] ?? null,
            'lampiran' => $path,
            'status' => 'Menunggu',
        ]);

        Audit::catat('tambah', 'izin_guru', $izin->id,
            'Mengajukan izin mengajar (' . $data['jenis'] . ') ' . $data['tanggal_mulai'] . ' s.d. ' . $data['tanggal_selesai'],
            null, ['jenis' => $data['jenis'], 'tanggal_mulai' => $data['tanggal_mulai'], 'tanggal_selesai' => $data['tanggal_selesai'], 'alasan' => $data['alasan']]);

        return redirect()->route('guru.izin.index')->with('success', 'Pengajuan izin dikirim dan menunggu persetujuan Waka Kurikulum.');
    }

    /** Guru membatalkan pengajuannya sendiri selama masih Menunggu. */
    public function destroy($id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }

        $guru = JadwalHariIni::guruLogin();
        $izin = $guru ? IzinGuru::where('id', $id)->where('id_guru', $guru->id_guru)->first() : null;
        if (!$izin) {
            return back()->withErrors(['error' => 'Pengajuan tidak ditemukan.']);
        }
        if ($izin->status !== 'Menunggu') {
            return back()->withErrors(['error' => 'Hanya pengajuan yang masih menunggu yang dapat dibatalkan.']);
        }

        $lama = $izin->only(['jenis', 'tanggal_mulai', 'tanggal_selesai', 'status']);
        $izin->delete();
        Audit::catat('hapus', 'izin_guru', $id, 'Membatalkan pengajuan izin mengajar', $lama);

        return redirect()->route('guru.izin.index')->with('success', 'Pengajuan izin dibatalkan.');
    }
}
