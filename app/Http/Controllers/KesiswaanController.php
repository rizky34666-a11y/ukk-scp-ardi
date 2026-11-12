<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use App\Support\Audit;

class KesiswaanController extends Controller
{
    public function index(Request $request)
    {
        // Pengaman Hak Akses Tim Kesiswaan (Wali Kelas / BK / Waka)
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idRole = (int) session('id_role');

        if (!in_array($idRole, [3, 7, 4])) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Wali Kelas dan Guru BK.']);
        }

        // Ambil daftar kelas dari database
        $daftarKelas = Kelas::leftJoin('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->select('kelas.*', 'jurusan.nama_jurusan')
            ->orderBy('kelas.tingkat')
            ->orderBy('jurusan.nama_jurusan')
            ->get();

        $isWaliKelas    = ($idRole === 3);
        $waliKelasId    = null;
        $waliKelasLabel = null;
        $selectedKelasId = null;

        if ($isWaliKelas) {
            // Tentukan kelas Wali Kelas: username wali_<kelas>, atau data guru bernama sama (NIP wali_<kelas>)
            $waliKelasId = Kelas::kelasWaliDariSesi($daftarKelas) ?: (session('id_kelas') ?? null);

            // Ambil label kelas wali
            if ($waliKelasId) {
                $kelasObj = $daftarKelas->firstWhere('id_kelas', $waliKelasId);
                if ($kelasObj) {
                    $waliKelasLabel = trim(($kelasObj->tingkat ?? '') . ' ' . ($kelasObj->id_jurusan ?? $kelasObj->nama_jurusan ?? $kelasObj->id_kelas));
                }
            }

            // Build query - hanya tampilkan siswa dari kelas Wali Kelas ini
            $query = LeaveRequest::with(['student.kelas', 'verifier'])->latest();
            if ($waliKelasId) {
                $query->whereHas('student', function ($q) use ($waliKelasId) {
                    $q->where('id_kelas', $waliKelasId);
                });
            } else {
                $query->whereRaw('1 = 0'); // kelas perwalian tidak dikenali: jangan tampilkan kelas lain
            }
            $pendingLeaves = $query->get();

        } else {
            // Guru BK (7) atau Waka (4): bisa lihat semua kelas, dengan filter opsional
            $selectedKelasId = $request->filled('id_kelas') ? $request->id_kelas : null;

            $query = LeaveRequest::with(['student.kelas', 'verifier'])->latest();
            if ($selectedKelasId) {
                $query->whereHas('student', function ($q) use ($selectedKelasId) {
                    $q->where('id_kelas', $selectedKelasId);
                });
            }
            $pendingLeaves = $query->get();
        }

        return view('kesiswaan.izin.index', compact(
            'pendingLeaves',
            'daftarKelas',
            'isWaliKelas',
            'waliKelasId',
            'waliKelasLabel',
            'selectedKelasId'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'status_approval' => 'required|in:Approved,Rejected',
        ]);

        $leave = LeaveRequest::findOrFail($id);
        $statusLama = $leave->status_approval;
        $leave->update([
            'status_approval' => $request->status_approval,
            'verified_by'     => session('id_user'),
        ]);

        Audit::catat('verifikasi', 'izin', $leave->id,
            'Mengubah status pengajuan izin #' . $leave->id . ' menjadi ' . $request->status_approval,
            ['status_approval' => $statusLama], ['status_approval' => $request->status_approval]);

        return redirect()->back()->with('success', 'Status pengajuan izin berhasil diperbarui!');
    }
}