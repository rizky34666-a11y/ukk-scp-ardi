<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function dashboard()
    {
        // Pengaman anti-bobol: Pastikan yang masuk adalah role Guru atau Wali Kelas
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $role = session('id_role');

        // Role 2 = Guru Pengampu, Role 3 = Wali Kelas (dual-role: juga bisa akses jurnal & jadwal)
        if ($role != 2 && $role != 3) {
            if ($role == 7) {
                return redirect()->route('kesiswaan.izin.index');
            }
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Guru.']);
        }

        return view('guru.dashboard');
    }
}
