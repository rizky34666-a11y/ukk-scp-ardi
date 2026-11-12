<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Audit;

class WakaController extends Controller
{
    public function dashboard()
    {
        // PENGAMAN ANTI-BOBOL: Cek apakah user yang login memiliki id_role == 4 (Waka Kurikulum)
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }

        $config = DB::table('system_configurations')->first();

        if (!$config) {
            DB::table('system_configurations')->insert([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
            ]);
            $config = DB::table('system_configurations')->first();
        }

        return view('waka.dashboard', compact('config'));
    }

    public function updateConfig(Request $request)
    {
        // PENGAMAN ANTI-BOBOL: Pastikan hanya Waka Kurikulum yang bisa menyimpan perubahan konfigurasi
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Anda tidak memiliki hak untuk mengubah konfigurasi.']);
        }

        $request->validate([
            'active_cycle' => 'required|in:1,2,Putaran 1,Putaran 2',
            'is_schedule_locked' => 'required|boolean',
        ]);

        // Kolom active_cycle bertipe enum('Putaran 1','Putaran 2'); terima juga nilai "1"/"2" dari formulir
        $siklus = in_array($request->active_cycle, ['1', '2'], true) ? 'Putaran ' . $request->active_cycle : $request->active_cycle;

        $lama = DB::table('system_configurations')->where('id', 1)->first(['active_cycle', 'is_schedule_locked']);
        DB::table('system_configurations')->where('id', 1)->update([
            'active_cycle' => $siklus,
            'is_schedule_locked' => $request->is_schedule_locked,
        ]);
        Audit::catat('ubah', 'konfigurasi', 1, 'Mengubah putaran aktif / kunci jadwal', $lama,
            ['active_cycle' => $siklus, 'is_schedule_locked' => $request->is_schedule_locked]);

        return back()->with('success', 'Konfigurasi siklus dan status jadwal berhasil diperbarui!');
    }
}