<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Siswa;
use App\Services\JadwalHariIni;

class SiswaDashboardController extends Controller
{
    public function index()
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }
        if (session('id_role') != 5) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Siswa.']);
        }

        // Username siswa berisi NISN
        $siswa = Siswa::with('kelas.jurusan')->where('nisn', session('username'))->first();
        $jadwalHariIni = JadwalHariIni::untukKelas($siswa?->id_kelas);

        $izinMenunggu = $siswa
            ? LeaveRequest::where('student_id', $siswa->id_siswa)->where('status_approval', 'Pending')->count()
            : 0;

        return view('siswa.dashboard', compact('siswa', 'jadwalHariIni', 'izinMenunggu'));
    }
}
