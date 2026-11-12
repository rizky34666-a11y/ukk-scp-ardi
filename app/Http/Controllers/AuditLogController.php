<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Halaman Log Aktivitas — hanya untuk Waka Kurikulum (role 4). Hanya-baca.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }
        if (session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Log aktivitas hanya untuk Waka Kurikulum.']);
        }

        $filter = [
            'q'      => trim((string) $request->query('q', '')),
            'modul'  => (string) $request->query('modul', ''),
            'aksi'   => (string) $request->query('aksi', ''),
            'dari'   => (string) $request->query('dari', ''),
            'sampai' => (string) $request->query('sampai', ''),
        ];

        $query = AuditLog::query()->orderByDesc('id');

        if ($filter['q'] !== '') {
            $q = '%' . str_replace(['%', '_'], ['\%', '\_'], $filter['q']) . '%';
            $query->where(function ($w) use ($q) {
                $w->where('deskripsi', 'like', $q)
                  ->orWhere('username', 'like', $q)
                  ->orWhere('nama_lengkap', 'like', $q)
                  ->orWhere('target_id', 'like', $q)
                  ->orWhere('ip_address', 'like', $q);
            });
        }
        if ($filter['modul'] !== '') {
            $query->where('modul', $filter['modul']);
        }
        if ($filter['aksi'] !== '') {
            $query->where('aksi', $filter['aksi']);
        }
        if ($this->tanggalValid($filter['dari'])) {
            $query->where('created_at', '>=', $filter['dari'] . ' 00:00:00');
        }
        if ($this->tanggalValid($filter['sampai'])) {
            $query->where('created_at', '<=', $filter['sampai'] . ' 23:59:59');
        }

        $logs = $query->paginate(20)->withQueryString();

        $modulList = AuditLog::query()->select('modul')->distinct()->orderBy('modul')->pluck('modul');
        $aksiList  = AuditLog::query()->select('aksi')->distinct()->orderBy('aksi')->pluck('aksi');

        return view('waka.audit-log', compact('logs', 'filter', 'modulList', 'aksiList'));
    }

    private function tanggalValid(string $v): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;
    }
}
